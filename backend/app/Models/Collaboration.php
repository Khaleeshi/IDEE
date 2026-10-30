<?php

namespace App\Models;

use App\Enums\CollaborationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Collaboration déclarée par l'un et confirmée par l'autre (CO-03, FC-06).
 */
#[Fillable(['summary'])]
class Collaboration extends Model
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
            'status' => CollaborationStatus::class,
            'confirmed_at' => 'datetime',
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
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function declarer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'declared_by')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_id')->withTrashed();
    }

    /**
     * Collaborations confirmées auxquelles un membre a pris part.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function confirmedFor(Builder $query, User $user): void
    {
        $query->where('status', CollaborationStatus::Confirmee)
            ->where(fn (Builder $query) => $query->where('declared_by', $user->id)->orWhere('partner_id', $user->id));
    }

    /**
     * L'autre personne de la collaboration, vue depuis un membre.
     */
    public function otherThan(User $user): ?User
    {
        return $this->declared_by === $user->id ? $this->partner : $this->declarer;
    }
}
