<?php

namespace App\Services\Trust;

use App\Enums\VerificationLevel;
use App\Enums\VerificationStatus;
use App\Models\User;
use App\Models\Verification;
use App\Notifications\VerificationReviewed;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Vérification d'identité des investisseurs et des experts (CF-02), traitée
 * par l'équipe (AD-04). La pièce d'identité est effacée après examen.
 */
class VerificationService
{
    /**
     * @throws ValidationException
     */
    public function submit(User $user, UploadedFile $document): Verification
    {
        if ($this->pendingFor($user) !== null) {
            throw ValidationException::withMessages(['document' => 'Votre demande est déjà en cours d\'examen.']);
        }

        if ($user->verification_level->isAtLeast(VerificationLevel::Identity)) {
            throw ValidationException::withMessages(['document' => 'Votre identité est déjà vérifiée.']);
        }

        $verification = new Verification;
        $verification->user()->associate($user);
        $verification->forceFill([
            'level' => VerificationLevel::Identity,
            'status' => VerificationStatus::EnAttente,
            'document_path' => $document->store("verifications/{$user->id}", 'local'),
            'document_name' => Str::limit($document->getClientOriginalName(), 200, ''),
        ])->save();

        return $verification;
    }

    public function pendingFor(User $user): ?Verification
    {
        return $user->verifications()->where('status', VerificationStatus::EnAttente)->first();
    }

    public function latestFor(User $user): ?Verification
    {
        return $user->verifications()->latest()->first();
    }

    /**
     * @return Collection<int, Verification>
     */
    public function pending(): Collection
    {
        return Verification::where('status', VerificationStatus::EnAttente)->with('user')->oldest()->get();
    }

    /**
     * @throws ValidationException
     */
    public function approve(Verification $verification, User $reviewer): Verification
    {
        $this->close($verification, $reviewer, VerificationStatus::Approuvee);
        $verification->forceFill(['expires_at' => now()->addMonths(config('idee.identity_valid_months'))])->save();

        $user = $verification->user;
        if (! $user->verification_level->isAtLeast($verification->level)) {
            $user->forceFill(['verification_level' => $verification->level])->save();
        }

        $user->notify(new VerificationReviewed($verification));

        return $verification;
    }

    /**
     * @throws ValidationException
     */
    public function refuse(Verification $verification, User $reviewer, string $reason): Verification
    {
        $this->close($verification, $reviewer, VerificationStatus::Refusee);
        $verification->forceFill(['refusal_reason' => $reason])->save();
        $verification->user->notify(new VerificationReviewed($verification));

        return $verification;
    }

    private function close(Verification $verification, User $reviewer, VerificationStatus $status): void
    {
        if ($verification->status !== VerificationStatus::EnAttente) {
            throw ValidationException::withMessages(['decision' => 'Cette demande a déjà été traitée.']);
        }

        // Minimisation des données : la pièce d'identité n'est pas conservée après examen.
        if ($verification->document_path) {
            Storage::disk('local')->delete($verification->document_path);
        }

        $verification->forceFill([
            'status' => $status,
            'reviewer_id' => $reviewer->id,
            'reviewed_at' => now(),
            'document_path' => null,
        ])->save();
    }
}
