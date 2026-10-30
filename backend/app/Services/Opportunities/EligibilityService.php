<?php

namespace App\Services\Opportunities;

use App\Enums\NeedType;
use App\Enums\ProjectStage;
use App\Models\Need;
use App\Models\Opportunity;
use App\Models\Project;
use App\Models\Sector;
use Illuminate\Support\Collection;

/**
 * Filtre d'éligibilité selon le projet (OP-02). Il ne lit que les critères
 * structurés de la fiche ; les autres critères restent à vérifier auprès
 * de l'organisme.
 */
class EligibilityService
{
    /**
     * @return array{eligible: bool, reasons: list<string>, blockers: list<string>}
     */
    public function check(Opportunity $opportunity, Project $project): array
    {
        $reasons = [];
        $blockers = [];

        if (filled($opportunity->stages)) {
            $labels = collect($opportunity->stages)->map(fn (string $stage) => ProjectStage::tryFrom($stage)?->label())->filter()->join(', ');

            if (in_array($project->stage?->value, $opportunity->stages, true)) {
                $reasons[] = 'Stade du projet : '.mb_strtolower($project->stage->label());
            } else {
                $blockers[] = 'Réservé aux projets au stade : '.mb_strtolower($labels);
            }
        }

        if (filled($opportunity->sectors)) {
            if (in_array($project->sector?->slug, $opportunity->sectors, true)) {
                $reasons[] = 'Secteur : '.$project->sector->name;
            } else {
                $blockers[] = 'Secteurs visés : '.Sector::whereIn('slug', $opportunity->sectors)->orderBy('name')->pluck('name')->join(', ');
            }
        }

        if ($opportunity->max_amount_fcfa !== null) {
            $amounts = $project->openNeeds
                ->filter(fn (Need $need) => $need->type === NeedType::Financement && $need->amount_fcfa !== null)
                ->pluck('amount_fcfa');

            if ($amounts->contains(fn (int $amount) => $amount <= $opportunity->max_amount_fcfa)) {
                $reasons[] = 'Montant recherché dans le plafond de '.$this->fcfa($opportunity->max_amount_fcfa);
            } elseif ($amounts->isNotEmpty()) {
                $blockers[] = 'Montant recherché au-delà du plafond de '.$this->fcfa($opportunity->max_amount_fcfa);
            }
        }

        if ($reasons === [] && $blockers === []) {
            $reasons[] = 'Ouvert à tous les projets, sous réserve des critères de l\'organisme';
        }

        return ['eligible' => $blockers === [], 'reasons' => $reasons, 'blockers' => $blockers];
    }

    /**
     * Opportunités ouvertes compatibles avec le projet, avec leurs raisons.
     *
     * @param  Collection<int, Opportunity>  $opportunities
     * @return Collection<int, Opportunity>
     */
    public function eligibleFor(Project $project, Collection $opportunities): Collection
    {
        $project->loadMissing(['sector', 'openNeeds']);

        return $opportunities
            ->each(fn (Opportunity $opportunity) => $opportunity->setAttribute('eligibility', $this->check($opportunity, $project)))
            ->filter(fn (Opportunity $opportunity) => $opportunity->eligibility['eligible'])
            ->values();
    }

    private function fcfa(int $amount): string
    {
        return number_format($amount, 0, ',', "\u{00A0}").' FCFA';
    }
}
