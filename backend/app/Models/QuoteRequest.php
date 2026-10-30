<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Demande de devis à un expert, puis suivi de la prestation (SE-02).
 */
#[Fillable(['description', 'budget', 'wanted_by'])]
class QuoteRequest extends Model
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
            'status' => QuoteStatus::class,
            'wanted_by' => 'date',
            'quote_amount_fcfa' => 'integer',
            'quoted_at' => 'datetime',
            'accepted_at' => 'datetime',
            'delivered_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function expert(): BelongsTo
    {
        return $this->belongsTo(User::class, 'expert_id')->withTrashed();
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function involves(?User $user): bool
    {
        return $user !== null && in_array($user->id, [$this->client_id, $this->expert_id], true);
    }
}
