<?php

namespace App\Services\Messaging;

use App\Contracts\CodeSender;
use App\Exceptions\CodeDeliveryFailed;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Envoi du code par SMS avec Twilio (API Messages), pour les membres qui ont
 * payé leur SMS (CP-07) : numéros du Sénégal et de l'étranger.
 */
class TwilioSmsCodeSender implements CodeSender
{
    public function send(string $phone, string $code): void
    {
        $this->sendText($phone, OrangeSmsCodeSender::message($code));
    }

    /**
     * @throws CodeDeliveryFailed
     */
    public function sendText(string $phone, string $text): void
    {
        $config = config('services.twilio');

        if (blank($config['account_sid']) || blank($config['auth_token']) || (blank($config['from']) && blank($config['messaging_service_sid']))) {
            throw new CodeDeliveryFailed('Les accès à Twilio ne sont pas configurés.');
        }

        $message = ['To' => $phone, 'Body' => $text];
        if (filled($config['messaging_service_sid'])) {
            $message['MessagingServiceSid'] = $config['messaging_service_sid'];
        } else {
            $message['From'] = $config['from'];
        }

        try {
            $response = Http::baseUrl($config['base_url'])
                ->withBasicAuth($config['account_sid'], $config['auth_token'])
                ->asForm()
                ->acceptJson()
                ->timeout(10)
                ->post('/2010-04-01/Accounts/'.rawurlencode($config['account_sid']).'/Messages.json', $message);
        } catch (ConnectionException $exception) {
            throw new CodeDeliveryFailed('Twilio injoignable.', previous: $exception);
        }

        if ($response->failed() || in_array($response->json('status'), ['failed', 'undelivered'], true)) {
            throw new CodeDeliveryFailed("Refus de Twilio (HTTP {$response->status()}, code {$response->json('code')}).");
        }
    }
}
