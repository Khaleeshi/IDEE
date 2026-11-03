<?php

namespace App\Services\Members;

use App\Models\MemberProfile;
use App\Models\Profession;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Profils professionnels d'un membre (PM-02 à PM-05) : un compte peut être à
 * la fois coach de projet, chef de projet et formatrice, sans créer trois comptes.
 */
class MemberProfileService
{
    /**
     * @param  array<string, mixed>  $attributes  données validées
     *
     * @throws ValidationException
     */
    public function create(User $user, array $attributes): MemberProfile
    {
        if ($user->memberProfiles()->count() >= config('idee.member_profiles.max_per_member')) {
            throw ValidationException::withMessages([
                'profession_id' => 'Vous avez atteint le nombre maximum de profils ('.config('idee.member_profiles.max_per_member').'). Supprimez-en un pour en ajouter un autre.',
            ]);
        }

        $profession = Profession::findOrFail($attributes['profession_id']);

        // PM-04 : un métier désactivé ne se choisit plus.
        if (! $profession->active) {
            throw ValidationException::withMessages(['profession_id' => 'Ce métier n\'est plus proposé. Choisissez-en un autre.']);
        }

        if ($user->memberProfiles()->where('profession_id', $profession->id)->exists()) {
            throw ValidationException::withMessages(['profession_id' => 'Vous avez déjà un profil pour ce métier : modifiez-le plutôt.']);
        }

        $this->ensureAnOffer($attributes);

        return DB::transaction(function () use ($user, $attributes) {
            $profile = $user->memberProfiles()->make(Arr::except($attributes, ['is_primary']));
            // PM-02 : le premier profil devient le profil principal.
            $profile->is_primary = ! $user->memberProfiles()->exists() || (bool) ($attributes['is_primary'] ?? false);
            $profile->save();

            if ($profile->is_primary) {
                $this->makePrimary($profile);
            }

            return $profile;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes  données validées (le métier ne change pas)
     *
     * @throws ValidationException
     */
    public function update(MemberProfile $profile, array $attributes): MemberProfile
    {
        $attributes = Arr::except($attributes, ['profession_id']);
        $this->ensureAnOffer([...$profile->only(['offers_paid', 'offers_volunteer', 'offers_mentoring']), ...$attributes]);

        $profile->fill(Arr::except($attributes, ['is_primary']))->save();

        if ($attributes['is_primary'] ?? false) {
            $this->makePrimary($profile);
        }

        return $profile;
    }

    /**
     * Supprime un profil sans toucher au compte ; si c'était le principal,
     * le plus ancien des autres le devient.
     */
    public function delete(MemberProfile $profile): void
    {
        DB::transaction(function () use ($profile) {
            $profile->delete();

            if ($profile->is_primary) {
                $next = MemberProfile::where('user_id', $profile->user_id)->oldest()->orderBy('id')->first();
                $next?->forceFill(['is_primary' => true])->save();
            }
        });
    }

    /**
     * Un seul profil principal par membre.
     */
    public function makePrimary(MemberProfile $profile): void
    {
        DB::transaction(function () use ($profile) {
            MemberProfile::where('user_id', $profile->user_id)->whereKeyNot($profile->id)->update(['is_primary' => false]);
            $profile->forceFill(['is_primary' => true])->save();
        });
    }

    /**
     * PM-03 : au moins une forme d'intervention (payante, bénévole ou mentorat).
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    private function ensureAnOffer(array $attributes): void
    {
        if (! ($attributes['offers_paid'] ?? false) && ! ($attributes['offers_volunteer'] ?? false) && ! ($attributes['offers_mentoring'] ?? false)) {
            throw ValidationException::withMessages([
                'offers' => 'Cochez au moins une forme d\'intervention : prestations payantes, bénévolat ou mentorat.',
            ]);
        }
    }
}
