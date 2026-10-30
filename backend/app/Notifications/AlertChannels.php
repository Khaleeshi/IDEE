<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Channels\WebPushChannel;
use App\Notifications\Channels\WhatsAppChannel;
use App\Services\Notifications\PushService;

/**
 * Canaux d'une alerte : toujours dans IDEE ; en notification push sur les
 * appareils que le membre a abonnés (NO-01) ; sur WhatsApp quand le canal
 * est activé et que le membre ne l'a pas refusé (NO-02).
 */
final class AlertChannels
{
    /**
     * @return list<string>
     */
    public static function for(object $notifiable, bool $whatsapp = true): array
    {
        $channels = ['database'];

        if (! $notifiable instanceof User) {
            return $channels;
        }

        if (app(PushService::class)->isEnabled() && $notifiable->pushSubscriptions()->exists()) {
            $channels[] = WebPushChannel::class;
        }

        if ($whatsapp && config('idee.notifications.whatsapp') && ($notifiable->contact_preferences['whatsapp_alerts'] ?? true)) {
            $channels[] = WhatsAppChannel::class;
        }

        return $channels;
    }
}
