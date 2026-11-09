<?php

namespace App\Policies;

use App\Models\Accompaniment;
use App\Models\User;

class AccompanimentPolicy
{
    /**
     * La personne accompagnée et son coach, eux seuls.
     */
    public function view(User $user, Accompaniment $accompaniment): bool
    {
        return $accompaniment->involves($user);
    }

    /**
     * Accepter ou refuser : le coach.
     */
    public function respond(User $user, Accompaniment $accompaniment): bool
    {
        return $user->id === $accompaniment->coach_id;
    }

    /**
     * Retirer sa demande, laisser un avis : la personne accompagnée.
     */
    public function cancel(User $user, Accompaniment $accompaniment): bool
    {
        return $user->id === $accompaniment->requester_id;
    }
}
