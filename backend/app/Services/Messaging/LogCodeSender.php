<?php

namespace App\Services\Messaging;

use App\Contracts\CodeSender;
use Illuminate\Support\Facades\Log;

/**
 * Envoi factice pour le développement : le code est écrit dans le journal.
 * L'envoi par WhatsApp puis par SMS Orange le remplacera en production.
 */
class LogCodeSender implements CodeSender
{
    public function send(string $phone, string $code): void
    {
        Log::info('Code de connexion IDEE', ['phone' => $phone, 'code' => $code]);
    }
}
