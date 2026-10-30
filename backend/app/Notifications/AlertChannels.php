<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Channels\WhatsAppChannel;

/**
 * Canaux d'une alerte : toujours dans IDEE, et sur WhatsApp quand le canal
 * est activé et que le membre ne l'a pas refusé (NO-02).
 */
final class AlertChannels
{
    /**
     * @return list<string>
     */
    public static function for(object $notifiable): array
    {
        $whatsapp = config('idee.notifications.whatsapp')
            && $notifiable instanceof User
            && ($notifiable->contact_preferences['whatsapp_alerts'] ?? true);

        return $whatsapp ? ['database', WhatsAppChannel::class] : ['database'];
    }
}
