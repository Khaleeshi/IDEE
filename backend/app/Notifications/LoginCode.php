<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Code de connexion envoyé par e-mail (CP-01).
 */
class LoginCode extends Notification
{
    use Queueable;

    public function __construct(public readonly string $code) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Votre code de connexion IDEE : {$this->code}")
            ->greeting('Bonjour,')
            ->line("Votre code de connexion est {$this->code}.")
            ->line('Il expire dans '.config('idee.otp.ttl_minutes').' minutes. Ne le communiquez à personne.')
            ->salutation('L\'équipe IDEE');
    }
}
