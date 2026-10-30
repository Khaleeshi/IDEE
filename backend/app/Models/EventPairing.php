<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Binôme d'une rencontre ciblée, pour un tour donné (EV-02).
 */
class EventPairing extends Model
{
    use HasUlids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['starts_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function first(): BelongsTo
    {
        return $this->belongsTo(User::class, 'first_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function second(): BelongsTo
    {
        return $this->belongsTo(User::class, 'second_user_id');
    }

    public function partnerOf(User $user): ?User
    {
        return $this->first_user_id === $user->id ? $this->second : $this->first;
    }
}
