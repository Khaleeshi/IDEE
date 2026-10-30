<?php

namespace App\Policies;

use App\Models\Agreement;
use App\Models\User;

class AgreementPolicy
{
    /**
     * Seule l'autre personne accepte ou refuse une proposition.
     */
    public function respond(User $user, Agreement $agreement): bool
    {
        return $agreement->contactRequest->involves($user) && $agreement->proposer_id !== $user->getKey();
    }

    public function complete(User $user, Agreement $agreement): bool
    {
        return $agreement->contactRequest->involves($user);
    }
}
