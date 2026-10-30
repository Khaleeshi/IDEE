<?php

namespace App\Notifications;

use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Étape d'une demande de devis ou d'une prestation (SE-02).
 */
class QuoteNotice extends Notification
{
    use Queueable;

    public function __construct(
        public readonly QuoteRequest $quote,
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
            'quote_request_id' => $this->quote->id,
            'message' => $this->text,
        ];
    }
}
