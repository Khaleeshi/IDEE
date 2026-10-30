<?php

namespace App\Models;

use App\Enums\ProjectMatchStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rapprochement proposé par le porteur d'un projet à celui d'un projet proche (PR-08).
 */
#[Fillable(['message'])]
class ProjectMatch extends Model
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
            'status' => ProjectMatchStatus::class,
            'responded_at' => 'datetime',
        ];
    }

    /**
     * Projet dont le porteur propose le rapprochement.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function targetProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'target_project_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Conversation ouverte une fois le rapprochement accepté.
     *
     * @return BelongsTo<ContactRequest, $this>
     */
    public function contactRequest(): BelongsTo
    {
        return $this->belongsTo(ContactRequest::class);
    }
}
