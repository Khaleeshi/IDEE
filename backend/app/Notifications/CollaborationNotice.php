<?php

namespace App\Notifications;

use App\Models\ContactRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Prochaine étape, rendez-vous ou collaboration à confirmer dans une
 * conversation (CO-01, CO-03, MR-07). Mène à la conversation.
 */
class CollaborationNotice extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ContactRequest $conversation,
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
            'contact_request_id' => $this->conversation->id,
            'message' => $this->text,
        ];
    }
}
