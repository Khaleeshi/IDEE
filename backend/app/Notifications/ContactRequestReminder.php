<?php

namespace App\Notifications;

use App\Models\ContactRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Relance du destinataire d'une demande restée sans réponse (RG-03).
 * Elle est enregistrée en base ; les canaux push et WhatsApp viendront
 * s'ajouter (NO-01, NO-02).
 */
class ContactRequestReminder extends Notification
{
    use Queueable;

    public function __construct(public readonly ContactRequest $contactRequest) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
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
