<?php

namespace App\Notifications;

use App\Models\Organisation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Nouvelle concernant une organisation : ajout comme membre, décision de vérification (CP-05, CF-03).
 */
class OrganisationNotice extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Organisation $organisation,
        public readonly string $text,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return AlertChannels::for($notifiable, whatsapp: false);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'organisation_id' => $this->organisation->id,
            'message' => $this->text,
        ];
    }
}
