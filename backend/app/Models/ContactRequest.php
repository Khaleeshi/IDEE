<?php

namespace App\Models;

use App\Enums\ContactRequestStatus;
use App\Services\Moderation\ScamDetector;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['project_id', 'need_id', 'message', 'expires_at'])]
class ContactRequest extends Model
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
            'status' => ContactRequestStatus::class,
            'read_at' => 'datetime',
            'responded_at' => 'datetime',
            'reminded_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id')->withTrashed();
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Need, $this>
     */
    public function need(): BelongsTo
    {
        return $this->belongsTo(Need::class);
    }

    /**
     * @return HasMany<ContactRequestEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(ContactRequestEvent::class);
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->oldest()->oldest('id');
    }

    /**
     * @return HasMany<Agreement, $this>
     */
    public function agreements(): HasMany
    {
        return $this->hasMany(Agreement::class)->latest();
    }

    /**
     * @return HasMany<Meeting, $this>
     */
    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class)->orderBy('starts_at');
    }

    /**
     * @return HasMany<Collaboration, $this>
     */
    public function collaborations(): HasMany
    {
        return $this->hasMany(Collaboration::class)->latest();
    }

    /**
     * L'autre personne de la conversation.
     */
    public function otherParty(User $user): ?User
    {
        return $this->sender_id === $user->getKey() ? $this->recipient : $this->sender;
    }

    /**
     * La messagerie s'ouvre quand le destinataire s'est dit intéressé (double consentement).
     */
    public function allowsMessages(): bool
    {
        return $this->status === ContactRequestStatus::Interesse;
    }

    /**
     * Le message de la demande parle d'argent, d'un code ou de coordonnées bancaires (section 8).
     */
    public function raisesScamAlert(): bool
    {
        return app(ScamDetector::class)->isSuspicious($this->message);
    }

    public function involves(User $user): bool
    {
        return in_array($user->getKey(), [$this->sender_id, $this->recipient_id], true);
    }

    /**
     * Change le statut et garde la trace du changement dans l'historique.
     */
    public function transitionTo(ContactRequestStatus $status, ?User $actor = null): void
    {
        $from = $this->status;
        $this->status = $status;
        $this->save();

        $this->events()->create([
            'actor_id' => $actor?->getKey(),
            'from_status' => $from,
            'to_status' => $status,
        ]);
    }
}
