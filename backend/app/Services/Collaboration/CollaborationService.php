<?php

namespace App\Services\Collaboration;

use App\Enums\ActivityType;
use App\Enums\CollaborationStatus;
use App\Models\Collaboration;
use App\Models\ContactRequest;
use App\Models\User;
use App\Notifications\CollaborationNotice;
use App\Services\Feed\FeedService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Historique des collaborations confirmées (CO-03) et réussites validées
 * par les deux parties (FC-06) : rien n'est publié sans l'accord de l'autre.
 */
class CollaborationService
{
    public function __construct(private readonly FeedService $feed) {}

    /**
     * @throws ValidationException
     */
    public function declare(ContactRequest $conversation, User $declarer, string $summary): Collaboration
    {
        if (! $conversation->involves($declarer) || ! $conversation->allowsMessages()) {
            throw ValidationException::withMessages([
                'summary' => 'Une collaboration se déclare quand les deux personnes se sont dites intéressées.',
            ]);
        }

        if ($conversation->collaborations()->where('status', CollaborationStatus::EnAttente)->exists()) {
            throw ValidationException::withMessages(['summary' => 'Une collaboration attend déjà une confirmation dans cette conversation.']);
        }

        $partner = $conversation->otherParty($declarer);

        $collaboration = new Collaboration(['summary' => trim($summary)]);
        $collaboration->contactRequest()->associate($conversation);
        $collaboration->project()->associate($conversation->project);
        $collaboration->declarer()->associate($declarer);
        $collaboration->partner()->associate($partner);
        $collaboration->status = CollaborationStatus::EnAttente;
        $collaboration->save();

        $partner?->notify(new CollaborationNotice(
            $conversation,
            $declarer->name.' vous demande de confirmer votre collaboration : « '.$collaboration->summary.' ».',
        ));

        return $collaboration;
    }

    /**
     * Seul le partenaire confirme. Une collaboration confirmée sur un projet
     * apparaît dans le fil comme une réussite.
     *
     * @throws ValidationException
     */
    public function respond(Collaboration $collaboration, bool $confirmed): Collaboration
    {
        if ($collaboration->status !== CollaborationStatus::EnAttente) {
            throw ValidationException::withMessages(['collaboration' => 'Cette collaboration a déjà reçu une réponse.']);
        }

        $collaboration->status = $confirmed ? CollaborationStatus::Confirmee : CollaborationStatus::Refusee;
        $collaboration->confirmed_at = $confirmed ? now() : null;
        $collaboration->save();

        if ($confirmed && $collaboration->project !== null) {
            $this->feed->record(ActivityType::Reussite, $collaboration->project, $collaboration->declarer, collaboration: $collaboration);
        }

        $collaboration->declarer->notify(new CollaborationNotice(
            $collaboration->contactRequest,
            $collaboration->partner->name.($confirmed ? ' a confirmé' : ' n\'a pas confirmé').' votre collaboration « '.$collaboration->summary.' ».',
        ));

        return $collaboration;
    }

    /**
     * Collaborations confirmées d'un membre, les plus récentes d'abord.
     *
     * @return Collection<int, Collaboration>
     */
    public function confirmedFor(User $user, int $limit = 20): Collection
    {
        return Collaboration::query()
            ->confirmedFor($user)
            ->with(['declarer', 'partner', 'project'])
            ->latest('confirmed_at')
            ->limit($limit)
            ->get();
    }
}
