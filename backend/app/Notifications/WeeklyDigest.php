<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Résumé hebdomadaire par e-mail (NO-04).
 */
class WeeklyDigest extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $summary
     */
    public function __construct(public readonly array $summary) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $summary = $this->summary;
        $mail = (new MailMessage)
            ->subject('Votre semaine sur IDEE')
            ->greeting('Bonjour '.$notifiable->name.',');

        if ($summary['pending_requests'] > 0) {
            $mail->line($summary['pending_requests'].' demande'.($summary['pending_requests'] > 1 ? 's attendent' : ' attend').' votre réponse. Une réponse, même « pas maintenant », aide l\'expéditeur.');
        }

        if ($summary['unread_messages'] > 0) {
            $mail->line($summary['unread_messages'].' message'.($summary['unread_messages'] > 1 ? 's non lus' : ' non lu').' dans vos conversations.');
        }

        foreach ($summary['alerts'] as $alert) {
            $mail->line('Alerte « '.$alert['label'].' » : '.$alert['count'].' nouveau'.($alert['count'] > 1 ? 'x projets' : ' projet').' cette semaine — '.$alert['url']);
        }

        foreach ($summary['deadlines'] as $deadline) {
            $mail->line('Votre candidature « '.$deadline['title'].' » : date limite le '.$deadline['deadline'].' — '.$deadline['url']);
        }

        foreach ($summary['opportunities'] as $opportunity) {
            $mail->line('Pour « '.$opportunity['project'].' » : « '.$opportunity['title'].' », date limite le '.$opportunity['deadline'].' — '.$opportunity['url']);
        }

        return $mail
            ->action('Ouvrir IDEE', route('home'))
            ->line('Vous pouvez arrêter ce résumé dans votre profil : '.route('profile.edit'))
            ->salutation('L\'équipe IDEE');
    }
}
