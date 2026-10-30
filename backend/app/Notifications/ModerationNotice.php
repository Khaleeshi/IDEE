<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Informe la personne visée d'une décision de modération et de son motif,
 * puis de l'issue de son recours (section 8).
 */
class ModerationNotice extends Notification
{
    use Queueable;

    public function __construct(public readonly Report $report) {}

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
        $message = $this->report->appeal_decided_at
            ? ($this->report->appeal_upheld ? 'Votre recours a été examiné : la décision est maintenue.' : 'Votre recours a été accepté : la décision est annulée.')
            : 'Décision de modération : '.$this->report->decision->label().'. Motif : '.$this->report->reason->label().'.';

        return ['report_id' => $this->report->id, 'message' => $message];
    }
}
