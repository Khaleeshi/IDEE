<?php

namespace App\Models;

use App\Enums\MeetingMode;
use App\Enums\MeetingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rendez-vous entre les deux personnes d'une conversation (MR-07).
 */
#[Fillable(['starts_at', 'mode', 'place', 'agenda'])]
class Meeting extends Model
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
            'status' => MeetingStatus::class,
            'mode' => MeetingMode::class,
            'starts_at' => 'datetime',
            'responded_at' => 'datetime',
            'reminded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ContactRequest, $this>
     */
    public function contactRequest(): BelongsTo
    {
        return $this->belongsTo(ContactRequest::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposer_id')->withTrashed();
    }

    /**
     * Le lieu est un lien quand le rendez-vous se tient en visio.
     */
    public function placeIsLink(): bool
    {
        return $this->mode === MeetingMode::Visio && str_starts_with((string) $this->place, 'https://');
    }
}
