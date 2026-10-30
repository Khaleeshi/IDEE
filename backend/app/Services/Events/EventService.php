<?php

namespace App\Services\Events;

use App\Enums\OrganisationRole;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\EventPairing;
use App\Models\EventRegistration;
use App\Models\Organisation;
use App\Models\Project;
use App\Models\User;
use App\Notifications\EventNotice;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Sessions de pitch en ligne et en présentiel (EV-01), organisées par
 * l'équipe IDEE ou par une organisation vérifiée.
 */
class EventService
{
    /**
     * Organisations vérifiées qu'un membre gère et au nom desquelles il peut organiser.
     *
     * @return Collection<int, Organisation>
     */
    public function organisationsFor(User $user): Collection
    {
        return Organisation::query()
            ->where('verification_status', 'verifiee')
            ->whereHas('members', fn ($query) => $query->where('user_id', $user->id)->where('role', OrganisationRole::Administrateur))
            ->orderBy('name')
            ->get();
    }

    public function canOrganise(User $user): bool
    {
        return $user->isAdmin() || $this->organisationsFor($user)->isNotEmpty();
    }

    public function canManage(?User $user, Event $event): bool
    {
        if ($user === null) {
            return false;
        }

        return $event->created_by === $user->id
            || $user->isAdmin()
            || ($event->organisation !== null && $event->organisation->isManagedBy($user));
    }

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    public function create(User $organiser, array $attributes, ?Organisation $organisation = null): Event
    {
        if ($organisation !== null && ! $this->organisationsFor($organiser)->contains('id', $organisation->id)) {
            throw ValidationException::withMessages(['organisation_id' => 'Vous organisez au nom d\'une organisation vérifiée que vous gérez.']);
        }

        if ($organisation === null && ! $organiser->isAdmin()) {
            throw ValidationException::withMessages(['organisation_id' => 'Choisissez l\'organisation qui organise la session.']);
        }

        $event = new Event($attributes);
        $event->organisation()->associate($organisation);
        $event->creator()->associate($organiser);
        $event->status = 'publie';
        $event->save();

        return $event;
    }

    /**
     * S'inscrire pour écouter, ou candidater pour présenter un de ses projets.
     *
     * @throws ValidationException
     */
    public function register(Event $event, User $user, ?Project $project = null, ?string $side = null): EventRegistration
    {
        if ($event->isSpeedMeeting() && ! in_array($side, ['porteur', 'partenaire'], true)) {
            throw ValidationException::withMessages(['side' => 'Indiquez si vous venez comme porteur de projet ou comme partenaire.']);
        }

        if ($event->isCancelled() || $event->starts_at->isPast()) {
            throw ValidationException::withMessages(['event' => 'Les inscriptions à cette session sont closes.']);
        }

        if ($event->registrations()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages(['event' => 'Vous êtes déjà inscrit à cette session.']);
        }

        if ($project !== null) {
            if (! $project->isOwnedBy($user)) {
                throw ValidationException::withMessages(['project_id' => 'Vous présentez l\'un de vos propres projets.']);
            }

            if (! $event->acceptsPitches()) {
                throw ValidationException::withMessages(['project_id' => 'Les candidatures pour présenter un projet sont closes.']);
            }
        }

        $registration = new EventRegistration;
        $registration->event()->associate($event);
        $registration->user()->associate($user);
        $registration->project()->associate($project);
        $registration->kind = $project && ! $event->isSpeedMeeting() ? 'pitch' : 'auditeur';
        $registration->side = $event->isSpeedMeeting() ? $side : null;
        $registration->status = $project && ! $event->isSpeedMeeting() ? RegistrationStatus::Candidat : RegistrationStatus::Inscrit;
        $registration->save();

        return $registration;
    }

