<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug', 'position', 'title', 'body', 'documents', 'organisation', 'cost', 'delay',
    'source_label', 'source_url', 'checked_on', 'needs_expert', 'action', 'related_journey_id',
])]
class JourneyStep extends Model
{
    use HasUlids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'documents' => 'array',
            'checked_on' => 'date',
            'needs_expert' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Journey, $this>
     */
    public function journey(): BelongsTo
    {
        return $this->belongsTo(Journey::class);
    }

    /**
     * @return BelongsTo<Journey, $this>
     */
    public function relatedJourney(): BelongsTo
    {
        return $this->belongsTo(Journey::class, 'related_journey_id');
    }

    /**
     * @return HasMany<StepProgress, $this>
     */
    public function progress(): HasMany
    {
        return $this->hasMany(StepProgress::class);
    }

    /**
     * Fiche dont la source n'a pas été revue depuis trop longtemps (RG-10).
     */
    public function isStale(): bool
    {
        return $this->source_url !== null
            && ($this->checked_on === null || $this->checked_on->lt(self::reviewThreshold()));
    }

    /**
     * @param  Builder<JourneyStep>  $query
     */
    #[Scope]
    protected function stale(Builder $query): void
    {
        $query->whereNotNull('source_url')->where(fn (Builder $query) => $query
            ->whereNull('checked_on')
            ->orWhereDate('checked_on', '<', self::reviewThreshold()));
    }

    private static function reviewThreshold(): CarbonInterface
    {
        return now()->subMonths(config('idee.guide_review_after_months'))->startOfDay();
    }
}
