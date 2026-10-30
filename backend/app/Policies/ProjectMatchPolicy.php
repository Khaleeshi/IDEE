<?php

namespace App\Policies;

use App\Models\ProjectMatch;
use App\Models\User;

class ProjectMatchPolicy
{
    /**
     * Seul le porteur du projet visé accepte ou décline (RG-12).
     */
    public function respond(User $user, ProjectMatch $match): bool
    {
        return $match->targetProject->isOwnedBy($user);
    }
}
