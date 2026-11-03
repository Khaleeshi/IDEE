<?php

namespace App\Services\Auth;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentUnavailable;
use App\Models\SmsPurchase;
use App\Services\Payments\BictorysGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * SMS de connexion payé d'avance (CP-07). WhatsApp et l'e-mail restent
 * gratuits ; le SMS coûte cher à IDEE, il est donc payé avant l'envoi. Comme
 * personne ne reçoit de SMS sans avoir payé, les demandes en masse destinées à
 * gonfler la facture n'ont plus d'intérêt.
 */
class SmsPurchaseService
{
    public function __construct(private readonly BictorysGateway $bictorys) {}

    public function isAvailable(): bool
    {
        return (bool) config('idee.otp.paid_sms') && $this->bictorys->isConfigured();
    }

    public function price(): int
    {
        return config('idee.otp.sms_price_fcfa');
    }

    /**
     * @return list<PaymentMethod>
     */
    public function methods(): array
    {
        return $this->isAvailable() ? PaymentMethod::cases() : [];
    }

    /**
     * Ouvre le paiement du SMS chez Bictorys.
     *
     * @throws ValidationException
     */
    public function start(string $phone, PaymentMethod $method): SmsPurchase
    {
        if (! $this->isAvailable()) {
            throw ValidationException::withMessages(['channel' => 'La réception du code par SMS n\'est pas disponible. Choisissez WhatsApp ou l\'e-mail.']);
        }

        $purchase = new SmsPurchase;
        $purchase->forceFill([
            'phone' => $phone,
            'amount_fcfa' => $this->price(),
            'method' => $method,
            'status' => PaymentStatus::EnAttente,
            'sends_remaining' => config('idee.otp.sms_sends_per_purchase'),
        ])->save();

        try {
            $charge = $this->bictorys->createCharge($purchase->id, $purchase->amount_fcfa, $method, route('login.sms.show', $purchase), ['phone' => $phone]);
        } catch (PaymentUnavailable $exception) {
            $purchase->forceFill(['status' => PaymentStatus::Echouee])->save();

            throw ValidationException::withMessages(['method' => $exception->getMessage()]);
        }

        $purchase->forceFill([
            'provider_reference' => $charge['id'],
            'launch_url' => $charge['link'],
            'instructions' => $charge['instructions'],
        ])->save();

        return $purchase;
    }

    /**
     * Relit le statut auprès de Bictorys.
     */
    public function refresh(SmsPurchase $purchase): SmsPurchase
    {
        if ($purchase->status !== PaymentStatus::EnAttente || blank($purchase->provider_reference)) {
            return $purchase;
        }

        try {
            $status = $this->bictorys->status($purchase->provider_reference);
        } catch (PaymentUnavailable) {
            return $purchase;
        }

        if ($status === 'paid') {
            DB::transaction(function () use ($purchase) {
                $locked = SmsPurchase::lockForUpdate()->findOrFail($purchase->id);
                if ($locked->isPaid()) {
                    return;
                }

                $prefix = 'SMS-'.now()->year.'-';
                $sequence = SmsPurchase::where('receipt_number', 'like', $prefix.'%')->lockForUpdate()->count() + 1;
                $locked->forceFill([
                    'status' => PaymentStatus::Payee,
                    'paid_at' => now(),
                    'receipt_number' => $prefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
                ])->save();
            });
        } elseif ($status === 'failed') {
            $purchase->forceFill(['status' => PaymentStatus::Echouee])->save();
        }

        return $purchase->refresh();
    }

    /**
     * Achat utilisable pour ce numéro, relu auprès de Bictorys si besoin.
     *
     * @throws ValidationException
     */
    public function usableFor(string $phone, ?string $purchaseId): SmsPurchase
    {
        $purchase = $purchaseId ? SmsPurchase::where('phone', $phone)->find($purchaseId) : null;

        if ($purchase === null) {
            throw ValidationException::withMessages(['channel' => 'Payez d\'abord votre SMS ('.$this->price().' FCFA), ou choisissez WhatsApp ou l\'e-mail, gratuits.']);
        }

        $purchase = $this->refresh($purchase);

        if (! $purchase->isPaid()) {
            throw ValidationException::withMessages(['channel' => 'Votre paiement n\'est pas encore confirmé. Terminez-le dans Wave, Orange Money ou sur la page de paiement, puis réessayez.']);
        }

        if (! $purchase->isUsable()) {
            throw ValidationException::withMessages(['channel' => 'Ce paiement a déjà servi pour tous ses envois. Choisissez WhatsApp ou l\'e-mail, ou payez un nouveau SMS.']);
        }

        return $purchase;
    }

    /**
     * Un SMS est parti : un envoi de moins sur cet achat.
     */
    public function consume(SmsPurchase $purchase): void
    {
        SmsPurchase::whereKey($purchase->id)->where('sends_remaining', '>', 0)->decrement('sends_remaining');
    }

    /**
     * Relit les achats en attente et fait expirer les plus anciens.
     */
    public function sweep(): int
    {
        $expired = 0;

        SmsPurchase::where('status', PaymentStatus::EnAttente)->each(function (SmsPurchase $purchase) use (&$expired) {
            $purchase = $this->refresh($purchase);

            if ($purchase->status === PaymentStatus::EnAttente && $purchase->created_at->lt(now()->subHours(config('idee.payments.expire_after_hours')))) {
                $purchase->forceFill(['status' => PaymentStatus::Expiree])->save();
                $expired++;
            }
        });

        return $expired;
    }
}
