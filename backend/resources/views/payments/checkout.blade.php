<x-layout :title="$offer->label()">
    <a href="{{ route('offers.index') }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← Offres</a>
    <h1 class="text-2xl font-bold">{{ $offer->label() }}</h1>
    <p class="mt-1 text-sm text-stone-700">{{ $offer->benefits() }}</p>

    @if ($methods === [])
        <x-card class="mt-4 text-sm text-stone-600">Le paiement en ligne arrive bientôt.</x-card>
    @else
        <form method="POST" action="{{ route('payments.store') }}" class="mt-4">
            @csrf
            <input type="hidden" name="offer" value="{{ $offer->value }}">

            @if ($offer->isForProject())
                <x-select name="project_id" label="Projet à mettre en avant" :options="$projects->pluck('title', 'id')->all()" :value="$project?->id" />
                <x-select name="quantity" label="Durée" :value="1"
                          :options="collect(range(1, config('idee.offers.sponsorise.max_weeks')))->mapWithKeys(fn ($weeks) => [$weeks => $weeks.' semaine'.($weeks > 1 ? 's' : '').' · '.number_format($weeks * $offer->price(), 0, ',', ' ').' FCFA'])->all()" />
            @else
                <p class="mb-4 text-lg font-bold text-brand-800">{{ number_format($offer->price(), 0, ',', "\u{00A0}") }} FCFA pour {{ $offer->days() }} jours</p>
            @endif

            <fieldset class="mb-4">
                <legend class="block text-sm font-medium text-stone-800">Payer avec</legend>
                <div class="mt-1 space-y-2">
                    @foreach ($methods as $method)
                        <label class="flex min-h-12 items-center gap-3 rounded-xl border border-stone-200 bg-white px-3">
                            <input type="radio" name="method" value="{{ $method->value }}" @checked($loop->first) class="h-5 w-5 accent-brand-700">
                            <span>{{ $method->label() }}@if ($method->value === 'orange_money') <span class="text-xs text-stone-500">· transfert vérifié par l'équipe</span>@endif</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <x-button class="w-full">Payer</x-button>
        </form>
        <p class="mt-3 text-xs text-stone-500">Paiement unique, sans prélèvement automatique. Prix toutes taxes comprises ; l'opérateur peut prélever la taxe de 0,5 % sur les transferts (2 000 FCFA au plus).</p>
    @endif
</x-layout>
