<?php

namespace App\Notifications;

use App\Models\ContactRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Relance du destinataire d'une demande restée sans réponse (RG-03).
 * Elle est affichée dans IDEE et envoyée sur WhatsApp quand le canal est activé (NO-02).
 */
class ContactRequestReminder extends Notification implements CriticalAlert
{
    use Queueable;

    public function __construct(public readonly ContactRequest $contactRequest) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return AlertChannels::for($notifiable);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'contact_request_id' => $this->contactRequest->getKey(),
            'message' => 'Une demande de contact attend votre réponse. Elle expirera le '
                .$this->contactRequest->expires_at->translatedFormat('d/m/Y').'.',
        ];
    }
}
