<?php

namespace App\Services\Members;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Arr;

class ProfileService
{
    /**
     * @param  array<string, mixed>  $attributes  données validées, rôles compris
     */
    public function update(User $user, array $attributes): User
    {
        $user->fill(Arr::except($attributes, ['roles']));
        $user->save();

        if (array_key_exists('roles', $attributes)) {
            $user->syncRoles(array_map(UserRole::from(...), $attributes['roles']));
        }

        return $user;
    }
}
