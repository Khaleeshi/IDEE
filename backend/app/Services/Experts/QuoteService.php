<?php

namespace App\Services\Experts;

use App\Enums\QuoteStatus;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Notifications\QuoteNotice;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Demande de devis à un expert vérifié et suivi de la prestation (SE-02).
 * Le prix est réglé directement entre les deux : rien ne transite par IDEE.
 */
class QuoteService
{
    /**
     * @throws ValidationException
     */
    public function request(User $client, User $expert, string $description, ?Project $project = null, ?string $budget = null, ?Carbon $wantedBy = null): QuoteRequest
    {
        if ($client->is($expert)) {
            throw ValidationException::withMessages(['description' => 'Vous ne pouvez pas vous demander un devis.']);
        }

        // RG-11 : seul un expert à l'identité vérifiée reçoit des demandes de devis.
        if (! $expert->hasRole(UserRole::Expert) || ! $expert->hasVerifiedIdentity()) {
            throw ValidationException::withMessages(['description' => 'Ce membre n\'est pas un expert vérifié.']);
        }

        if ($project !== null && ! $project->isOwnedBy($client)) {
            throw ValidationException::withMessages(['project_id' => 'Choisissez l\'un de vos projets.']);
        }

        $quote = new QuoteRequest(['description' => trim($description), 'budget' => filled($budget) ? trim($budget) : null, 'wanted_by' => $wantedBy]);
        $quote->client()->associate($client);
        $quote->expert()->associate($expert);
        $quote->project()->associate($project);
        $quote->status = QuoteStatus::Demandee;
        $quote->save();

        $expert->notify(new QuoteNotice($quote, $client->name.' vous demande un devis'.($project ? ' pour « '.$project->title.' »' : '').'.'));

        return $quote;
    }

    /**
     * L'expert répond par un devis : montant, délai, contenu.
     *
     * @throws ValidationException
     */
    public function sendQuote(QuoteRequest $quote, int $amount, string $delay, string $details): QuoteRequest
    {
        $this->ensureStatus($quote, [QuoteStatus::Demandee, QuoteStatus::DevisEnvoye]);

        $quote->forceFill([
            'quote_amount_fcfa' => $amount,
            'quote_delay' => trim($delay),
            'quote_details' => trim($details),
            'quoted_at' => now(),
            'status' => QuoteStatus::DevisEnvoye,
        ])->save();

        $quote->client->notify(new QuoteNotice($quote, $quote->expert->name.' vous a envoyé un devis de '.number_format($amount, 0, ',', "\u{00A0}").' FCFA.'));

        return $quote;
    }

    /**
     * @throws ValidationException
     */
    public function answer(QuoteRequest $quote, bool $accepted): QuoteRequest
    {
        $this->ensureStatus($quote, [QuoteStatus::DevisEnvoye]);

        $quote->forceFill([
            'status' => $accepted ? QuoteStatus::Acceptee : QuoteStatus::Refusee,
            'accepted_at' => $accepted ? now() : null,
        ])->save();

        $quote->expert->notify(new QuoteNotice($quote, $quote->client->name.($accepted ? ' a accepté' : ' a refusé').' votre devis.'));

        return $quote;
    }

    /**
     * @throws ValidationException
     */
    public function deliver(QuoteRequest $quote): QuoteRequest
    {
        $this->ensureStatus($quote, [QuoteStatus::Acceptee]);

        $quote->forceFill(['status' => QuoteStatus::Livree, 'delivered_at' => now()])->save();
        $quote->client->notify(new QuoteNotice($quote, $quote->expert->name.' indique avoir livré la prestation. Confirmez-le si c\'est le cas.'));

        return $quote;
    }

    /**
     * Le client confirme la fin de la prestation.
     *
     * @throws ValidationException
     */
    public function complete(QuoteRequest $quote): QuoteRequest
    {
        $this->ensureStatus($quote, [QuoteStatus::Livree]);

        $quote->forceFill(['status' => QuoteStatus::Terminee, 'completed_at' => now()])->save();
        $quote->expert->notify(new QuoteNotice($quote, $quote->client->name.' a confirmé la fin de la prestation.'));

        return $quote;
    }

    /**
     * @throws ValidationException
     */
    public function cancel(QuoteRequest $quote, User $by): QuoteRequest
    {
        $this->ensureStatus($quote, [QuoteStatus::Demandee, QuoteStatus::DevisEnvoye, QuoteStatus::Acceptee]);

        $quote->forceFill(['status' => QuoteStatus::Annulee])->save();
        $other = $by->is($quote->client) ? $quote->expert : $quote->client;
        $other->notify(new QuoteNotice($quote, $by->name.' a annulé la demande de devis.'));

        return $quote;
    }

    /**
     * @param  list<QuoteStatus>  $allowed
     *
     * @throws ValidationException
     */
    private function ensureStatus(QuoteRequest $quote, array $allowed): void
    {
        if (! in_array($quote->status, $allowed, true)) {
            throw ValidationException::withMessages(['quote' => 'Cette action n\'est plus possible à cette étape.']);
        }
    }
}
