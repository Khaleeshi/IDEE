<?php

namespace App\Notifications;

use App\Models\OpportunityApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Rappel fixé par un membre sur une candidature déposée auprès d'un guichet.
 */
class ApplicationReminder extends Notification
{
    use Queueable;

    public function __construct(public readonly OpportunityApplication $application) {}

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
            'opportunity_id' => $this->application->opportunity_id,
            'message' => 'Rappel : où en est votre candidature « '.$this->application->opportunity->title.' » ('.$this->application->opportunity->organisation.') ?',
        ];
    }
}
