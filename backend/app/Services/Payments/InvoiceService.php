<?php

namespace App\Services\Payments;

use App\Enums\InvoiceStatus;
use App\Enums\OrganisationRole;
use App\Models\Invoice;
use App\Models\Organisation;
use App\Models\OrganisationMember;
use App\Notifications\OrganisationNotice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Factures des contrats « espace organisations », réglés par virement ou
 * mobile money hors de la plateforme, puis marqués payés par l'équipe (PA-03).
 */
class InvoiceService
{
    public function issue(Organisation $organisation, string $label, int $amount, ?Carbon $dueOn = null): Invoice
    {
        $invoice = DB::transaction(function () use ($organisation, $label, $amount, $dueOn) {
            $prefix = 'FAC-'.now()->year.'-';
            $sequence = Invoice::where('number', 'like', $prefix.'%')->lockForUpdate()->count() + 1;

            $invoice = new Invoice(['label' => trim($label), 'amount_fcfa' => $amount, 'due_on' => $dueOn]);
            $invoice->organisation()->associate($organisation);
            $invoice->forceFill(['number' => $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT), 'status' => InvoiceStatus::Emise])->save();

            return $invoice;
        });

        $organisation->members()->with('user')->where('role', OrganisationRole::Administrateur)->get()
            ->each(fn (OrganisationMember $member) => $member->user->notify(new OrganisationNotice(
                $organisation,
                'Nouvelle facture '.$invoice->number.' pour « '.$organisation->name.' » : '.number_format($amount, 0, ',', "\u{00A0}").' FCFA.',
            )));

        return $invoice;
    }

    /**
     * @throws ValidationException
     */
    public function settle(Invoice $invoice, bool $paid): Invoice
    {
        if ($invoice->status !== InvoiceStatus::Emise) {
            throw ValidationException::withMessages(['invoice' => 'Cette facture a déjà été réglée ou annulée.']);
        }

        $invoice->forceFill([
            'status' => $paid ? InvoiceStatus::Payee : InvoiceStatus::Annulee,
            'paid_at' => $paid ? now() : null,
        ])->save();

        return $invoice;
    }
}
