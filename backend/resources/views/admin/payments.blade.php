<x-layout title="Paiements">
    <h1 class="mb-3 text-2xl font-bold">Paiements</h1>
    <x-admin-nav />
    <p class="mb-3 text-sm text-stone-600">Encaissé ce mois-ci : <strong>{{ number_format($monthTotal, 0, ',', "\u{00A0}") }} FCFA</strong>.</p>

    <h2 class="mb-2 mt-4 text-lg font-bold">Transferts Orange Money à vérifier</h2>
    <p class="mb-2 text-sm text-stone-600">Retrouvez chaque transfert dans le compte Orange Money {{ $orangeMoneyNumber ?? '(numéro non configuré)' }} avant de confirmer.</p>
    @forelse ($pending as $payment)
        <x-card class="text-sm">
            <p class="font-semibold">{{ $payment->user->name }} · {{ number_format($payment->amount_fcfa, 0, ',', "\u{00A0}") }} FCFA</p>
            <p class="text-stone-500">{{ $payment->offer->label() }} · transfert <span class="font-mono">{{ $payment->payer_reference }}</span> · {{ $payment->created_at->diffForHumans() }}</p>
            <div class="mt-2 grid grid-cols-2 gap-2">
                <form method="POST" action="{{ route('admin.payments.decide', $payment) }}">
                    @csrf
                    <input type="hidden" name="approved" value="1">
                    <x-button class="w-full min-h-10 py-2 text-sm">Confirmer</x-button>
                </form>
                <form method="POST" action="{{ route('admin.payments.decide', $payment) }}">
                    @csrf
                    <input type="hidden" name="approved" value="0">
                    <x-button variant="danger" class="w-full min-h-10 py-2 text-sm">Introuvable</x-button>
                </form>
            </div>
        </x-card>
    @empty
        <p class="text-sm text-stone-500">Aucun transfert en attente.</p>
    @endforelse

    <h2 class="mb-2 mt-6 text-lg font-bold">Factures des organisations</h2>
    <details class="mb-3 rounded-2xl border border-stone-200 bg-white p-4" @if ($errors->hasAny(['organisation_id', 'label', 'amount_fcfa'])) open @endif>
        <summary class="cursor-pointer font-semibold text-brand-800">+ Émettre une facture</summary>
        <form method="POST" action="{{ route('admin.invoices.store') }}" class="mt-3">
            @csrf
            <x-select name="organisation_id" label="Organisation" :options="$organisations->all()" placeholder="Choisir" />
            <x-field name="label" label="Désignation" placeholder="Ex. : espace organisations, cohorte 2027, 12 mois" maxlength="300" />
            <x-field name="amount_fcfa" type="number" inputmode="numeric" min="1" label="Montant (FCFA, TTC)" />
            <x-field name="due_on" type="date" label="À régler avant (facultatif)" />
            <x-button class="w-full">Émettre</x-button>
        </form>
    </details>
    @foreach ($invoices as $invoice)
        <div class="mb-2 rounded-xl border border-stone-200 bg-white p-3 text-sm">
            <div class="flex items-center justify-between gap-2">
                <a href="{{ route('invoices.show', $invoice) }}" class="font-medium underline">{{ $invoice->number }} · {{ $invoice->organisation->name }}</a>
                <span class="text-xs">{{ $invoice->status->label() }}</span>
            </div>
            <p class="text-xs text-stone-500">{{ $invoice->label }} · {{ number_format($invoice->amount_fcfa, 0, ',', "\u{00A0}") }} FCFA</p>
            @if ($invoice->status->value === 'emise')
                <div class="mt-2 flex gap-3">
                    <form method="POST" action="{{ route('admin.invoices.settle', $invoice) }}">@csrf<input type="hidden" name="paid" value="1"><button type="submit" class="py-1 text-xs font-medium text-brand-700 underline">Marquer payée</button></form>
                    <form method="POST" action="{{ route('admin.invoices.settle', $invoice) }}" data-confirm="Annuler cette facture ?">@csrf<input type="hidden" name="paid" value="0"><button type="submit" class="py-1 text-xs text-red-700 underline">Annuler</button></form>
                </div>
            @endif
        </div>
    @endforeach

    <h2 class="mb-2 mt-6 text-lg font-bold">Derniers paiements reçus</h2>
    @forelse ($recent as $payment)
        <a href="{{ route('payments.receipt', $payment) }}" class="mb-2 flex justify-between gap-2 rounded-xl border border-stone-200 bg-white p-3 text-sm">
            <span>{{ $payment->receipt_number }} · {{ $payment->user->name }}<span class="block text-xs text-stone-500">{{ $payment->offer->label() }} · {{ $payment->method->label() }}</span></span>
            <span class="shrink-0 font-medium">{{ number_format($payment->amount_fcfa, 0, ',', "\u{00A0}") }} FCFA</span>
        </a>
    @empty
        <p class="text-sm text-stone-500">Aucun paiement pour le moment.</p>
    @endforelse
</x-layout>
