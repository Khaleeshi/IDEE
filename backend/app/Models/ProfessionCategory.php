<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catégorie du référentiel des métiers (PM-01) : gestion de projet, coaching et mentorat…
 */
#[Fillable(['slug', 'name', 'position'])]
class ProfessionCategory extends Model
{
    /**
     * @return HasMany<Profession, $this>
     */
    public function professions(): HasMany
    {
        return $this->hasMany(Profession::class)->orderBy('name');
    }
}
