<?php

namespace App\Services\Projects;

use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;

/**
 * Recherche de projets actifs (RR-01) : mot-clé, secteur, région, stade
 * et type de besoin ouvert.
 */
class ProjectSearch
{
    /**
     * @param  array{q?: string|null, sector?: string|null, region?: string|null, stage?: string|null, need_type?: string|null}  $filters
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
            ->latest('last_activity_at');
    }
}
