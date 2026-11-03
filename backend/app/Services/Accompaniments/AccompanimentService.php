<?php

namespace App\Services\Accompaniments;

use App\Enums\AccompanimentKind;
use App\Enums\AccompanimentStatus;
use App\Models\Accompaniment;
use App\Models\AccompanimentSession;
use App\Models\MemberProfile;
use App\Models\Project;
use App\Models\User;
use App\Notifications\AccompanimentNotice;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Accompagnement bénévole ou de mentorat (AC-01 à AC-04). Une prestation payante
 * passe par un devis (SE-02) ; ici, aucun argent n'est en jeu.
 */
class AccompanimentService
{
    /**
     * Demande d'accompagnement adressée au profil d'un coach ou d'un mentor.
     *
     * @throws ValidationException
     */
    public function request(User $requester, MemberProfile $profile, AccompanimentKind $kind, string $objectives, ?Project $project = null): Accompaniment
    {
        $coach = $profile->user;

        if ($requester->is($coach)) {
            throw ValidationException::withMessages(['objectives' => 'Vous ne pouvez pas vous demander un accompagnement.']);
        }

        // AC-01 : le profil doit proposer cette forme d'accompagnement et être visible.
        $offered = $kind === AccompanimentKind::Mentorat ? $profile->offers_mentoring : $profile->offers_volunteer;
        if (! $offered || ! $profile->isPublic()) {
            throw ValidationException::withMessages(['kind' => 'Ce profil ne propose pas cette forme d\'accompagnement.']);
        }

        if (mb_strlen(trim($objectives)) < config('idee.accompaniments.objectives_min_length')) {
            throw ValidationException::withMessages(['objectives' => 'Décrivez ce que vous attendez en '.config('idee.accompaniments.objectives_min_length').' caractères au moins : votre situation, vos objectifs.']);
        }

        if ($project !== null && ! $project->isOwnedBy($requester)) {
            throw ValidationException::withMessages(['project_id' => 'Choisissez l\'un de vos projets.']);
        }

        $pending = Accompaniment::where('requester_id', $requester->id)->where('coach_id', $coach->id)
            ->whereIn('status', [AccompanimentStatus::Demandee, AccompanimentStatus::EnCours])->exists();
        if ($pending) {
            throw ValidationException::withMessages(['objectives' => 'Vous avez déjà une demande ou un accompagnement en cours avec cette personne.']);
        }

        $accompaniment = new Accompaniment;
        $accompaniment->forceFill([
            'requester_id' => $requester->id,
            'coach_id' => $coach->id,
            'member_profile_id' => $profile->id,
            'project_id' => $project?->id,
            'kind' => $kind,
            'status' => AccompanimentStatus::Demandee,
            'objectives' => trim($objectives),
        ])->save();

        $coach->notify(new AccompanimentNotice($accompaniment, $requester->name.' vous demande un accompagnement ('.mb_strtolower($kind->label()).').'));

        return $accompaniment;
    }

    /**
     * Le coach accepte, dans la limite des accompagnements de mentorat en cours (AC-02).
     *
     * @throws ValidationException
     */
    public function accept(Accompaniment $accompaniment): Accompaniment
    {
        $this->ensureStatus($accompaniment, AccompanimentStatus::Demandee);

        $max = config('idee.accompaniments.max_active_per_coach');
        $active = Accompaniment::where('coach_id', $accompaniment->coach_id)
            ->where('kind', AccompanimentKind::Mentorat)
            ->where('status', AccompanimentStatus::EnCours)->count();

        if ($accompaniment->kind === AccompanimentKind::Mentorat && $active >= $max) {
            throw ValidationException::withMessages(['status' => "Vous accompagnez déjà {$active} personne(s) en mentorat, le maximum. Terminez un accompagnement avant d'en accepter un autre."]);
        }

        $accompaniment->forceFill(['status' => AccompanimentStatus::EnCours, 'started_at' => now()])->save();
        $accompaniment->requester->notify(new AccompanimentNotice($accompaniment, $accompaniment->coach->name.' accepte de vous accompagner.'));

        return $accompaniment;
    }

    /**
     * Le coach refuse, avec un motif (comme toute demande sur IDEE, RG-03).
     *
     * @throws ValidationException
     */
    public function decline(Accompaniment $accompaniment, string $reason): Accompaniment
    {
        $this->ensureStatus($accompaniment, AccompanimentStatus::Demandee);

        $accompaniment->forceFill(['status' => AccompanimentStatus::Refusee, 'decline_reason' => trim($reason), 'ended_at' => now()])->save();
        $accompaniment->requester->notify(new AccompanimentNotice($accompaniment, $accompaniment->coach->name.' ne peut pas vous accompagner pour le moment.'));

        return $accompaniment;
    }

    /**
     * La personne accompagnée retire sa demande avant la réponse.
     *
     * @throws ValidationException
     */
    public function cancel(Accompaniment $accompaniment): Accompaniment
    {
        $this->ensureStatus($accompaniment, AccompanimentStatus::Demandee);
        $accompaniment->forceFill(['status' => AccompanimentStatus::Annulee, 'ended_at' => now()])->save();

        return $accompaniment;
    }

    /**
     * AC-03 : point de suivi daté, écrit par l'un ou l'autre.
     *
     * @throws ValidationException
     */
    public function addSession(Accompaniment $accompaniment, User $author, Carbon $heldOn, string $summary, ?string $nextSteps = null): AccompanimentSession
    {
        $this->ensureStatus($accompaniment, AccompanimentStatus::EnCours);

        if ($heldOn->isAfter(today())) {
            throw ValidationException::withMessages(['held_on' => 'Un point de suivi se note une fois la séance passée.']);
        }

        $session = new AccompanimentSession;
        $session->forceFill([
            'accompaniment_id' => $accompaniment->id,
            'author_id' => $author->id,
            'held_on' => $heldOn,
            'summary' => trim($summary),
            'next_steps' => filled($nextSteps) ? trim($nextSteps) : null,
        ])->save();

        $other = $author->id === $accompaniment->coach_id ? $accompaniment->requester : $accompaniment->coach;
        $other->notify(new AccompanimentNotice($accompaniment, $author->name.' a noté un point de suivi.'));

        return $session;
    }

    /**
     * AC-04 : l'un ou l'autre met fin à l'accompagnement ; l'avis devient possible.
     *
     * @throws ValidationException
     */
    public function complete(Accompaniment $accompaniment, User $actor): Accompaniment
    {
        $this->ensureStatus($accompaniment, AccompanimentStatus::EnCours);

        $accompaniment->forceFill(['status' => AccompanimentStatus::Terminee, 'ended_at' => now()])->save();
        $accompaniment->requester->notify(new AccompanimentNotice($accompaniment, 'Votre accompagnement avec '.$accompaniment->coach->name.' est terminé. Laissez un avis pour aider les autres membres.'));

        if ($actor->id === $accompaniment->requester_id) {
            $accompaniment->coach->notify(new AccompanimentNotice($accompaniment, $accompaniment->requester->name.' a mis fin à l\'accompagnement.'));
        }

        return $accompaniment;
    }

    /**
     * @throws ValidationException
     */
    private function ensureStatus(Accompaniment $accompaniment, AccompanimentStatus $expected): void
    {
        if ($accompaniment->status !== $expected) {
            throw ValidationException::withMessages(['status' => 'Cet accompagnement est « '.$accompaniment->status->label().' » : cette action n\'est plus possible.']);
        }
    }
}
