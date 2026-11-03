<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Profil professionnel d'un membre (PM-02) : un compte peut en avoir plusieurs,
 * par exemple coach de projet, chef de projet et formatrice.
 */
#[Fillable(['profession_id', 'title', 'description', 'experience_years', 'offers_paid', 'offers_volunteer', 'offers_mentoring', 'rate', 'active'])]
class MemberProfile extends Model
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
            'offers_paid' => 'boolean',
            'offers_volunteer' => 'boolean',
            'offers_mentoring' => 'boolean',
            'is_primary' => 'boolean',
            'active' => 'boolean',
        ];
    }

    /**
     * Intitulé affiché : celui choisi par le membre, sinon le nom du métier.
     */
    public function label(): string
    {
        return $this->title ?: $this->profession->name;
    }

    /**
     * Formes d'intervention proposées, en toutes lettres.
     *
     * @return list<string>
     */
    public function offerLabels(): array
    {
        return array_values(array_filter([
            $this->offers_paid ? 'Prestations payantes' : null,
            $this->offers_volunteer ? 'Bénévolat' : null,
            $this->offers_mentoring ? 'Mentorat' : null,
        ]));
    }

    /**
     * PM-05 : un profil payant, de mentorat ou d'une profession réglementée
     * n'est visible qu'une fois l'identité du membre vérifiée.
     */
    public function requiresVerifiedIdentity(): bool
    {
        return $this->offers_paid || $this->offers_mentoring || $this->profession->regulated;
    }

    public function isPublic(): bool
    {
        return $this->active && (! $this->requiresVerifiedIdentity() || $this->user->hasVerifiedIdentity());
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Profession, $this>
     */
    public function profession(): BelongsTo
    {
        return $this->belongsTo(Profession::class);
    }
}
