<?php

namespace App\Notifications;

use App\Models\OpportunityApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * La date limite d'une candidature en préparation approche (OP-03).
 */
class ApplicationDeadline extends Notification
{
    use Queueable;

    public function __construct(
        public readonly OpportunityApplication $application,
        public readonly int $daysLeft,
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
        $opportunity = $this->application->opportunity;
        $when = match ($this->daysLeft) {
            0 => 'C\'est aujourd\'hui le dernier jour',
            1 => 'Plus qu\'un jour',
            default => "Plus que {$this->daysLeft} jours",
        };

        return [
            'opportunity_id' => $opportunity->id,
            'message' => $when.' pour déposer votre candidature « '.$opportunity->title.' » (date limite le '.$opportunity->deadline->translatedFormat('d F').').',
        ];
    }
}
