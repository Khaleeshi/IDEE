<?php

namespace App\Policies;

use App\Models\QuoteRequest;
use App\Models\User;

class QuoteRequestPolicy
{
    public function view(User $user, QuoteRequest $quote): bool
    {
        return $quote->involves($user);
    }

    /**
     * L'expert envoie le devis et déclare la livraison.
     */
    public function quote(User $user, QuoteRequest $quote): bool
    {
        return $quote->expert_id === $user->id;
    }

    /**
     * Le client accepte le devis et confirme la fin de la prestation.
     */
    public function answer(User $user, QuoteRequest $quote): bool
    {
        return $quote->client_id === $user->id;
    }
}
