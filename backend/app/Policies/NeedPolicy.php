<?php

namespace App\Policies;

use App\Models\Need;
use App\Models\User;

class NeedPolicy
{
    public function update(User $user, Need $need): bool
    {
        return $need->project->isOwnedBy($user);
    }

    public function viewHelpOffers(User $user, Need $need): bool
    {
        return $need->project->isOwnedBy($user);
    }
}
