<?php

namespace App\Services\Experts;

use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Annuaire des experts vérifiés avec leurs tarifs (SE-01). RG-11 : un
 * expert n'y figure qu'une fois son identité vérifiée.
 */
class ExpertDirectory
{
    /**
     * @param  array{q?: string|null, sector?: string|null, region?: string|null}  $filters
     * @return Builder<User>
     */
    public function query(array $filters = []): Builder
    {
        $verified = collect(VerificationLevel::cases())
            ->filter(fn (VerificationLevel $level) => $level->isAtLeast(VerificationLevel::Identity))
            ->map(fn (VerificationLevel $level) => $level->value)
            ->values();
        $search = trim((string) ($filters['q'] ?? ''));

        return User::query()
            ->whereHas('roleAssignments', fn (Builder $query) => $query->where('role', UserRole::Expert))
            ->whereIn('verification_level', $verified)
            ->whereNull('banned_at')
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('expertise', 'like', "%{$search}%")
                ->orWhere('skills', 'like', "%{$search}%")
                ->orWhere('bio', 'like', "%{$search}%")))
            ->when($filters['sector'] ?? null, fn (Builder $query, string $sector) => $query->whereJsonContains('sectors', $sector))
            ->when($filters['region'] ?? null, fn (Builder $query, string $region) => $query->where('region', $region))
            ->with('roleAssignments')
            ->orderByRaw('expertise is null')
            ->orderBy('name');
    }
}
