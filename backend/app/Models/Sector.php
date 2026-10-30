<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

#[Fillable(['slug', 'name'])]
class Sector extends Model
{
    /**
     * Secteurs pour une liste de choix, triés sans tenir compte des accents.
     *
     * @return Collection<int|string, string>
     */
    public static function options(string $key = 'id'): Collection
    {
        return static::query()->pluck('name', $key)->sortBy(fn (string $name) => Str::ascii($name));
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
