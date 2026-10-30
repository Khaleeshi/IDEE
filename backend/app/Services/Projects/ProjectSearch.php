<?php

namespace App\Services\Projects;

use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;

/**
 * Recherche de projets actifs (RR-01) : mot-clé, secteur, région, stade,
 * type de besoin ouvert et montant recherché.
 */
class ProjectSearch
{
    /**
     * @param  array{q?: string|null, sector?: string|null, region?: string|null, stage?: string|null, need_type?: string|null, amount_min?: string|int|null, amount_max?: string|int|null}  $filters
     * @return Builder<Project>
     */
    public function query(array $filters): Builder
    {
        $search = trim((string) ($filters['q'] ?? ''));

        return Project::query()
            ->active()
            ->with(['owner.roleAssignments', 'sector', 'openNeeds'])
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhere('summary', 'like', "%{$search}%")))
            ->when($filters['sector'] ?? null, fn (Builder $query, $sector) => $query->whereHas(
                'sector', fn (Builder $query) => $query->where('slug', $sector)
            ))
            ->when($filters['region'] ?? null, fn (Builder $query, $region) => $query->where('region', $region))
            ->when($filters['stage'] ?? null, fn (Builder $query, $stage) => $query->where('stage', $stage))
            ->when($filters['need_type'] ?? null, fn (Builder $query, $type) => $query->whereHas(
                'openNeeds', fn (Builder $query) => $query->where('type', $type)
            ))
            ->when(
                is_numeric($filters['amount_min'] ?? null) || is_numeric($filters['amount_max'] ?? null),
                fn (Builder $query) => $query->whereHas('openNeeds', fn (Builder $query) => $query
                    ->whereNotNull('amount_fcfa')
                    ->when(is_numeric($filters['amount_min'] ?? null), fn (Builder $query) => $query->where('amount_fcfa', '>=', (int) $filters['amount_min']))
                    ->when(is_numeric($filters['amount_max'] ?? null), fn (Builder $query) => $query->where('amount_fcfa', '<=', (int) $filters['amount_max'])))
            )
            ->latest('last_activity_at');
    }
}
