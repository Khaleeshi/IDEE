<?php

namespace App\Notifications;

use App\Models\ContactRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Nouvelle demande de contact reçue (MR-01).
 */
class ContactRequestReceived extends Notification
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
            'message' => $this->contactRequest->sender->name.' vous a envoyé une demande de contact sur IDEE. Répondez avant le '
                .$this->contactRequest->expires_at->translatedFormat('d/m/Y').'.',
        ];
    }
}
