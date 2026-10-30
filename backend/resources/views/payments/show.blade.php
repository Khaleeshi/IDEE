<x-layout title="Paiement">
    <a href="{{ route('offers.index') }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← Offres</a>
    <h1 class="text-2xl font-bold">{{ $payment->offer->label() }}</h1>
    <p class="mt-1 text-sm text-stone-500">{{ number_format($payment->amount_fcfa, 0, ',', "\u{00A0}") }} FCFA · {{ $payment->method->label() }}@if ($payment->project) · {{ $payment->project->title }}@endif</p>

    <x-card class="mt-4">
        <p class="font-semibold">{{ $payment->status->label() }}</p>

        @if ($payment->isPaid())
            <p class="mt-1 text-sm text-stone-600">Reçu {{ $payment->receipt_number }} du {{ $payment->paid_at->translatedFormat('d F Y') }}.</p>
            <x-button :href="route('payments.receipt', $payment)" variant="secondary" class="mt-3 w-full" target="_blank">Voir le reçu</x-button>
        @elseif ($payment->status->value === 'en_attente' && $payment->method->value === 'wave')
            <p class="mt-1 text-sm text-stone-600">Si vous avez payé dans Wave, la confirmation arrive en quelques instants.</p>
            <form method="POST" action="{{ route('payments.check', $payment) }}" class="mt-3">
                @csrf
                <x-button variant="secondary" class="w-full">Vérifier le paiement</x-button>
            </form>
        @elseif ($payment->status->value === 'en_attente' && $payment->method->value === 'orange_money')
            @if ($payment->payer_reference)
                <p class="mt-1 text-sm text-stone-600">Transfert annoncé ({{ $payment->payer_reference }}). L'équipe le vérifie et active votre offre, en général dans la journée.</p>
            @else
                <ol class="mt-2 list-decimal space-y-1 pl-5 text-sm text-stone-700">
                    <li>Dans Orange Money, envoyez <strong>{{ number_format($payment->amount_fcfa, 0, ',', "\u{00A0}") }} FCFA</strong> au <strong>{{ config('idee.payments.orange_money_number') }}</strong>.</li>
                    <li>Notez l'identifiant du transfert reçu par SMS, puis indiquez-le ci-dessous.</li>
                </ol>
                <form method="POST" action="{{ route('payments.reference', $payment) }}" class="mt-3">
                    @csrf
                    <x-field name="payer_reference" label="Identifiant du transfert" placeholder="Ex. : CI261030.1205.A12345" maxlength="100" />
                    <x-button class="w-full">J'ai payé</x-button>
                </form>
            @endif
        @else
            <x-button :href="route('offers.checkout', $payment->offer)" variant="secondary" class="mt-3 w-full">Réessayer</x-button>
        @endif
    </x-card>
</x-layout>
