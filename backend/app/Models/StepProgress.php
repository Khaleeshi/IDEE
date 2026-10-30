<?php

namespace App\Models;

use App\Enums\StepStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['note', 'remind_on'])]
class StepProgress extends Model
{
    use HasUlids;

    protected $table = 'step_progress';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StepStatus::class,
            'remind_on' => 'date',
            'reminded_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<JourneyStep, $this>
     */
    public function step(): BelongsTo
    {
        return $this->belongsTo(JourneyStep::class, 'journey_step_id');
    }

    /**
     * Rappel arrivé à échéance sur une étape pas encore terminée.
     */
    public function isDue(): bool
    {
        return $this->remind_on !== null && ! $this->status->isDone() && $this->remind_on->lte(today());
    }
}
