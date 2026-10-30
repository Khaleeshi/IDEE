<?php

namespace App\Notifications;

use App\Models\DocumentAccess;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Un membre demande à consulter les documents privés d'un projet (RG-05).
 */
class DocumentAccessRequested extends Notification
{
    use Queueable;

    public function __construct(public readonly DocumentAccess $access) {}

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
            'project_id' => $this->access->project_id,
            'message' => $this->access->user->name.' demande à consulter les documents de « '.$this->access->project->title.' ».',
        ];
    }
}
