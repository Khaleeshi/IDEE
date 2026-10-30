<?php

namespace App\Models;

use App\Enums\MeetingMode;
use App\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Session de pitch en ligne ou en présentiel (EV-01).
 */
#[Fillable(['title', 'format', 'description', 'mode', 'starts_at', 'place', 'pitch_slots', 'apply_until'])]
class Event extends Model
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
            'mode' => MeetingMode::class,
            'starts_at' => 'datetime',
            'apply_until' => 'date',
            'pitch_slots' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Organisation, $this>
     */
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    /**
     * @return HasMany<EventRegistration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * Sessions publiées à venir.
     *
     * @param  Builder<Event>  $query
     */
    #[Scope]
    protected function upcoming(Builder $query): void
    {
        $query->where('status', 'publie')->where('starts_at', '>=', now())->orderBy('starts_at');
    }

    /**
     * @return HasMany<EventPairing, $this>
     */
    public function pairings(): HasMany
    {
        return $this->hasMany(EventPairing::class)->orderBy('round');
    }

    /**
     * Rencontres ciblées par binômes (EV-02), plutôt qu'une session de pitch.
     */
    public function isSpeedMeeting(): bool
    {
        return $this->format === 'rencontres';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'annule';
    }

    /**
     * Les candidatures pour présenter un projet sont encore ouvertes.
     */
    public function acceptsPitches(): bool
    {
        return $this->pitch_slots > 0
            && ! $this->isSpeedMeeting()
            && ! $this->isCancelled()
            && $this->starts_at->isFuture()
            && ($this->apply_until === null || ! $this->apply_until->isPast() || $this->apply_until->isToday());
    }

    public function selectedCount(): int
    {
        return $this->registrations()->where('status', RegistrationStatus::Retenu)->count();
    }
}
