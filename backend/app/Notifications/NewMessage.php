<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Nouveau message reçu dans la messagerie (MR-04).
 */
class NewMessage extends Notification
{
    use Queueable;

    public function __construct(public readonly Message $message) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        // En push mais pas sur WhatsApp : les messages sont trop fréquents.
        return AlertChannels::for($notifiable, whatsapp: false);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'contact_request_id' => $this->message->contact_request_id,
            'message' => 'Nouveau message de '.$this->message->sender->name.'.',
        ];
    }
}
