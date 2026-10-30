<?php

namespace App\Policies;

use App\Models\Meeting;
use App\Models\User;

class MeetingPolicy
{
    /**
     * Seule l'autre personne confirme ou refuse un rendez-vous.
     */
    public function respond(User $user, Meeting $meeting): bool
    {
        return $meeting->contactRequest->involves($user) && $meeting->proposer_id !== $user->getKey();
    }

    public function cancel(User $user, Meeting $meeting): bool
    {
        return $meeting->contactRequest->involves($user);
    }
}
