<?php

namespace App\Notifications\Channels;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Alertes WhatsApp pour les demandes et les rappels (NO-02).
 *
 * Envoie le modèle de message « utilitaire » configuré, avec le texte de la
 * notification comme unique variable. Un échec est journalisé sans bloquer :
 * la notification reste visible dans IDEE.
 */
class WhatsAppChannel
{
    public function send(User $notifiable, Notification $notification): void
    {
        $config = config('services.whatsapp');

        if (blank($config['token']) || blank($config['phone_number_id']) || blank($notifiable->phone)) {
            return;
        }

        $text = $notification->toArray($notifiable)['message'] ?? null;

        if (blank($text)) {
            return;
        }

        $response = Http::baseUrl('https://graph.facebook.com/'.$config['graph_version'])
            ->withToken($config['token'])
            ->timeout(10)
            ->post($config['phone_number_id'].'/messages', [
                'messaging_product' => 'whatsapp',
                'to' => ltrim($notifiable->phone, '+'),
                'type' => 'template',
                'template' => [
                    'name' => $config['notification_template'],
                    'language' => ['code' => $config['language']],
                    'components' => [[
                        'type' => 'body',
                        'parameters' => [['type' => 'text', 'text' => mb_substr($text, 0, 1000)]],
                    ]],
                ],
            ]);

        if ($response->failed()) {
            Log::warning('Alerte WhatsApp non envoyée.', ['status' => $response->status(), 'notification' => $notification::class]);
        }
    }
}
