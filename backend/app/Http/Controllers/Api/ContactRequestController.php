<?php

namespace App\Http\Controllers\Api;

use App\Enums\ContactRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RespondContactRequestRequest;
use App\Http\Requests\StoreContactRequestRequest;
use App\Http\Resources\ContactRequestResource;
use App\Models\ContactRequest;
use App\Models\Need;
use App\Models\Project;
use App\Models\User;
use App\Services\ContactRequests\ContactRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ContactRequestController extends Controller
{
    private const RELATIONS = ['sender.roleAssignments', 'recipient.roleAssignments', 'project', 'need'];

    public function __construct(private readonly ContactRequestService $requests) {}

    /**
     * Demandes reçues (par défaut) ou envoyées, avec leur statut.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $box = $request->query('box') === 'sent' ? 'sentContactRequests' : 'receivedContactRequests';

        $requests = $request->user()->{$box}()
            ->with(self::RELATIONS)
            ->when(
                ContactRequestStatus::tryFrom((string) $request->query('status')),
                fn ($query, ContactRequestStatus $status) => $query->where('status', $status),
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return ContactRequestResource::collection($requests);
    }

    public function store(StoreContactRequestRequest $request): JsonResponse
    {
        $contactRequest = $this->requests->send(
            $request->user(),
            User::findOrFail($request->validated('recipient_id')),
            $request->validated('message'),
            $request->filled('project_id') ? Project::findOrFail($request->validated('project_id')) : null,
            $request->filled('need_id') ? Need::findOrFail($request->validated('need_id')) : null,
        );

        return (new ContactRequestResource($contactRequest->load(self::RELATIONS)))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, ContactRequest $contactRequest): ContactRequestResource
    {
        Gate::authorize('view', $contactRequest);

        $this->requests->markAsRead($contactRequest, $request->user());

        return new ContactRequestResource($contactRequest->load(self::RELATIONS));
    }

    public function respond(RespondContactRequestRequest $request, ContactRequest $contactRequest): ContactRequestResource
    {
        Gate::authorize('respond', $contactRequest);

        $contactRequest = $this->requests->respond(
            $contactRequest,
            $request->user(),
            ContactRequestStatus::from($request->validated('status')),
            $request->validated('refusal_reason'),
        );

        return new ContactRequestResource($contactRequest->load(self::RELATIONS));
    }
}
