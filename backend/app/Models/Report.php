<?php

namespace App\Models;

use App\Enums\ModerationDecision;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
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
            'reason' => ReportReason::class,
            'status' => ReportStatus::class,
            'decision' => ModerationDecision::class,
            'decided_at' => 'datetime',
            'appealed_at' => 'datetime',
            'appeal_upheld' => 'boolean',
            'appeal_decided_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id')->withTrashed();
    }

    /**
     * La personne visée peut contester une fois, dans le délai fixé.
     */
    public function canBeAppealed(): bool
    {
        return $this->status === ReportStatus::Traite
            && $this->decision?->informsTarget()
            && $this->appealed_at === null
            && $this->decided_at?->gt(now()->subDays(config('idee.moderation.appeal_days')));
    }
}
