<?php

namespace App\Policies;

use App\Models\Organisation;
use App\Models\User;

class OrganisationPolicy
{
    /**
     * Gérer la page : informations, membres, vérification, programmes (CP-05).
     */
    public function update(User $user, Organisation $organisation): bool
    {
        return $organisation->isManagedBy($user);
    }

    /**
     * IN-01 : programmes, candidatures et cohortes.
     */
    public function manageProgrammes(User $user, Organisation $organisation): bool
    {
        return $organisation->managesProgrammes($user);
    }
}
