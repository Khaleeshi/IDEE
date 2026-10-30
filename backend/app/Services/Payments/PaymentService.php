<?php

namespace App\Services\Payments;

use App\Enums\Offer;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProjectStatus;
use App\Exceptions\PaymentUnavailable;
use App\Models\Entitlement;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use App\Notifications\PaymentNotice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Achat des offres en packs prépayés (PA-01), mise en avant (PA-02) et
 * reçus (PA-03). IDEE n'encaisse que le prix de ses propres offres : jamais
 * d'argent entre membres, jamais de commission sur une levée de fonds.
 */
class PaymentService
{
    public function __construct(private readonly WaveGateway $wave) {}

    /**
     * Moyens de paiement configurés.
     *
     * @return list<PaymentMethod>
     */
    public function methods(): array
    {
        return array_values(array_filter([
            $this->wave->isConfigured() ? PaymentMethod::Wave : null,
            filled(config('idee.payments.orange_money_number')) ? PaymentMethod::OrangeMoney : null,
        ]));
    }

    public function isEnabled(): bool
    {
        return $this->methods() !== [];
    }

    /**
     * Ouvre un achat. Avec Wave, la session de paiement est créée tout de suite.
     *
     * @throws ValidationException
     */
    public function start(User $buyer, Offer $offer, PaymentMethod $method, ?Project $project = null, int $quantity = 1): Payment
    {
        if (! in_array($method, $this->methods(), true)) {
            throw ValidationException::withMessages(['method' => 'Ce moyen de paiement n\'est pas disponible.']);
        }

        if ($offer->isForProject()) {
            if ($project === null || ! $project->isOwnedBy($buyer) || $project->status !== ProjectStatus::Actif) {
                throw ValidationException::withMessages(['project_id' => 'Choisissez l\'un de vos projets actifs à mettre en avant.']);
            }

            if ($quantity < 1 || $quantity > config('idee.offers.sponsorise.max_weeks')) {
                throw ValidationException::withMessages(['quantity' => 'Une mise en avant dure de 1 à '.config('idee.offers.sponsorise.max_weeks').' semaines.']);
            }
        } else {
            $project = null;
            $quantity = 1;
        }

        $payment = new Payment;
        $payment->user()->associate($buyer);
        $payment->project()->associate($project);
        $payment->forceFill([
            'offer' => $offer,
            'quantity' => $quantity,
            'amount_fcfa' => $offer->price() * $quantity,
            'method' => $method,
            'status' => PaymentStatus::EnAttente,
        ])->save();

        if ($method === PaymentMethod::Wave) {
            try {
                $session = $this->wave->createSession($payment);
            } catch (PaymentUnavailable $exception) {
                $payment->forceFill(['status' => PaymentStatus::Echouee])->save();

                throw ValidationException::withMessages(['method' => $exception->getMessage()]);
            }

            $payment->forceFill(['provider_reference' => $session['id'], 'launch_url' => $session['launch_url']])->save();
        }

        return $payment;
    }

    /**
     * Orange Money : le membre indique l'identifiant de son transfert ; l'équipe le vérifie.
     *
     * @throws ValidationException
     */
    public function submitReference(Payment $payment, string $reference): Payment
    {
        if ($payment->method !== PaymentMethod::OrangeMoney || $payment->status !== PaymentStatus::EnAttente) {
            throw ValidationException::withMessages(['payer_reference' => 'Ce paiement n\'attend pas de référence.']);
        }

        $payment->forceFill(['payer_reference' => trim($reference)])->save();

        return $payment;
    }

    /**
     * Relit le statut d'un paiement Wave auprès de Wave.
     */
    public function refresh(Payment $payment): Payment
    {
        if ($payment->method !== PaymentMethod::Wave || $payment->status !== PaymentStatus::EnAttente || blank($payment->provider_reference)) {
            return $payment;
        }

        try {
            $status = $this->wave->status($payment->provider_reference);
        } catch (PaymentUnavailable) {
            return $payment;
        }

        if ($status === 'paid') {
            return $this->markPaid($payment);
        }

        if ($status === 'failed') {
            $payment->forceFill(['status' => PaymentStatus::Echouee])->save();
        }

        return $payment;
    }

    /**
     * Confirme le paiement, numérote le reçu et ouvre les droits.
     */
    public function markPaid(Payment $payment, ?User $reviewer = null): Payment
    {
        DB::transaction(function () use ($payment, $reviewer) {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);

            if ($payment->isPaid()) {
                return;
            }

            $prefix = 'REC-'.now()->year.'-';
            $sequence = Payment::where('receipt_number', 'like', $prefix.'%')->lockForUpdate()->count() + 1;

            $payment->forceFill([
                'status' => PaymentStatus::Payee,
                'paid_at' => now(),
                'receipt_number' => $prefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
                'reviewed_by' => $reviewer?->id,
            ])->save();

            $this->grant($payment);
        });

        $payment->refresh();
        $payment->user->notify(new PaymentNotice($payment, 'Paiement reçu : '.$payment->offer->label().'. Votre reçu '.$payment->receipt_number.' est disponible.'));

        return $payment;
    }

    /**
     * L'équipe ne retrouve pas le transfert Orange Money annoncé.
     *
     * @throws ValidationException
     */
    public function refuse(Payment $payment, User $reviewer): Payment
    {
        if ($payment->status !== PaymentStatus::EnAttente) {
            throw ValidationException::withMessages(['payment' => 'Ce paiement a déjà été traité.']);
        }

        $payment->forceFill(['status' => PaymentStatus::Refusee, 'reviewed_by' => $reviewer->id])->save();
        $payment->user->notify(new PaymentNotice($payment, 'Nous n\'avons pas retrouvé votre transfert pour « '.$payment->offer->label().' ». Écrivez-nous avec votre reçu Orange Money.'));

        return $payment;
    }

    /**
     * Relit les paiements Wave en attente et fait expirer les plus anciens.
     */
    public function sweep(): int
    {
        $expired = 0;

        Payment::where('status', PaymentStatus::EnAttente)->each(function (Payment $payment) use (&$expired) {
            $payment = $this->refresh($payment);
            $limit = now()->subHours(config('idee.payments.expire_after_hours'));

            // Un transfert Orange Money annoncé attend la vérification de l'équipe.
            if ($payment->status === PaymentStatus::EnAttente && $payment->created_at->lt($limit) && blank($payment->payer_reference)) {
                $payment->forceFill(['status' => PaymentStatus::Expiree])->save();
                $expired++;
            }
        });

        return $expired;
    }

    /**
     * Ouvre ou prolonge le droit acheté, à la suite d'une période en cours.
     */
    private function grant(Payment $payment): void
    {
        $current = Entitlement::query()
            ->whereBelongsTo($payment->user)
            ->where('offer', $payment->offer)
            ->where('project_id', $payment->project_id)
            ->where('ends_at', '>', now())
            ->max('ends_at');
        $start = $current ? max(now(), Carbon::parse($current)) : now();

        $entitlement = new Entitlement;
        $entitlement->user()->associate($payment->user_id);
        $entitlement->project()->associate($payment->project_id);
        $entitlement->forceFill([
            'offer' => $payment->offer,
            'payment_id' => $payment->id,
            'starts_at' => $start,
            'ends_at' => $start->copy()->addDays($payment->offer->days() * $payment->quantity),
        ])->save();
    }
}
