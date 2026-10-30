<?php

namespace App\Services\Messaging;

use App\Contracts\CodeSender;
use App\Exceptions\CodeDeliveryFailed;
use Illuminate\Support\Facades\Log;

/**
 * Essaie les canaux dans l'ordre (WhatsApp d'abord, le moins cher, puis le
 * SMS) et s'arrête au premier qui remet le code.
 */
class FallbackCodeSender implements CodeSender
{
    /**
     * @param  list<CodeSender>  $senders
     */
    public function __construct(private readonly array $senders) {}

    public function send(string $phone, string $code): void
    {
        $failure = null;

        foreach ($this->senders as $sender) {
            try {
                $sender->send($phone, $code);

                return;
            } catch (CodeDeliveryFailed $exception) {
                Log::warning('Canal d\'envoi du code indisponible', [
                    'canal' => class_basename($sender),
                    'raison' => $exception->getMessage(),
                ]);
                $failure = $exception;
            }
        }

        throw $failure ?? new CodeDeliveryFailed('Aucun canal d\'envoi configuré.');
    }
}
