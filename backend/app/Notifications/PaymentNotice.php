<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Paiement confirmé ou refusé (PA-01), avec le lien vers le reçu.
 */
class PaymentNotice extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Payment $payment,
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
            'payment_id' => $this->payment->id,
            'message' => $this->text,
        ];
    }
}
