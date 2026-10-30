<?php

namespace App\Policies;

use App\Models\ContactRequest;
use App\Models\User;

class ContactRequestPolicy
{
    public function view(User $user, ContactRequest $request): bool
    {
        return $request->involves($user);
    }

    public function message(User $user, ContactRequest $request): bool
    {
        return $request->involves($user) && $request->allowsMessages();
    }

    public function respond(User $user, ContactRequest $request): bool
    {
        return $request->recipient_id === $user->getKey();
    }
}
