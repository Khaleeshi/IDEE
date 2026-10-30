<?php

namespace App\Http\Controllers\Api;

use App\Enums\MeetingMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\DecisionRequest;
use App\Http\Requests\DeclareCollaborationRequest;
use App\Http\Requests\ProposeAgreementRequest;
use App\Http\Requests\ProposeMeetingRequest;
use App\Http\Resources\AgreementResource;
use App\Http\Resources\CollaborationResource;
use App\Http\Resources\MeetingResource;
use App\Models\Agreement;
use App\Models\Collaboration;
use App\Models\ContactRequest;
use App\Models\Meeting;
use App\Models\User;
use App\Services\Collaboration\AgreementService;
use App\Services\Collaboration\CollaborationService;
use App\Services\Collaboration\MeetingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * Prochaines étapes, rendez-vous et collaborations d'une conversation
 * (CO-01, CO-03, MR-07, FC-06).
 */
class CollaborationController extends Controller
{
    /**
     * Tout ce que les deux personnes ont convenu dans la conversation.
     */
    public function show(ContactRequest $contactRequest): JsonResponse
    {
        Gate::authorize('view', $contactRequest);

        $contactRequest->load(['agreements.owner', 'meetings', 'collaborations.declarer', 'collaborations.partner', 'collaborations.project']);

        return response()->json(['data' => [
            'agreements' => AgreementResource::collection($contactRequest->agreements),
            'meetings' => MeetingResource::collection($contactRequest->meetings),
            'collaborations' => CollaborationResource::collection($contactRequest->collaborations),
        ]]);
    }

    public function proposeAgreement(ProposeAgreementRequest $request, ContactRequest $contactRequest, AgreementService $agreements): JsonResponse
    {
        Gate::authorize('message', $contactRequest);

        $agreement = $agreements->propose(
            $contactRequest,
            $request->user(),
            $request->validated('objective'),
            Carbon::parse($request->validated('due_on')),
            User::findOrFail($request->validated('owner_id')),
        );

        return (new AgreementResource($agreement->load('owner')))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function respondAgreement(DecisionRequest $request, Agreement $agreement, AgreementService $agreements): AgreementResource
    {
        Gate::authorize('respond', $agreement);

        return new AgreementResource($agreements->respond($agreement, $request->user(), $request->boolean('accepted'))->load('owner'));
    }

    public function completeAgreement(Agreement $agreement, AgreementService $agreements): AgreementResource
    {
        Gate::authorize('complete', $agreement);

        return new AgreementResource($agreements->complete($agreement)->load('owner'));
    }

    public function proposeMeeting(ProposeMeetingRequest $request, ContactRequest $contactRequest, MeetingService $meetings): JsonResponse
    {
        Gate::authorize('message', $contactRequest);

        $meeting = $meetings->propose(
            $contactRequest,
            $request->user(),
            Carbon::parse($request->validated('starts_at')),
            MeetingMode::from($request->validated('mode')),
            $request->validated('place'),
            $request->validated('agenda'),
        );

        return (new MeetingResource($meeting))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function respondMeeting(DecisionRequest $request, Meeting $meeting, MeetingService $meetings): MeetingResource
    {
        Gate::authorize('respond', $meeting);

        return new MeetingResource($meetings->respond($meeting, $request->user(), $request->boolean('accepted')));
    }

    public function cancelMeeting(Request $request, Meeting $meeting, MeetingService $meetings): MeetingResource
    {
        Gate::authorize('cancel', $meeting);

        return new MeetingResource($meetings->cancel($meeting, $request->user()));
    }

    public function declare(DeclareCollaborationRequest $request, ContactRequest $contactRequest, CollaborationService $collaborations): JsonResponse
    {
        Gate::authorize('message', $contactRequest);

        $collaboration = $collaborations->declare($contactRequest, $request->user(), $request->validated('summary'));

        return (new CollaborationResource($collaboration))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function respondCollaboration(DecisionRequest $request, Collaboration $collaboration, CollaborationService $collaborations): CollaborationResource
    {
        Gate::authorize('respond', $collaboration);

        return new CollaborationResource($collaborations->respond($collaboration, $request->boolean('accepted')));
    }

    /**
     * Historique public des collaborations confirmées d'un membre (CO-03).
     */
    public function forMember(User $user, CollaborationService $collaborations): AnonymousResourceCollection
    {
        return CollaborationResource::collection($collaborations->confirmedFor($user));
    }
}
