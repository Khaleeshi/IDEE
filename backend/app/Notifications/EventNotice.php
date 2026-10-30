<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Session de pitch : sélection, annulation ou rappel la veille (EV-01).
 */
class EventNotice extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Event $event,
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
            'event_id' => $this->event->id,
            'message' => $this->text,
        ];
    }
}
