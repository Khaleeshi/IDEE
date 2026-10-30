<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Lien de confirmation d'une adresse e-mail ajoutée au profil (CF-01).
 */
class VerifyEmailAddress extends Notification
{
    use Queueable;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute('email.verify', now()->addDay(), [
            'user' => $notifiable->getKey(),
            'hash' => sha1($notifiable->email),
        ]);

        return (new MailMessage)
            ->subject('Confirmez votre adresse e-mail sur IDEE')
            ->greeting("Bonjour {$notifiable->name},")
            ->line('Confirmez cette adresse pour pouvoir vous connecter à IDEE avec elle.')
            ->action('Confirmer mon adresse', $url)
            ->line('Ce lien est valable 24 heures. Si vous n\'avez rien demandé, ignorez ce message.')
            ->salutation('L\'équipe IDEE');
    }
}
