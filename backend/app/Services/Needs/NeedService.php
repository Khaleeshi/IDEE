<?php

namespace App\Services\Needs;

use App\Enums\ActivityType;
use App\Enums\HelpOfferKind;
use App\Enums\HelpOfferStatus;
use App\Enums\NeedStatus;
use App\Models\HelpOffer;
use App\Models\Need;
use App\Models\Project;
use App\Models\User;
use App\Services\Feed\FeedService;
use App\Services\Projects\ReadinessScore;
use Illuminate\Validation\ValidationException;

class NeedService
{
    public function __construct(
        private readonly ReadinessScore $readiness,
        private readonly FeedService $feed,
    ) {}

    /**
     * Publie un appel à action, dans la limite de besoins ouverts (RG-01).
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    public function create(Project $project, array $attributes): Need
    {
        $limit = config('idee.max_open_needs_per_project');

        if ($project->openNeeds()->count() >= $limit) {
            throw ValidationException::withMessages([
                'need' => "Un projet peut avoir au plus {$limit} besoins ouverts. Clôturez un besoin avant d'en publier un autre.",
            ]);
        }

        $need = $project->needs()->create($attributes);
        $need->status = NeedStatus::Ouvert;
        $need->save();

        $project->unsetRelation('openNeeds');
        $project->recordActivity();
        $this->readiness->refresh($project);
        $this->feed->record(ActivityType::BesoinPublie, $project, $project->owner, $need);

        return $need;
    }

    /**
     * Clôture un besoin avec son résultat (AA-04).
     */
    public function close(Need $need, NeedStatus $status, ?string $outcome = null): Need
    {
        if (! $status->isClosed()) {
            throw ValidationException::withMessages([
                'status' => 'Un besoin se clôture comme « comblé » ou « abandonné ».',
            ]);
        }

        $need->forceFill([
            'status' => $status,
            'outcome' => $outcome,
            'closed_at' => now(),
        ])->save();

        $need->project->recordActivity();
        $this->readiness->refresh($need->project);

        // Un besoin comblé est une réussite partagée avec le réseau.
        if ($status === NeedStatus::Comble) {
            $this->feed->record(ActivityType::Reussite, $need->project, $need->project->owner, $need);
        }

        return $need;
    }

    /**
     * Répond « Je peux aider » (AA-03) ou « Je connais quelqu'un » (FC-03) à un besoin.
     *
     * @throws ValidationException
     */
    public function offerHelp(Need $need, User $helper, string $message, HelpOfferKind $kind = HelpOfferKind::Aide): HelpOffer
    {
        if (! $need->isOpen()) {
            throw ValidationException::withMessages(['need' => 'Ce besoin est clôturé.']);
        }

        if ($need->project->isOwnedBy($helper)) {
            throw ValidationException::withMessages(['need' => 'Vous ne pouvez pas répondre à votre propre besoin.']);
        }

        if ($need->helpOffers()->where('user_id', $helper->getKey())->where('kind', $kind)->exists()) {
            throw ValidationException::withMessages(['need' => $kind === HelpOfferKind::Aide
                ? 'Vous avez déjà proposé votre aide pour ce besoin.'
                : 'Vous avez déjà signalé une personne pour ce besoin.']);
        }

        $offer = new HelpOffer(['message' => $message]);
        $offer->kind = $kind;
        $offer->need()->associate($need);
        $offer->user()->associate($helper);
        $offer->status = HelpOfferStatus::Proposee;
        $offer->save();

        return $offer;
    }

    public function respondToOffer(HelpOffer $offer, bool $accepted): HelpOffer
    {
        if ($offer->status !== HelpOfferStatus::Proposee) {
            throw ValidationException::withMessages(['offer' => 'Vous avez déjà répondu à cette proposition.']);
        }

        $offer->forceFill([
            'status' => $accepted ? HelpOfferStatus::Acceptee : HelpOfferStatus::Declinee,
            'responded_at' => now(),
        ])->save();

        return $offer;
    }
}
