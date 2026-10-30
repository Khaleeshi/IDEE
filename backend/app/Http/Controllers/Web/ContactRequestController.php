<?php

namespace App\Http\Controllers\Web;

use App\Enums\ContactRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RespondContactRequestRequest;
use App\Http\Requests\StoreContactRequestRequest;
use App\Models\ContactRequest;
use App\Models\Need;
use App\Models\Project;
use App\Models\User;
use App\Services\ContactRequests\ContactRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ContactRequestController extends Controller
{
    private const RELATIONS = ['sender', 'recipient', 'project', 'need'];

    public function __construct(private readonly ContactRequestService $requests) {}

    public function index(Request $request): View
    {
        $sent = $request->query('boite') === 'envoyees';

        $requests = $request->user()
            ->{$sent ? 'sentContactRequests' : 'receivedContactRequests'}()
            ->with(self::RELATIONS)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('contact-requests.index', ['requests' => $requests, 'sent' => $sent]);
    }

    public function create(Request $request, Project $project): View|RedirectResponse
    {
        abort_unless(Gate::allows('view', $project), 404);

        if ($project->isOwnedBy($request->user())) {
            return redirect()->route('projects.show', $project)->with('status', 'C\'est votre propre projet.');
        }

        $user = $request->user();

        return view('contact-requests.create', [
            'project' => $project->load('owner'),
            'need' => $request->filled('besoin') ? $project->openNeeds()->find($request->query('besoin')) : null,
            'quota' => $user->weeklyContactQuota(),
            'used' => $user->sentContactRequests()->where('created_at', '>=', now()->subWeek())->count(),
        ]);
    }

    public function store(StoreContactRequestRequest $request): RedirectResponse
    {
        $contactRequest = $this->requests->send(
            $request->user(),
            User::findOrFail($request->validated('recipient_id')),
            $request->validated('message'),
            $request->filled('project_id') ? Project::findOrFail($request->validated('project_id')) : null,
            $request->filled('need_id') ? Need::findOrFail($request->validated('need_id')) : null,
        );

        return redirect()->route('contact-requests.show', $contactRequest)
            ->with('status', 'Demande envoyée. Le destinataire a '.config('idee.contact_expire_after_days').' jours pour vous répondre.');
    }

    public function show(Request $request, ContactRequest $contactRequest): View
    {
        Gate::authorize('view', $contactRequest);

        $this->requests->markAsRead($contactRequest, $request->user());

        return view('contact-requests.show', [
            'contactRequest' => $contactRequest->load(self::RELATIONS),
            'canRespond' => $contactRequest->recipient_id === $request->user()->getKey() && $contactRequest->status->isPending(),
            'responses' => ContactRequestStatus::responses(),
        ]);
    }

    public function respond(RespondContactRequestRequest $request, ContactRequest $contactRequest): RedirectResponse
    {
        Gate::authorize('respond', $contactRequest);

        $this->requests->respond(
            $contactRequest,
            $request->user(),
            ContactRequestStatus::from($request->validated('status')),
            $request->validated('refusal_reason'),
        );

        return redirect()->route('contact-requests.show', $contactRequest)->with('status', 'Votre réponse a été envoyée.');
    }
}
