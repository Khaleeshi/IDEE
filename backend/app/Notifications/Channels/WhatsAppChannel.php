<?php

namespace App\Notifications\Channels;

use App\Exceptions\CodeDeliveryFailed;
use App\Models\User;
use App\Notifications\CriticalAlert;
use App\Services\Messaging\OrangeSmsCodeSender;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Alertes WhatsApp pour les demandes et les rappels (NO-02).
 *
 * Envoie le modèle de message « utilitaire » configuré, avec le texte de la
 * notification comme unique variable. Un échec est journalisé sans bloquer :
 * la notification reste visible dans IDEE, et une alerte critique part en
 * SMS de secours si celui-ci est activé (NO-03).
 */
class WhatsAppChannel
{
    public function __construct(private readonly OrangeSmsCodeSender $sms) {}

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
            $this->smsFallback($notifiable, $notification, $text);
        }
    }

    /**
     * SMS de secours pour les alertes critiques (NO-03), quand il est activé.
     */
    private function smsFallback(User $notifiable, Notification $notification, string $text): void
    {
        if (! $notification instanceof CriticalAlert || ! config('idee.notifications.sms_fallback')) {
            return;
        }

        try {
            $this->sms->sendText($notifiable->phone, 'IDEE : '.mb_substr($text, 0, 300));
        } catch (CodeDeliveryFailed $exception) {
            Log::warning('SMS de secours non envoyé.', ['raison' => $exception->getMessage()]);
        }
    }
}
