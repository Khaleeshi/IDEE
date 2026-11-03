<?php

namespace App\Notifications;

use App\Models\ProjectMember;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Invitation à rejoindre l'équipe d'un projet, et sa réponse (EQ-01).
 */
class TeamInvitation extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ProjectMember $member,
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
            'project_member_id' => $this->member->id,
            'message' => $this->text,
        ];
    }
}
