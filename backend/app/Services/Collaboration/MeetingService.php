<?php

namespace App\Services\Collaboration;

use App\Enums\MeetingMode;
use App\Enums\MeetingStatus;
use App\Models\ContactRequest;
use App\Models\Meeting;
use App\Models\User;
use App\Notifications\CollaborationNotice;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Prise de rendez-vous en visio ou en présentiel (MR-07) : proposé par
 * l'un, confirmé par l'autre, rappelé aux deux la veille.
 */
class MeetingService
{
    /**
     * @throws ValidationException
     */
    public function propose(ContactRequest $conversation, User $proposer, Carbon $startsAt, MeetingMode $mode, ?string $place, ?string $agenda): Meeting
    {
        if (! $conversation->involves($proposer) || ! $conversation->allowsMessages()) {
            throw ValidationException::withMessages([
                'conversation' => 'Un rendez-vous se propose quand les deux personnes se sont dites intéressées.',
            ]);
        }

        if ($startsAt->isPast()) {
            throw ValidationException::withMessages(['starts_at' => 'Choisissez une date à venir.']);
        }

        $meeting = new Meeting([
            'starts_at' => $startsAt,
            'mode' => $mode,
            'place' => filled($place) ? trim($place) : null,
            'agenda' => filled($agenda) ? trim($agenda) : null,
        ]);
        $meeting->contactRequest()->associate($conversation);
        $meeting->proposer()->associate($proposer);
        $meeting->status = MeetingStatus::Proposee;
        $meeting->save();

        $conversation->otherParty($proposer)?->notify(new CollaborationNotice(
            $conversation,
            $proposer->name.' propose un rendez-vous '.mb_strtolower($mode->label()).' le '.$this->when($meeting).'.',
        ));

        return $meeting;
    }

    /**
     * @throws ValidationException
     */
    public function respond(Meeting $meeting, User $user, bool $confirmed): Meeting
    {
        if ($meeting->status !== MeetingStatus::Proposee) {
            throw ValidationException::withMessages(['meeting' => 'Ce rendez-vous a déjà reçu une réponse.']);
        }

        $meeting->status = $confirmed ? MeetingStatus::Confirmee : MeetingStatus::Refusee;
        $meeting->responded_at = now();
        $meeting->save();

        $meeting->proposer->notify(new CollaborationNotice(
            $meeting->contactRequest,
            $user->name.($confirmed ? ' a confirmé' : ' ne peut pas venir à').' le rendez-vous du '.$this->when($meeting).'.',
        ));

        return $meeting;
    }

    /**
     * @throws ValidationException
     */
    public function cancel(Meeting $meeting, User $user): Meeting
    {
        if (! in_array($meeting->status, [MeetingStatus::Proposee, MeetingStatus::Confirmee], true) || $meeting->starts_at->isPast()) {
            throw ValidationException::withMessages(['meeting' => 'Ce rendez-vous ne peut plus être annulé.']);
        }

        $meeting->status = MeetingStatus::Annulee;
        $meeting->save();

        $meeting->contactRequest->otherParty($user)?->notify(new CollaborationNotice(
            $meeting->contactRequest,
            $user->name.' a annulé le rendez-vous du '.$this->when($meeting).'.',
        ));

        return $meeting;
    }

    /**
     * Rappelle aux deux personnes les rendez-vous confirmés qui approchent.
     */
    public function sendReminders(): int
    {
        $meetings = Meeting::query()
            ->where('status', MeetingStatus::Confirmee)
            ->whereNull('reminded_at')
            ->whereBetween('starts_at', [now(), now()->addHours(config('idee.collaboration.meeting_reminder_hours'))])
            ->with('contactRequest.sender', 'contactRequest.recipient')
            ->get();

        foreach ($meetings as $meeting) {
            $conversation = $meeting->contactRequest;

            foreach ([$conversation->sender, $conversation->recipient] as $person) {
                $person?->notify(new CollaborationNotice(
                    $conversation,
                    'Rappel : rendez-vous avec '.$conversation->otherParty($person)?->name.' le '.$this->when($meeting).'.',
                ));
            }

            $meeting->forceFill(['reminded_at' => now()])->save();
        }

        return $meetings->count();
    }

    private function when(Meeting $meeting): string
    {
        return $meeting->starts_at->translatedFormat('d F à H\hi');
    }
}
