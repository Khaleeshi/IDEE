<?php

namespace App\Policies;

use App\Models\HelpOffer;
use App\Models\User;

class HelpOfferPolicy
{
    public function respond(User $user, HelpOffer $offer): bool
    {
        return $offer->need->project->isOwnedBy($user);
    }
}
