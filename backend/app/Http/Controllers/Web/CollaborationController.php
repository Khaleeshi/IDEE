<?php

namespace App\Http\Controllers\Web;

use App\Enums\MeetingMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\DecisionRequest;
use App\Http\Requests\DeclareCollaborationRequest;
use App\Http\Requests\ProposeAgreementRequest;
use App\Http\Requests\ProposeMeetingRequest;
use App\Models\Agreement;
use App\Models\Collaboration;
use App\Models\ContactRequest;
use App\Models\Meeting;
use App\Models\User;
use App\Services\Collaboration\AgreementService;
use App\Services\Collaboration\CollaborationService;
use App\Services\Collaboration\MeetingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * Prochaines étapes, rendez-vous et collaborations d'une conversation
 * (CO-01, CO-03, MR-07, FC-06).
 */
class CollaborationController extends Controller
{
    public function proposeAgreement(ProposeAgreementRequest $request, ContactRequest $contactRequest, AgreementService $agreements): RedirectResponse
    {
        Gate::authorize('message', $contactRequest);

        $agreements->propose(
            $contactRequest,
            $request->user(),
            $request->validated('objective'),
            Carbon::parse($request->validated('due_on')),
            User::findOrFail($request->validated('owner_id')),
        );

        return $this->back($contactRequest, 'Prochaine étape proposée. Elle compte dès que l\'autre personne l\'accepte.');
    }

    public function respondAgreement(DecisionRequest $request, Agreement $agreement, AgreementService $agreements): RedirectResponse
    {
        Gate::authorize('respond', $agreement);

        $agreements->respond($agreement, $request->user(), $request->boolean('accepted'));

        return $this->back($agreement->contactRequest, $request->boolean('accepted')
            ? 'Prochaine étape acceptée.'.($agreement->project_id ? ' Elle figure dans le tableau de tâches du projet.' : '')
            : 'Proposition refusée.');
    }

    public function completeAgreement(Agreement $agreement, AgreementService $agreements): RedirectResponse
    {
        Gate::authorize('complete', $agreement);

        $agreements->complete($agreement);

        return $this->back($agreement->contactRequest, 'Étape marquée comme réalisée.');
    }

    public function proposeMeeting(ProposeMeetingRequest $request, ContactRequest $contactRequest, MeetingService $meetings): RedirectResponse
    {
        Gate::authorize('message', $contactRequest);

        $meetings->propose(
            $contactRequest,
            $request->user(),
            Carbon::parse($request->validated('starts_at')),
            MeetingMode::from($request->validated('mode')),
            $request->validated('place'),
            $request->validated('agenda'),
        );

        return $this->back($contactRequest, 'Rendez-vous proposé. Vous serez prévenu de la réponse.');
    }

    public function respondMeeting(DecisionRequest $request, Meeting $meeting, MeetingService $meetings): RedirectResponse
    {
        Gate::authorize('respond', $meeting);

        $meetings->respond($meeting, $request->user(), $request->boolean('accepted'));

        return $this->back($meeting->contactRequest, $request->boolean('accepted')
            ? 'Rendez-vous confirmé. Un rappel vous sera envoyé la veille.'
            : 'Vous avez décliné ce rendez-vous. Proposez une autre date si besoin.');
    }

    public function cancelMeeting(Request $request, Meeting $meeting, MeetingService $meetings): RedirectResponse
    {
        Gate::authorize('cancel', $meeting);

        $meetings->cancel($meeting, $request->user());

        return $this->back($meeting->contactRequest, 'Rendez-vous annulé.');
    }

    public function declare(DeclareCollaborationRequest $request, ContactRequest $contactRequest, CollaborationService $collaborations): RedirectResponse
    {
        Gate::authorize('message', $contactRequest);

        $collaborations->declare($contactRequest, $request->user(), $request->validated('summary'));

        return $this->back($contactRequest, 'Collaboration déclarée. Elle sera publiée quand l\'autre personne l\'aura confirmée.');
    }

    public function respondCollaboration(DecisionRequest $request, Collaboration $collaboration, CollaborationService $collaborations): RedirectResponse
    {
        Gate::authorize('respond', $collaboration);

        $collaborations->respond($collaboration, $request->boolean('accepted'));

        return $this->back($collaboration->contactRequest, $request->boolean('accepted')
            ? 'Collaboration confirmée. Elle apparaît sur vos deux profils.'
            : 'Collaboration non confirmée : elle ne sera pas publiée.');
    }

    private function back(ContactRequest $conversation, string $status): RedirectResponse
    {
        return redirect()->to(route('contact-requests.show', $conversation).'#collaboration')->with('status', $status);
    }
}
