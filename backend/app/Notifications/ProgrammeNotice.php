<?php

namespace App\Notifications;

use App\Models\ProgrammeApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Candidature reçue par une organisation, ou décision transmise au porteur (OR-02).
 */
class ProgrammeNotice extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ProgrammeApplication $application,
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
            'programme_application_id' => $this->application->id,
            'message' => $this->text,
        ];
    }
}
