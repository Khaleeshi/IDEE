<?php

namespace App\Models;

use App\Enums\NeedStatus;
use App\Enums\NeedType;
use Database\Factories\NeedFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['type', 'title', 'description', 'amount_fcfa', 'profile_sought', 'deadline', 'counterpart'])]
class Need extends Model
{
    /** @use HasFactory<NeedFactory> */
    use HasFactory, HasUlids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => NeedType::class,
            'status' => NeedStatus::class,
            'amount_fcfa' => 'integer',
            'deadline' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasMany<HelpOffer, $this>
     */
    public function helpOffers(): HasMany
    {
        return $this->hasMany(HelpOffer::class);
    }

    public function isOpen(): bool
    {
        return $this->status === NeedStatus::Ouvert;
    }

    /**
     * Besoin de financement : la page porte la mention exigée et n'est pas indexée (section 9).
     */
    public function isFundraising(): bool
    {
        return $this->type === NeedType::Financement;
    }
}
