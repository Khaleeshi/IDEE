<?php

namespace App\Notifications;

use App\Models\Accompaniment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Étape d'un accompagnement : demande, réponse, fin, avis (AC-01, AV-01).
 */
class AccompanimentNotice extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Accompaniment $accompaniment,
        public readonly string $text,
    ) {}

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
            'accompaniment_id' => $this->accompaniment->id,
            'message' => $this->text,
        ];
    }
}
