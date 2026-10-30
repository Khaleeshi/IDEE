<?php

namespace App\Notifications\Channels;

use App\Models\User;
use App\Services\Notifications\PushService;
use App\Support\NotificationLink;
use Illuminate\Notifications\Notification;

/**
 * Canal des notifications push du navigateur (NO-01).
 */
class WebPushChannel
{
    public function __construct(private readonly PushService $push) {}

    public function send(User $notifiable, Notification $notification): void
    {
        $data = $notification->toArray($notifiable);

        if (filled($data['message'] ?? null)) {
            $this->push->send($notifiable, $data['message'], NotificationLink::for($data));
        }
    }
}
