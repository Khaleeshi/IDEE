<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Proposition ou réponse de rapprochement entre deux projets (PR-08).
 * Mène à la page des rapprochements du projet du destinataire.
 */
class ProjectMatchNotice extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Project $project,
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
            'matching_project_id' => $this->project->id,
            'message' => $this->text,
        ];
    }
}
