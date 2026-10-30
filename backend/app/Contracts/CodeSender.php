<?php

namespace App\Contracts;

/**
 * Envoie un code de connexion à usage unique à un numéro de téléphone.
 */
interface CodeSender
{
    public function send(string $phone, string $code): void;
}
