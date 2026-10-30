<?php

namespace App\Services\Collaboration;

use App\Enums\AgreementStatus;
use App\Enums\TaskStatus;
use App\Models\Agreement;
use App\Models\ContactRequest;
use App\Models\ProjectTask;
use App\Models\User;
use App\Notifications\CollaborationNotice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Accord de prochaine étape (CO-01) : l'un propose un objectif, une échéance
 * et un responsable ; l'autre accepte ou refuse. Un accord accepté sur un
 * projet rejoint son tableau de tâches (CO-02).
 */
class AgreementService
{
    /**
     * @throws ValidationException
     */
    public function propose(ContactRequest $conversation, User $proposer, string $objective, Carbon $dueOn, User $owner): Agreement
    {
        $this->ensureOpen($conversation, $proposer);

        if (! $conversation->involves($owner)) {
            throw ValidationException::withMessages(['owner_id' => 'Le responsable doit être l\'une des deux personnes de la conversation.']);
        }

        if ($dueOn->isPast() && ! $dueOn->isToday()) {
            throw ValidationException::withMessages(['due_on' => 'L\'échéance ne peut pas être dans le passé.']);
        }

        $agreement = new Agreement(['objective' => trim($objective), 'due_on' => $dueOn]);
        $agreement->contactRequest()->associate($conversation);
        $agreement->project()->associate($conversation->project);
        $agreement->proposer()->associate($proposer);
        $agreement->owner()->associate($owner);
        $agreement->status = AgreementStatus::Proposee;
        $agreement->save();

        $conversation->otherParty($proposer)?->notify(new CollaborationNotice(
            $conversation,
            $proposer->name.' propose une prochaine étape : « '.$agreement->objective.' », avant le '.$dueOn->translatedFormat('d F').'.',
        ));

        return $agreement;
    }

    /**
     * Réponse de l'autre personne : un accord ne lie que si les deux l'acceptent.
     *
     * @throws ValidationException
     */
    public function respond(Agreement $agreement, User $user, bool $accepted): Agreement
    {
        if ($agreement->status !== AgreementStatus::Proposee) {
            throw ValidationException::withMessages(['agreement' => 'Cette proposition a déjà reçu une réponse.']);
        }

        DB::transaction(function () use ($agreement, $accepted) {
            $agreement->status = $accepted ? AgreementStatus::Acceptee : AgreementStatus::Refusee;
            $agreement->responded_at = now();
            $agreement->save();

            if ($accepted && $agreement->project_id !== null) {
                $task = new ProjectTask(['title' => $agreement->objective, 'due_on' => $agreement->due_on]);
                $task->project()->associate($agreement->project_id);
                $task->agreement()->associate($agreement);
                $task->author()->associate($agreement->proposer_id);
                $task->assignee()->associate($agreement->owner_id);
                $task->status = TaskStatus::AFaire;
                $task->save();
            }
        });

        $agreement->proposer->notify(new CollaborationNotice(
            $agreement->contactRequest,
            $user->name.($accepted ? ' a accepté' : ' n\'a pas accepté').' la prochaine étape « '.$agreement->objective.' ».',
        ));

        return $agreement;
    }

    /**
     * Marque l'étape comme réalisée, ainsi que la tâche liée.
     *
     * @throws ValidationException
     */
    public function complete(Agreement $agreement): Agreement
    {
        if ($agreement->status !== AgreementStatus::Acceptee) {
            throw ValidationException::withMessages(['agreement' => 'Seule une étape acceptée peut être marquée comme réalisée.']);
        }

        $agreement->status = AgreementStatus::Realisee;
        $agreement->done_at = now();
        $agreement->save();

        $agreement->task?->forceFill(['status' => TaskStatus::Fait, 'done_at' => now()])->save();

        return $agreement;
    }

    /**
     * @throws ValidationException
     */
    private function ensureOpen(ContactRequest $conversation, User $user): void
    {
        if (! $conversation->involves($user) || ! $conversation->allowsMessages()) {
            throw ValidationException::withMessages([
                'conversation' => 'Cette action s\'ouvre quand les deux personnes se sont dites intéressées.',
            ]);
        }
    }
}