    /**
     * Forme les binômes porteur / partenaire, tour par tour, sans se revoir (EV-02).
     *
     * @return Collection<int, EventPairing>
     *
     * @throws ValidationException
     */
    public function pair(Event $event): Collection
    {
        if (! $event->isSpeedMeeting()) {
            throw ValidationException::withMessages(['event' => 'Les binômes ne concernent que les rencontres ciblées.']);
        }

        $registrations = $event->registrations()->where('status', RegistrationStatus::Inscrit)->oldest()->get();
        $founders = $registrations->where('side', 'porteur')->pluck('user_id')->values();
        $partners = $registrations->where('side', 'partenaire')->pluck('user_id')->values();

        if ($founders->isEmpty() || $partners->isEmpty()) {
            throw ValidationException::withMessages(['event' => 'Il faut au moins un porteur et un partenaire inscrits.']);
        }

        [$small, $large] = $founders->count() <= $partners->count() ? [$founders, $partners] : [$partners, $founders];
        $rounds = min(config('idee.speed_meeting.max_rounds'), $large->count());

        $event->pairings()->delete();

        for ($round = 0; $round < $rounds; $round++) {
            foreach ($small as $index => $userId) {
                $pairing = new EventPairing;
                $pairing->forceFill([
                    'event_id' => $event->id,
                    'round' => $round + 1,
                    'starts_at' => $event->starts_at->copy()->addMinutes($round * config('idee.speed_meeting.minutes')),
                    'first_user_id' => $userId,
                    'second_user_id' => $large[($index + $round) % $large->count()],
                    'room_url' => rtrim(config('idee.speed_meeting.video_base'), '/').'/IDEE-'.Str::random(16),
                ])->save();
            }
        }

        $registrations->each(fn (EventRegistration $registration) => $registration->user->notify(new EventNotice(
            $event,
            'Vos rencontres pour « '.$event->title.' » sont prêtes : horaires et liens sur la page de l\'événement.',
        )));

        return $event->pairings()->get();
    }

    public function unregister(Event $event, User $user): void
    {
        $event->registrations()->where('user_id', $user->id)->delete();
    }

    /**
     * L'organisateur retient ou non un projet candidat, dans la limite des places.
     *
     * @throws ValidationException
     */
    public function decide(EventRegistration $registration, bool $selected): EventRegistration
    {
        $event = $registration->event;

        if (! in_array($registration->status, [RegistrationStatus::Candidat, RegistrationStatus::Retenu, RegistrationStatus::NonRetenu], true)) {
            throw ValidationException::withMessages(['registration' => 'Cette inscription n\'est pas une candidature.']);
        }

        if ($selected && $registration->status !== RegistrationStatus::Retenu && $event->selectedCount() >= $event->pitch_slots) {
            throw ValidationException::withMessages(['registration' => 'Les '.$event->pitch_slots.' places de présentation sont déjà attribuées.']);
        }

        $registration->status = $selected ? RegistrationStatus::Retenu : RegistrationStatus::NonRetenu;
        $registration->save();

        $registration->user->notify(new EventNotice($event, $selected
            ? 'Votre projet « '.$registration->project?->title.' » est retenu pour présenter à « '.$event->title.' » le '.$event->starts_at->translatedFormat('d F à H\hi').'.'
            : 'Votre projet n\'a pas été retenu pour présenter à « '.$event->title.' ». Vous pouvez toujours y assister.'));

        return $registration;
    }

    public function cancel(Event $event): Event
    {
        $event->forceFill(['status' => 'annule'])->save();

        $event->registrations()->with('user')->get()->each(fn (EventRegistration $registration) => $registration->user->notify(
            new EventNotice($event, 'La session « '.$event->title.' » du '.$event->starts_at->translatedFormat('d F').' est annulée.'),
        ));

        return $event;
    }

    /**
     * Rappelle la session aux inscrits et aux projets retenus, la veille.
     */
    public function sendReminders(): int
    {
        $registrations = EventRegistration::query()
            ->whereIn('status', [RegistrationStatus::Inscrit, RegistrationStatus::Retenu])
            ->whereNull('reminded_at')
            ->whereHas('event', fn ($query) => $query
                ->where('status', 'publie')
                ->whereBetween('starts_at', [now(), now()->addHours(config('idee.collaboration.meeting_reminder_hours'))]))
            ->with(['event', 'user'])
            ->get();

        foreach ($registrations as $registration) {
            $registration->user->notify(new EventNotice(
                $registration->event,
                'Rappel : « '.$registration->event->title.' » le '.$registration->event->starts_at->translatedFormat('d F à H\hi').'.',
            ));
            $registration->forceFill(['reminded_at' => now()])->save();
        }

        return $registrations->count();
    }
}
