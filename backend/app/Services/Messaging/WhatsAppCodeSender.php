<?php

namespace App\Services\Messaging;

use App\Contracts\CodeSender;
use App\Exceptions\CodeDeliveryFailed;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Envoi du code par WhatsApp, avec un modèle de message de catégorie
 * « authentification » validé par Meta (bouton « Copier le code »).
 */
class WhatsAppCodeSender implements CodeSender
{
    public function send(string $phone, string $code): void
    {
        $config = config('services.whatsapp');

        if (blank($config['token']) || blank($config['phone_number_id'])) {
            throw new CodeDeliveryFailed('Les accès à WhatsApp ne sont pas configurés.');
        }

        try {
            $response = Http::baseUrl('https://graph.facebook.com/'.$config['graph_version'])
                ->withToken($config['token'])
                ->acceptJson()
                ->timeout(10)
                ->post("/{$config['phone_number_id']}/messages", [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => ltrim($phone, '+'),
                    'type' => 'template',
                    'template' => [
                        'name' => $config['template'],
                        'language' => ['code' => $config['language']],
                        'components' => [
                            ['type' => 'body', 'parameters' => [['type' => 'text', 'text' => $code]]],
                            ['type' => 'button', 'sub_type' => 'url', 'index' => '0', 'parameters' => [['type' => 'text', 'text' => $code]]],
                        ],
                    ],
                ]);
        } catch (ConnectionException $exception) {
            throw new CodeDeliveryFailed('WhatsApp injoignable.', previous: $exception);
        }

        if ($response->failed()) {
            throw new CodeDeliveryFailed('Refus de WhatsApp (HTTP '.$response->status().') : '.$response->json('error.message', 'raison inconnue'));
        }
    }
}
