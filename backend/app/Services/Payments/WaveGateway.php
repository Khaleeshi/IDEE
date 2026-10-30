<?php

namespace App\Services\Payments;

use App\Exceptions\PaymentUnavailable;
use App\Models\Payment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Paiement par Wave Business, API Checkout (PA-01).
 *
 * Une session de paiement est créée avec le montant et la référence de
 * l'achat ; le membre paie dans Wave puis revient sur IDEE. Le statut est
 * toujours relu auprès de Wave avant d'ouvrir les droits : un appel entrant
 * ne suffit jamais à valider un paiement.
 */
class WaveGateway
{
    public function isConfigured(): bool
    {
        return (bool) config('idee.payments.wave') && filled(config('services.wave.key'));
    }

    /**
     * @return array{id: string, launch_url: string}
     *
     * @throws PaymentUnavailable
     */
    public function createSession(Payment $payment): array
    {
        $response = $this->send(fn (PendingRequest $http) => $http->post('/v1/checkout/sessions', [
            'amount' => (string) $payment->amount_fcfa,
            'currency' => 'XOF',
            'client_reference' => $payment->id,
            'success_url' => route('payments.return', $payment),
            'error_url' => route('payments.return', $payment),
        ]));

        if (blank($response['id'] ?? null) || blank($response['wave_launch_url'] ?? null)) {
            throw new PaymentUnavailable('Wave n\'a pas ouvert la session de paiement. Réessayez dans un instant.');
        }

        return ['id' => $response['id'], 'launch_url' => $response['wave_launch_url']];
    }

    /**
     * Statut de la session : « paid », « failed » ou « pending ».
     *
     * @throws PaymentUnavailable
     */
    public function status(string $sessionId): string
    {
        $session = $this->send(fn (PendingRequest $http) => $http->get('/v1/checkout/sessions/'.rawurlencode($sessionId)));

        return match (true) {
            ($session['payment_status'] ?? null) === 'succeeded' => 'paid',
            in_array($session['payment_status'] ?? null, ['cancelled', 'failed'], true),
            ($session['checkout_status'] ?? null) === 'expired' => 'failed',
            default => 'pending',
        };
    }

    /**
     * @param  callable(PendingRequest): Response  $call
     * @return array<string, mixed>
     *
     * @throws PaymentUnavailable
     */
    private function send(callable $call): array
    {
        try {
            $response = $call(Http::baseUrl(config('services.wave.base_url'))
                ->withToken((string) config('services.wave.key'))
                ->acceptJson()
                ->timeout(15));
        } catch (ConnectionException $exception) {
            Log::warning('Wave injoignable.', ['message' => $exception->getMessage()]);

            throw new PaymentUnavailable('Wave est injoignable pour le moment. Réessayez plus tard.', previous: $exception);
        }

        if ($response->failed()) {
            Log::error('Wave a refusé la demande.', ['status' => $response->status(), 'body' => $response->json('message')]);

            throw new PaymentUnavailable('Wave n\'a pas pu traiter le paiement. Réessayez plus tard.');
        }

        return (array) $response->json();
    }
}
