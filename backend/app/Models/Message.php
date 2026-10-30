<?php

namespace App\Models;

use App\Enums\MessageKind;
use App\Services\Moderation\ScamDetector;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
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
            'kind' => MessageKind::class,
            'read_at' => 'datetime',
            'removed_at' => 'datetime',
            'attachment_size' => 'integer',
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
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id')->withTrashed();
    }

    public function hasAttachment(): bool
    {
        return $this->attachment_path !== null && ! $this->isRemoved();
    }

    public function isRemoved(): bool
    {
        return $this->removed_at !== null;
    }

    /**
     * Le message parle d'argent, d'un code ou de coordonnées bancaires (section 8).
     */
    public function raisesScamAlert(): bool
    {
        return ! $this->isRemoved() && app(ScamDetector::class)->isSuspicious($this->body);
    }
}
