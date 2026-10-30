<x-layout title="Devis">
    <a href="{{ route('quotes.index') }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← Devis et prestations</a>
    @php($isExpert = $quote->expert_id === auth()->id())
    <div class="flex items-start justify-between gap-2">
        <h1 class="text-2xl font-bold">{{ $isExpert ? 'Demande de '.$quote->client->name : 'Devis de '.$quote->expert->name }}</h1>
        <span class="shrink-0 rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-medium">{{ $quote->status->label() }}</span>
    </div>
    @if ($quote->project)
        <p class="mt-1 text-sm">Projet : <a href="{{ route('projects.show', $quote->project) }}" class="underline">{{ $quote->project->title }}</a></p>
    @endif

    <x-card class="mt-4 text-sm">
        <h2 class="font-semibold">Prestation demandée</h2>
        <p class="mt-1 whitespace-pre-line">{{ $quote->description }}</p>
        <p class="mt-2 text-xs text-stone-500">@if ($quote->budget)Budget envisagé : {{ $quote->budget }} · @endif @if ($quote->wanted_by)pour le {{ $quote->wanted_by->translatedFormat('d F Y') }} · @endif demandé le {{ $quote->created_at->translatedFormat('d F Y') }}</p>
    </x-card>

    @if ($quote->quoted_at)
        <x-card class="mt-3 text-sm">
            <h2 class="font-semibold">Devis</h2>
            <p class="mt-1 text-lg font-bold text-brand-800">{{ number_format($quote->quote_amount_fcfa, 0, ',', "\u{00A0}") }} FCFA · {{ $quote->quote_delay }}</p>
            <p class="mt-1 whitespace-pre-line">{{ $quote->quote_details }}</p>
        </x-card>
    @endif

    @if ($isExpert && in_array($quote->status->value, ['demandee', 'devis_envoye'], true))
        <x-card class="mt-3">
            <h2 class="font-semibold">{{ $quote->quoted_at ? 'Modifier mon devis' : 'Envoyer mon devis' }}</h2>
            <form method="POST" action="{{ route('quotes.send', $quote) }}" class="mt-2">
                @csrf
                <x-field name="quote_amount_fcfa" type="number" inputmode="numeric" min="0" label="Montant (FCFA)" :value="$quote->quote_amount_fcfa" />
                <x-field name="quote_delay" label="Délai" :value="$quote->quote_delay" placeholder="Ex. : 10 jours" maxlength="120" />
                <x-field name="quote_details" type="textarea" rows="4" label="Ce que comprend la prestation" :value="$quote->quote_details" maxlength="3000" />
                <x-button class="w-full">Envoyer le devis</x-button>
            </form>
        </x-card>
    @endif

    <div class="mt-4 space-y-2">
        @if (! $isExpert && $quote->status->value === 'devis_envoye')
            <div class="grid grid-cols-2 gap-2">
                <form method="POST" action="{{ route('quotes.answer', $quote) }}">@csrf<input type="hidden" name="accepted" value="1"><x-button class="w-full">Accepter</x-button></form>
                <form method="POST" action="{{ route('quotes.answer', $quote) }}">@csrf<input type="hidden" name="accepted" value="0"><x-button variant="secondary" class="w-full">Refuser</x-button></form>
            </div>
        @endif
        @if ($isExpert && $quote->status->value === 'acceptee')
            <form method="POST" action="{{ route('quotes.deliver', $quote) }}">@csrf<x-button class="w-full">J'ai livré la prestation</x-button></form>
        @endif
        @if (! $isExpert && $quote->status->value === 'livree')
            <form method="POST" action="{{ route('quotes.complete', $quote) }}">@csrf<x-button class="w-full">Confirmer : la prestation est terminée</x-button></form>
        @endif
        @if (in_array($quote->status->value, ['demandee', 'devis_envoye', 'acceptee'], true))
            <form method="POST" action="{{ route('quotes.cancel', $quote) }}" data-confirm="Annuler cette demande ?" class="text-center">@csrf<button type="submit" class="py-2 text-sm text-red-700 underline">Annuler la demande</button></form>
        @endif
    </div>

    <p class="mt-6 text-xs text-stone-500">Le paiement de la prestation se fait directement entre vous : aucun argent ne transite par IDEE.</p>
</x-layout>
