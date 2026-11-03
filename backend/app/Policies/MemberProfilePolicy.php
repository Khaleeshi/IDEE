<?php

namespace App\Policies;

use App\Models\MemberProfile;
use App\Models\User;

class MemberProfilePolicy
{
    /**
     * Seul le membre modifie ou supprime ses propres profils.
     */
    public function update(User $user, MemberProfile $profile): bool
    {
        return $profile->user_id === $user->id;
    }

    public function delete(User $user, MemberProfile $profile): bool
    {
        return $this->update($user, $profile);
    }
}
