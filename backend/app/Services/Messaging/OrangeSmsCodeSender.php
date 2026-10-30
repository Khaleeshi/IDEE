<?php

namespace App\Services\Messaging;

use App\Contracts\CodeSender;
use App\Exceptions\CodeDeliveryFailed;
use App\Support\PhoneNumber;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Envoi du code par l'API SMS d'Orange Sénégal. Elle ne dessert que les
 * numéros sénégalais : les autres passent par WhatsApp.
 */
class OrangeSmsCodeSender implements CodeSender
{
    private const TOKEN_CACHE_KEY = 'orange_sms_token';

    public function send(string $phone, string $code): void
    {
        if (! str_starts_with($phone, PhoneNumber::SENEGAL_PREFIX)) {
            throw new CodeDeliveryFailed('Le SMS Orange ne dessert que les numéros sénégalais.');
        }

        $config = config('services.orange_sms');

        if (blank($config['client_id']) || blank($config['client_secret']) || blank($config['sender'])) {
            throw new CodeDeliveryFailed('Les accès à l\'API SMS d\'Orange ne sont pas configurés.');
        }

        $request = [
            'address' => 'tel:'.$phone,
            'senderAddress' => $config['sender'],
            'outboundSMSTextMessage' => ['message' => self::message($code)],
        ];

        if (filled($config['sender_name'])) {
            $request['senderName'] = $config['sender_name'];
        }

        try {
            $response = Http::baseUrl($config['base_url'])
                ->withToken($this->token($config))
                ->acceptJson()
                ->timeout(10)
                ->post('/smsmessaging/v1/outbound/'.rawurlencode($config['sender']).'/requests', [
                    'outboundSMSMessageRequest' => $request,
                ]);
        } catch (ConnectionException $exception) {
            throw new CodeDeliveryFailed('API SMS d\'Orange injoignable.', previous: $exception);
        }

        if ($response->failed()) {
            if ($response->status() === 401) {
                Cache::forget(self::TOKEN_CACHE_KEY);
            }

            throw new CodeDeliveryFailed("Refus de l'API SMS d'Orange (HTTP {$response->status()}).");
        }
    }

    public static function message(string $code): string
    {
        return "IDEE : votre code de connexion est {$code}. Il expire dans "
            .config('idee.otp.ttl_minutes').' minutes. Ne le communiquez à personne.';
    }

    /**
     * Jeton OAuth d'Orange, gardé en cache jusqu'à peu avant son expiration.
     *
     * @param  array<string, mixed>  $config
     */
    private function token(array $config): string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);

        if (is_string($cached)) {
            return $cached;
        }

        try {
            $response = Http::baseUrl($config['base_url'])
                ->withBasicAuth($config['client_id'], $config['client_secret'])
                ->acceptJson()
                ->asForm()
                ->timeout(10)
                ->post('/oauth/v3/token', ['grant_type' => 'client_credentials']);
        } catch (ConnectionException $exception) {
            throw new CodeDeliveryFailed('Authentification Orange injoignable.', previous: $exception);
        }

        if ($response->failed() || ! is_string($response->json('access_token'))) {
            throw new CodeDeliveryFailed("Authentification Orange refusée (HTTP {$response->status()}).");
        }

        $token = $response->json('access_token');
        Cache::put(self::TOKEN_CACHE_KEY, $token, max(60, (int) $response->json('expires_in', 3600) - 60));

        return $token;
    }
}
