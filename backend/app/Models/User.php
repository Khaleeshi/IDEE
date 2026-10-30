<?php

namespace App\Models;

use App\Enums\Availability;
use App\Enums\Offer;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'phone', 'email', 'region', 'languages', 'bio', 'expertise', 'rate', 'skills', 'sectors', 'contact_preferences', 'availability'])]
#[Hidden(['password', 'remember_token', 'phone', 'email'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUlids, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'languages' => 'array',
            'skills' => 'array',
            'sectors' => 'array',
            'contact_preferences' => 'array',
            'verification_level' => VerificationLevel::class,
            'availability' => Availability::class,
            'is_admin' => 'boolean',
            'suspended_until' => 'datetime',
            'banned_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
        ];
    }

    /**
     * Le membre a accepté la version en vigueur des conditions et de la charte.
     */
    public function hasAcceptedTerms(): bool
    {
        return $this->terms_version === config('idee.legal.version');
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /**
     * Compte exclu, ou suspendu à la suite d'une décision de modération.
     */
    public function isRestricted(): bool
    {
        return $this->banned_at !== null || $this->suspended_until?->isFuture();
    }

    public function restrictionMessage(): string
    {
        return $this->banned_at !== null
            ? 'Ce compte a été exclu d\'IDEE à la suite d\'une décision de modération.'
            : 'Ce compte est suspendu jusqu\'au '.$this->suspended_until->translatedFormat('d F Y').' à la suite d\'une décision de modération.';
    }

    /**
     * @return HasMany<RoleAssignment, $this>
     */
    public function roleAssignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class);
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    /**
     * @return HasMany<ContactRequest, $this>
     */
    public function sentContactRequests(): HasMany
    {
        return $this->hasMany(ContactRequest::class, 'sender_id');
    }

    /**
     * @return HasMany<ContactRequest, $this>
     */
    public function receivedContactRequests(): HasMany
    {
        return $this->hasMany(ContactRequest::class, 'recipient_id');
    }

    /**
     * @return HasMany<HelpOffer, $this>
     */
    public function helpOffers(): HasMany
    {
        return $this->hasMany(HelpOffer::class);
    }

    /**
     * @return HasMany<PushSubscription, $this>
     */
    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    /**
     * @return HasMany<OrganisationMember, $this>
     */
    public function organisationMemberships(): HasMany
    {
        return $this->hasMany(OrganisationMember::class);
    }

    /**
     * @return HasMany<SavedSearch, $this>
     */
    public function savedSearches(): HasMany
    {
        return $this->hasMany(SavedSearch::class)->latest();
    }

    /**
     * @return HasMany<Verification, $this>
     */
    public function verifications(): HasMany
    {
        return $this->hasMany(Verification::class);
    }

    /**
     * @return HasMany<StepProgress, $this>
     */
    public function stepProgress(): HasMany
    {
        return $this->hasMany(StepProgress::class);
    }

    /**
     * @return list<UserRole>
     */
    public function roles(): array
    {
        return $this->roleAssignments->pluck('role')->all();
    }

    public function hasRole(UserRole $role): bool
    {
        return $this->roleAssignments->contains(fn (RoleAssignment $assignment) => $assignment->role === $role);
    }

    /**
     * Remplace les rôles du membre par la liste donnée.
     *
     * @param  list<UserRole>  $roles
     */
    public function syncRoles(array $roles): void
    {
        $values = array_values(array_unique(array_map(fn (UserRole $role) => $role->value, $roles)));

        $this->roleAssignments()->whereNotIn('role', $values)->delete();

        foreach ($values as $value) {
            $this->roleAssignments()->firstOrCreate(['role' => $value]);
        }

        $this->unsetRelation('roleAssignments');
    }

    /**
     * Rôles affichés publiquement : un investisseur ou un expert n'apparaît
     * comme tel qu'après vérification de son identité (RG-11).
     *
     * @return list<UserRole>
     */
    public function publicRoles(): array
    {
        return array_values(array_filter(
            $this->roles(),
            fn (UserRole $role) => ! $role->requiresIdentityVerification() || $this->hasVerifiedIdentity(),
        ));
    }

    public function hasVerifiedIdentity(): bool
    {
        return $this->verification_level->isAtLeast(VerificationLevel::Identity);
    }

    /**
     * Nombre de demandes de contact autorisées par semaine (RG-04).
     */
    public function weeklyContactQuota(): int
    {
        $quota = $this->hasVerifiedIdentity()
            ? config('idee.weekly_contact_quota.verified')
            : config('idee.weekly_contact_quota.unverified');

        // Les offres Pro et investisseur relèvent le quota (section 13).
        foreach ($this->activeOffers() as $offer) {
            $quota = max($quota, (int) config('idee.offers.'.$offer->value.'.weekly_contacts'));
        }

        return $quota;
    }

    /**
     * Nombre d'alertes enregistrées autorisées (RR-04), relevé par les offres payantes.
     */
    public function savedSearchLimit(): int
    {
        $limit = (int) config('idee.alerts.max_saved_searches');

        foreach ($this->activeOffers() as $offer) {
            $limit = max($limit, (int) config('idee.offers.'.$offer->value.'.saved_searches'));
        }

        return $limit;
    }

    /**
     * Offres du membre en cours de validité (hors mise en avant de projet).
     *
     * @return list<Offer>
     */
    public function activeOffers(): array
    {
        return $this->entitlements()->active()->whereNull('project_id')->pluck('offer')->unique()->values()->all();
    }

    /**
     * @return HasMany<Entitlement, $this>
     */
    public function entitlements(): HasMany
    {
        return $this->hasMany(Entitlement::class);
    }
}
