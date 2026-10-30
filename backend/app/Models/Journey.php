<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'situation', 'situation_hint', 'title', 'summary', 'position'])]
class Journey extends Model
{
    use HasUlids;

    /**
     * @return HasMany<JourneyStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(JourneyStep::class)->orderBy('position');
    }
}
