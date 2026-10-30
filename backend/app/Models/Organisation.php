<?php

namespace App\Models;

use App\Enums\OrganisationKind;
use App\Enums\OrganisationRole;
use App\Enums\OrganisationVerification;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Page d'organisation gérée par plusieurs membres (CP-05).
 */
#[Fillable(['name', 'kind', 'description', 'region', 'website'])]
#[Hidden(['verification_document_path'])]
class Organisation extends Model
{
    use HasUlids;

    /**
     * @var array<string, string>
     */
    protected $attributes = ['verification_status' => 'non_verifiee'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => OrganisationKind::class,
            'verification_status' => OrganisationVerification::class,
            'verification_requested_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<OrganisationMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(OrganisationMember::class)->orderByDesc('role')->oldest();
    }

    /**
     * Programmes publiés par l'organisation (OR-01).
     *
     * @return HasMany<Opportunity, $this>
     */
    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class)->latest();
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->latest();
    }

    public function isVerified(): bool
    {
        return $this->verification_status === OrganisationVerification::Verifiee;
    }

    public function isManagedBy(?User $user): bool
    {
        return $user !== null && $this->members()
            ->where('user_id', $user->id)
            ->where('role', OrganisationRole::Administrateur)
            ->exists();
    }

    /**
     * Une organisation vérifiée d'accompagnement, de financement ou publique publie ses programmes.
     */
    public function canPublishProgrammes(): bool
    {
        return $this->isVerified() && $this->kind->publishesProgrammes();
    }
}
