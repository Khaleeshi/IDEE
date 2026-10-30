<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['project_id'])]
class CohortProject extends Model
{
    use HasUlids;

    /**
     * @return BelongsTo<Cohort, $this>
     */
    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasMany<CohortCheckin, $this>
     */
    public function checkins(): HasMany
    {
        return $this->hasMany(CohortCheckin::class)->latest();
    }

    /**
     * @return HasOne<CohortCheckin, $this>
     */
    public function latestCheckin(): HasOne
    {
        return $this->hasOne(CohortCheckin::class)->latestOfMany();
    }
}
