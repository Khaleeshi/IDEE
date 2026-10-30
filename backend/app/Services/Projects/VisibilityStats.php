<?php

namespace App\Services\Projects;

use App\Models\ContactRequest;
use App\Models\HelpOffer;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Statistiques de visibilité d'un projet, réservées à l'offre Pro (section 13).
 */
class VisibilityStats
{
    /**
     * Compte une vue de la fiche, sauf celles du porteur. Une ligne par projet et par jour.
     */
    public function recordView(Project $project, ?User $viewer): void
    {
        if ($project->isOwnedBy($viewer)) {
            return;
        }

        DB::table('project_views')->upsert(
            [['project_id' => $project->id, 'day' => today()->toDateString(), 'views' => 1]],
            ['project_id', 'day'],
            ['views' => DB::raw('views + 1')],
        );
    }

    /**
     * @return array{views_7: int, views_30: int, requests_30: int, help_offers_30: int}
     */
    public function for(Project $project): array
    {
        $views = fn (int $days) => (int) DB::table('project_views')
            ->where('project_id', $project->id)
            ->where('day', '>', today()->subDays($days)->toDateString())
            ->sum('views');

        return [
            'views_7' => $views(7),
            'views_30' => $views(30),
            'requests_30' => ContactRequest::where('project_id', $project->id)->where('created_at', '>=', now()->subDays(30))->count(),
            'help_offers_30' => HelpOffer::whereHas('need', fn ($query) => $query->where('project_id', $project->id))
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
        ];
    }

    public function isAvailableTo(User $user): bool
    {
        return $user->activeOffers() !== [];
    }
}
