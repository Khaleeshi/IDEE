<x-layout title="Offres" description="Les offres payantes d'IDEE : l'essentiel reste gratuit pour les porteurs de projets.">
    <h1 class="text-2xl font-bold">Offres</h1>
    <p class="mt-1 text-stone-600">L'essentiel reste gratuit : profil, publication de projets et de besoins, demandes de contact, parcours, fiches pratiques et premier business plan. Les offres se paient à l'avance, sans prélèvement automatique.</p>

    @unless ($enabled)
        <x-card class="mt-4 text-sm text-stone-600">Le paiement des offres par Wave et Orange Money arrive bientôt.</x-card>
    @endunless

    @foreach ($offers as $offer)
        <x-card class="mt-4">
            <div class="flex items-start justify-between gap-2">
                <h2 class="text-lg font-semibold">{{ $offer->label() }}</h2>
                <p class="shrink-0 text-right font-bold text-brand-800">{{ number_format($offer->price(), 0, ',', "\u{00A0}") }} FCFA<span class="block text-xs font-normal text-stone-500">pour {{ $offer->days() }} jours</span></p>
            </div>
            <p class="mt-2 text-sm text-stone-700">{{ $offer->benefits() }}</p>
            @if ($enabled)
                @auth
                    <x-button :href="route('offers.checkout', $offer)" class="mt-3 w-full">Choisir cette offre</x-button>
                @else
                    <x-button :href="route('login')" variant="secondary" class="mt-3 w-full">Se connecter pour choisir</x-button>
                @endauth
            @endif
        </x-card>
    @endforeach

    <x-card class="mt-4">
        <h2 class="text-lg font-semibold">{{ \App\Enums\Offer::Sponsorise->label() }}</h2>
        <p class="mt-1 text-sm font-bold text-brand-800">{{ number_format(\App\Enums\Offer::Sponsorise->price(), 0, ',', "\u{00A0}") }} FCFA par semaine</p>
        <p class="mt-2 text-sm text-stone-700">{{ \App\Enums\Offer::Sponsorise->benefits() }}</p>
        @if ($enabled)
            @auth
                <x-button :href="route('offers.checkout', \App\Enums\Offer::Sponsorise)" variant="secondary" class="mt-3 w-full">Mettre un projet en avant</x-button>
            @endauth
        @endif
    </x-card>

    @if ($active->isNotEmpty())
        <h2 class="mb-2 mt-6 text-lg font-bold">Mes offres en cours</h2>
        @foreach ($active as $entitlement)
            <div class="mb-2 rounded-xl border border-brand-100 bg-white p-3 text-sm">
                <span class="font-medium">{{ $entitlement->offer->label() }}</span>@if ($entitlement->project) · {{ $entitlement->project->title }}@endif
                <span class="block text-xs text-stone-500">jusqu'au {{ $entitlement->ends_at->translatedFormat('d F Y') }}</span>
            </div>
        @endforeach
    @endif

    @if ($history->isNotEmpty())
        <h2 class="mb-2 mt-6 text-lg font-bold">Mes paiements</h2>
        @foreach ($history as $payment)
            <a href="{{ route('payments.show', $payment) }}" class="mb-2 flex items-center justify-between gap-2 rounded-xl border border-stone-200 bg-white p-3 text-sm">
                <span>{{ $payment->offer->label() }} · {{ number_format($payment->amount_fcfa, 0, ',', "\u{00A0}") }} FCFA
                    <span class="block text-xs text-stone-500">{{ $payment->created_at->translatedFormat('d F Y') }} · {{ $payment->method->label() }}</span></span>
                <span class="shrink-0 text-xs font-medium {{ $payment->isPaid() ? 'text-brand-800' : 'text-stone-500' }}">{{ $payment->status->label() }}</span>
            </a>
        @endforeach
    @endif

    <p class="mt-6 text-xs text-stone-500">Prix toutes taxes comprises. IDEE ne prend aucune commission sur les fonds levés et aucun argent entre membres ne transite par la plateforme.</p>
</x-layout>
