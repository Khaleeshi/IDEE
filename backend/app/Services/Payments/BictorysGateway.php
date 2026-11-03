<?php

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Exceptions\PaymentUnavailable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Paiement par Bictorys (PA-04) : Wave, Orange Money et carte bancaire par
 * une seule API (docs.bictorys.com).
 *
 * Un paiement est créé avec notre référence ; le membre paie dans Wave, par le
 * code USSD d'Orange Money ou sur la page de paiement par carte. Le statut est
 * toujours relu auprès de Bictorys avant d'ouvrir un droit : un avis entrant
 * ne suffit jamais.
 */
class BictorysGateway
{
    public function isConfigured(): bool
    {
        return (bool) config('idee.payments.bictorys') && filled(config('services.bictorys.public_key'));
    }

    /**
     * Crée le paiement chez Bictorys.
     *
     * @param  array{name?: string|null, phone?: string|null, email?: string|null}  $customer
     * @return array{id: string, link: ?string, instructions: ?string}
     *
     * @throws PaymentUnavailable
     */
    public function createCharge(string $reference, int $amountFcfa, PaymentMethod $method, string $returnUrl, array $customer = []): array
    {
        $query = ['payment_type' => self::paymentType($method)];
        if ($method === PaymentMethod::Carte) {
            $query['payment_category'] = 'card';
        }

        $response = $this->send(fn (PendingRequest $http) => $http->post('/pay/v1/charges?'.http_build_query($query), array_filter([
            'amount' => $amountFcfa,
            'currency' => 'XOF',
            'country' => config('services.bictorys.country'),
            'paymentReference' => $reference,
            'successRedirectUrl' => $returnUrl,
            'ErrorRedirectUrl' => $returnUrl,
            'customerObject' => array_filter([...$customer, 'country' => config('services.bictorys.country')]) ?: null,
        ], fn ($value) => $value !== null)));

        $id = $response['transactionId'] ?? $response['id'] ?? null;
        $link = $response['link'] ?? $response['redirectUrl'] ?? null;

        if (blank($id) || (blank($link) && blank($response['message'] ?? null))) {
            throw new PaymentUnavailable('Bictorys n\'a pas ouvert le paiement. Réessayez dans un instant.');
        }

        return ['id' => (string) $id, 'link' => $link, 'instructions' => $response['message'] ?? null];
    }

    /**
     * Statut du paiement : « paid », « failed » ou « pending ».
     *
     * @throws PaymentUnavailable
     */
    public function status(string $transactionId): string
    {
        $response = $this->send(fn (PendingRequest $http) => $http->get('/pay/v1/transactions/'.rawurlencode($transactionId).'/status'));
        $status = strtolower((string) ($response['status'] ?? $response['state'] ?? $response['paymentStatus'] ?? ''));

        return match ($status) {
            'succeeded', 'successful', 'success', 'paid' => 'paid',
            'failed', 'cancelled', 'canceled', 'reversed', 'expired' => 'failed',
            default => 'pending',
        };
    }

    /**
     * Un avis de Bictorys porte le secret choisi dans le tableau de bord (en-tête X-Secret-Key).
     */
    public function isAuthentic(Request $request): bool
    {
        $secret = (string) config('services.bictorys.webhook_secret');

        return $secret !== '' && hash_equals($secret, (string) $request->header('X-Secret-Key'));
    }

    public static function paymentType(PaymentMethod $method): string
    {
        return match ($method) {
            PaymentMethod::Wave => 'wave_money',
            PaymentMethod::OrangeMoney => 'orange_money',
            PaymentMethod::Carte => 'card',
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
            $response = $call(Http::baseUrl(config('services.bictorys.base_url'))
                ->withHeaders(['X-Api-Key' => (string) config('services.bictorys.public_key')])
                ->acceptJson()
                ->asJson()
                ->timeout(15));
        } catch (ConnectionException $exception) {
            Log::warning('Bictorys injoignable.', ['message' => $exception->getMessage()]);

            throw new PaymentUnavailable('Le service de paiement est injoignable pour le moment. Réessayez plus tard.', previous: $exception);
        }

        if ($response->failed()) {
            Log::error('Bictorys a refusé la demande.', ['status' => $response->status(), 'body' => $response->json('message')]);

            throw new PaymentUnavailable('Le paiement n\'a pas pu être ouvert. Réessayez plus tard.');
        }

        return (array) $response->json();
    }
}
