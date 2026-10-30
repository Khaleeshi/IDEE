<?php

namespace App\Notifications;

use App\Models\StepProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Rappel fixé par un membre sur une étape de parcours (PG-05).
 * Il est enregistré en base et affiché dans « Parcours » ; les canaux
 * push et WhatsApp viendront s'ajouter.
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
        return ['database'];
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
