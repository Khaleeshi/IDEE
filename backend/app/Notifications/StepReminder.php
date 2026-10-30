<?php

namespace App\Notifications;

use App\Models\StepProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Rappel fixé par un membre sur une étape de parcours (PG-05).
 * Il est affiché dans IDEE et envoyé sur WhatsApp quand le canal est activé (NO-02).
 */
class StepReminder extends Notification
{
    use Queueable;

    public function __construct(public readonly StepProgress $progress) {}

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
            'journey_step_id' => $this->progress->journey_step_id,
            'message' => 'Rappel : « '.$this->progress->step->title.' » ('.$this->progress->step->journey->title.').',
        ];
    }
}
