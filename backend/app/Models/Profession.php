<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Métier du référentiel (PM-01). Un métier ne donne aucun droit : il sert à se
 * présenter, à chercher et à rapprocher les membres.
 */
#[Fillable(['profession_category_id', 'slug', 'name', 'description', 'regulated', 'active'])]
class Profession extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'regulated' => 'boolean',
            'active' => 'boolean',
        ];
    }

    /**
     * Métiers actifs pour une liste de choix, groupés par catégorie.
     *
     * @return Collection<string, Collection<int, string>>
     */
    public static function grouped(): Collection
    {
        return ProfessionCategory::query()
            ->orderBy('position')
            ->with(['professions' => fn ($query) => $query->where('active', true)])
            ->get()
            ->filter(fn (ProfessionCategory $category) => $category->professions->isNotEmpty())
            ->mapWithKeys(fn (ProfessionCategory $category) => [
                $category->name => $category->professions
                    ->sortBy(fn (Profession $profession) => Str::ascii($profession->name))
                    ->pluck('name', 'id'),
            ]);
    }

    /**
     * @param  Builder<Profession>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    /**
     * @return BelongsTo<ProfessionCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProfessionCategory::class, 'profession_category_id');
    }

    /**
     * @return HasMany<MemberProfile, $this>
     */
    public function memberProfiles(): HasMany
    {
        return $this->hasMany(MemberProfile::class);
    }
}
