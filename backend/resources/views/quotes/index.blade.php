<x-layout title="Devis et prestations">
    <h1 class="text-2xl font-bold">Devis et prestations</h1>

    @foreach (['asClient' => 'Mes demandes', 'asExpert' => 'Reçues en tant qu\'expert'] as $list => $title)
        @if ($$list->isNotEmpty() || $list === 'asClient')
            <h2 class="mb-2 mt-5 text-lg font-bold">{{ $title }}</h2>
            @forelse ($$list as $quote)
                <a href="{{ route('quotes.show', $quote) }}" class="mb-2 flex items-center justify-between gap-2 rounded-xl border border-stone-200 bg-white p-3 text-sm">
                    <span><span class="font-medium">{{ $list === 'asClient' ? $quote->expert->name : $quote->client->name }}</span>
                        <span class="block line-clamp-1 text-xs text-stone-500">{{ $quote->description }}</span></span>
                    <span class="shrink-0 text-xs font-medium text-stone-600">{{ $quote->status->label() }}</span>
                </a>
            @empty
                <p class="text-sm text-stone-500">Aucune demande. Trouvez un expert dans <a href="{{ route('experts.index') }}" class="font-medium text-brand-700 underline">l'annuaire</a>.</p>
            @endforelse
        @endif
    @endforeach
</x-layout>
