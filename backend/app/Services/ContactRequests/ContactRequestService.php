<?php

namespace App\Services\ContactRequests;

use App\Enums\ContactRequestStatus;
use App\Models\ContactRequest;
use App\Models\Need;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ContactRequestReceived;
use App\Notifications\ContactRequestReminder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Mise en relation encadrée par la règle « aucune demande sans réponse »
 * (MR-01 à MR-06, RG-02 à RG-04).
 */
class ContactRequestService
{
    /**
     * @throws ValidationException
     */
    public function send(User $sender, User $recipient, string $message, ?Project $project = null, ?Need $need = null): ContactRequest
    {
        if ($need !== null) {
            if (! $need->isOpen()) {
                throw ValidationException::withMessages(['need_id' => 'Ce besoin est clôturé.']);
            }

            $project = $need->project;
        }

        if ($project === null) {
            throw ValidationException::withMessages([
                'project_id' => 'Une demande de contact doit viser un projet ou un besoin précis.',
            ]);
        }

        if ($sender->is($recipient)) {
            throw ValidationException::withMessages(['recipient_id' => 'Vous ne pouvez pas vous envoyer une demande.']);
        }

        if (($recipient->contact_preferences['accepts_requests'] ?? true) === false) {
            throw ValidationException::withMessages(['recipient_id' => 'Ce membre ne reçoit pas de demandes pour le moment.']);
        }

        $minimum = config('idee.contact_message_min_length');

        if (mb_strlen(trim($message)) < $minimum) {
            throw ValidationException::withMessages([
                'message' => "Présentez votre demande en au moins {$minimum} caractères.",
            ]);
        }

        $quota = $sender->weeklyContactQuota();
        $sentThisWeek = $sender->sentContactRequests()->where('created_at', '>=', now()->subWeek())->count();

        if ($sentThisWeek >= $quota) {
            throw ValidationException::withMessages([
                'quota' => "Vous avez atteint votre limite de {$quota} demandes par semaine.",
            ]);
        }

        $duplicate = $sender->sentContactRequests()
            ->where('recipient_id', $recipient->getKey())
            ->where('project_id', $project->getKey())
            ->whereIn('status', [ContactRequestStatus::Envoyee, ContactRequestStatus::Lue])
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'recipient_id' => 'Une demande pour ce projet attend déjà la réponse de ce membre.',
            ]);
        }

        return DB::transaction(function () use ($sender, $recipient, $message, $project, $need) {
            $request = new ContactRequest([
                'project_id' => $project->getKey(),
                'need_id' => $need?->getKey(),
                'message' => trim($message),
                'expires_at' => now()->addDays(config('idee.contact_expire_after_days')),
            ]);
            $request->sender()->associate($sender);
            $request->recipient()->associate($recipient);
            $request->status = ContactRequestStatus::Envoyee;
            $request->save();

            $request->events()->create([
                'actor_id' => $sender->getKey(),
                'from_status' => null,
                'to_status' => ContactRequestStatus::Envoyee,
            ]);

            $recipient->notify(new ContactRequestReceived($request));

            return $request;
        });
    }

    /**
     * Le destinataire a ouvert la demande.
     */
    public function markAsRead(ContactRequest $request, User $reader): void
    {
        if ($request->recipient_id !== $reader->getKey() || $request->status !== ContactRequestStatus::Envoyee) {
            return;
        }

        $request->read_at = now();
        $request->transitionTo(ContactRequestStatus::Lue, $reader);
    }

    /**
     * @throws ValidationException
     */
    public function respond(ContactRequest $request, User $recipient, ContactRequestStatus $answer, ?string $reason = null): ContactRequest
    {
        if (! in_array($answer, ContactRequestStatus::responses(), true)) {
            throw ValidationException::withMessages(['status' => 'Réponse non reconnue.']);
        }

        if (! $request->status->isPending()) {
            throw ValidationException::withMessages(['status' => 'Cette demande n\'attend plus de réponse.']);
        }

        if ($answer === ContactRequestStatus::Refusee && blank($reason)) {
            throw ValidationException::withMessages([
                'refusal_reason' => 'Indiquez le motif du refus : il aide le porteur à améliorer sa demande.',
            ]);
        }

        $request->read_at ??= now();
        $request->responded_at = now();
        $request->refusal_reason = $answer === ContactRequestStatus::Refusee ? $reason : null;
        $request->transitionTo($answer, $recipient);

        return $request;
    }

    /**
     * Relance à 7 jours puis expiration à 14 jours (RG-03).
     *
     * @return array{reminded: int, expired: int}
     */
    public function processDeadlines(): array
    {
        $pending = [ContactRequestStatus::Envoyee, ContactRequestStatus::Lue];
        $expired = 0;
        $reminded = 0;

        ContactRequest::query()
            ->whereIn('status', $pending)
            ->where('expires_at', '<=', now())
            ->each(function (ContactRequest $request) use (&$expired) {
                $request->transitionTo(ContactRequestStatus::Expiree);
                $expired++;
            });

        ContactRequest::query()
            ->with('recipient')
            ->whereIn('status', $pending)
            ->whereNull('reminded_at')
            ->where('created_at', '<=', now()->subDays(config('idee.contact_reminder_after_days')))
            ->each(function (ContactRequest $request) use (&$reminded) {
                $request->forceFill(['reminded_at' => now()])->save();
                $request->recipient->notify(new ContactRequestReminder($request));
                $reminded++;
            });

        return ['reminded' => $reminded, 'expired' => $expired];
    }
}
