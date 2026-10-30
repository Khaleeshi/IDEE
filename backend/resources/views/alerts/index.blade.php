<x-layout title="Mes alertes">
    <h1 class="text-2xl font-bold">Mes alertes</h1>
    <p class="mt-1 text-stone-600">Chaque matin, IDEE vérifie vos recherches enregistrées et vous prévient des nouveaux projets. Jusqu'à {{ $max }} alertes.</p>

    <div class="mt-4">
        @forelse ($alerts as $alert)
            <div class="mb-2 flex items-center justify-between gap-2 rounded-xl border border-stone-200 bg-white p-3 text-sm">
                <a href="{{ $alert->url() }}" class="font-medium text-brand-800 underline">{{ $alert->label }}</a>
                <form method="POST" action="{{ route('alerts.destroy', $alert) }}" data-confirm="Supprimer cette alerte ?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="py-2 text-xs text-red-700 underline">Supprimer</button>
                </form>
            </div>
        @empty
            <x-card class="text-sm text-stone-600">
                <p>Aucune alerte pour le moment. Faites une recherche avec des filtres (secteur, région, besoin, montant…), puis touchez « M'alerter des nouveaux projets ».</p>
                <x-button :href="route('home', ['vue' => 'projets'])" variant="secondary" class="mt-3 w-full">Rechercher des projets</x-button>
            </x-card>
        @endforelse
    </div>
</x-layout>
