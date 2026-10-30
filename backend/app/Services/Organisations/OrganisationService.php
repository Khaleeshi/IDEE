<?php

namespace App\Services\Organisations;

use App\Enums\OrganisationRole;
use App\Enums\OrganisationVerification;
use App\Models\Opportunity;
use App\Models\Organisation;
use App\Models\OrganisationMember;
use App\Models\User;
use App\Notifications\OrganisationNotice;
use App\Services\Auth\OneTimeCodeService;
use App\Support\PhoneNumber;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Pages d'organisation gérées à plusieurs (CP-05), vérification par RCCM et
 * NINEA (CF-03) et programmes publiés par les organisations vérifiées (OR-01).
 */
class OrganisationService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $creator, array $attributes): Organisation
    {
        return DB::transaction(function () use ($creator, $attributes) {
            $organisation = Organisation::create($attributes);
            $this->attach($organisation, $creator, OrganisationRole::Administrateur);

            return $organisation;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Organisation $organisation, array $attributes): Organisation
    {
        $organisation->update($attributes);

        return $organisation;
    }

    /**
     * Ajoute un membre déjà inscrit, retrouvé par son téléphone ou son e-mail.
     *
     * @throws ValidationException
     */
    public function addMember(Organisation $organisation, User $actor, string $identifier, OrganisationRole $role): OrganisationMember
    {
        $identifier = trim($identifier);
        $user = OneTimeCodeService::isEmail($identifier)
            ? User::where('email', Str::lower($identifier))->first()
            : User::where('phone', PhoneNumber::normalize($identifier))->first();

        if ($user === null) {
            throw ValidationException::withMessages(['identifier' => 'Aucun membre avec ce numéro ou cet e-mail. Il doit d\'abord se connecter une fois à IDEE.']);
        }

        if ($organisation->members()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages(['identifier' => $user->name.' fait déjà partie de l\'organisation.']);
        }

        $member = $this->attach($organisation, $user, $role);
        $user->notify(new OrganisationNotice($organisation, $actor->name.' vous a ajouté à la page « '.$organisation->name.' » ('.mb_strtolower($role->label()).').'));

        return $member;
    }

    /**
     * @throws ValidationException
     */
    public function removeMember(OrganisationMember $member): void
    {
        $admins = $member->organisation->members()->where('role', OrganisationRole::Administrateur)->count();

        if ($member->role === OrganisationRole::Administrateur && $admins <= 1) {
            throw ValidationException::withMessages(['member' => 'Une organisation garde au moins une personne qui gère sa page.']);
        }

        $member->delete();
    }

    /**
     * Demande de vérification avec l'extrait RCCM ou l'attestation NINEA (CF-03).
     *
     * @throws ValidationException
     */
    public function requestVerification(Organisation $organisation, string $rccm, string $ninea, UploadedFile $document): Organisation
    {
        if ($organisation->verification_status === OrganisationVerification::EnAttente) {
            throw ValidationException::withMessages(['document' => 'Une vérification est déjà en cours.']);
        }

        if ($organisation->verification_document_path) {
            Storage::disk('local')->delete($organisation->verification_document_path);
        }

        $organisation->forceFill([
            'rccm' => Str::upper(trim($rccm)),
            'ninea' => Str::upper(trim($ninea)),
            'verification_status' => OrganisationVerification::EnAttente,
            'verification_document_path' => $document->store("verifications/organisations/{$organisation->id}", 'local'),
            'verification_note' => null,
            'verification_requested_at' => now(),
            'verified_at' => null,
        ])->save();

        return $organisation;
    }

    /**
     * Décision de l'équipe. La pièce justificative n'est pas conservée après examen.
     *
     * @throws ValidationException
     */
    public function decideVerification(Organisation $organisation, bool $approved, ?string $note = null): Organisation
    {
        if ($organisation->verification_status !== OrganisationVerification::EnAttente) {
            throw ValidationException::withMessages(['decision' => 'Cette demande a déjà été traitée.']);
        }

        if (! $approved && blank($note)) {
            throw ValidationException::withMessages(['note' => 'Indiquez le motif du refus : il est transmis à l\'organisation.']);
        }

        if ($organisation->verification_document_path) {
            Storage::disk('local')->delete($organisation->verification_document_path);
        }

        $organisation->forceFill([
            'verification_status' => $approved ? OrganisationVerification::Verifiee : OrganisationVerification::Refusee,
            'verification_document_path' => null,
            'verification_note' => $approved ? null : trim((string) $note),
            'verified_at' => $approved ? now() : null,
        ])->save();

        $text = $approved
            ? 'La page « '.$organisation->name.' » est vérifiée.'
            : 'La vérification de « '.$organisation->name.' » n\'a pas abouti : '.$organisation->verification_note;

        $organisation->members()->with('user')->where('role', OrganisationRole::Administrateur)->get()
            ->each(fn (OrganisationMember $member) => $member->user->notify(new OrganisationNotice($organisation, $text)));

        return $organisation;
    }

    /**
     * Publie ou met à jour un programme de l'organisation dans le répertoire (OR-01).
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    public function saveProgramme(Organisation $organisation, array $attributes, ?Opportunity $programme = null): Opportunity
    {
        if (! $organisation->canPublishProgrammes()) {
            throw ValidationException::withMessages(['title' => 'Seules les organisations vérifiées d\'accompagnement, de financement ou publiques publient des programmes.']);
        }

        $programme ??= new Opportunity(['slug' => Str::slug($attributes['title']).'-'.Str::lower(Str::random(6))]);
        $programme->fill([
            ...$attributes,
            'stages' => ($attributes['stages'] ?? null) ?: null,
            'sectors' => ($attributes['sectors'] ?? null) ?: null,
            'organisation' => $organisation->name,
            'source_label' => $organisation->name.' (organisation vérifiée)',
            'checked_on' => today(),
            'is_published' => true,
        ]);
        $programme->organisationPage()->associate($organisation);
        $programme->edited_at = now();
        $programme->save();

        return $programme;
    }

    private function attach(Organisation $organisation, User $user, OrganisationRole $role): OrganisationMember
    {
        $member = new OrganisationMember;
        $member->organisation()->associate($organisation);
        $member->user()->associate($user);
        $member->role = $role;
        $member->save();

        return $member;
    }
}
