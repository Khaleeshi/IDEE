<?php

namespace App\Policies;

use App\Models\Collaboration;
use App\Models\User;

class CollaborationPolicy
{
    /**
     * Seul le partenaire confirme une collaboration déclarée par l'autre.
     */
    public function respond(User $user, Collaboration $collaboration): bool
    {
        return $collaboration->partner_id === $user->getKey();
    }
}
