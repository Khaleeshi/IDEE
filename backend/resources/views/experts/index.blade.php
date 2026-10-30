<x-layout title="Experts" description="Comptables, juristes, formateurs et conseillers à l'identité vérifiée, avec leurs tarifs indicatifs.">
    <h1 class="text-2xl font-bold">Experts vérifiés</h1>
    <p class="mt-1 text-stone-600">Comptables, juristes, formateurs, conseillers : chaque expert a fait vérifier son identité. Les tarifs sont indicatifs et se règlent directement avec l'expert, sans commission d'IDEE.</p>

    <form method="GET" action="{{ route('experts.index') }}" role="search" class="mt-4">
        <label for="recherche-expert" class="sr-only">Rechercher un expert</label>
        <input id="recherche-expert" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Ex. : comptabilité, statuts, export…"
               class="block w-full rounded-xl border border-stone-300 px-3 py-3 text-base focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-100">
        <details class="mt-2" @if (isset($filters['sector']) || isset($filters['region'])) open @endif>
            <summary class="cursor-pointer py-2 text-sm font-medium text-brand-700">Filtres</summary>
            <div class="mt-2 grid gap-x-3 sm:grid-cols-2">
                <x-select name="sector" label="Secteur" :options="$sectors" :value="$filters['sector'] ?? null" placeholder="Tous les secteurs" />
                <x-field name="region" label="Région" :value="$filters['region'] ?? null" placeholder="Ex. : Thiès" />
            </div>
        </details>
        <x-button variant="secondary" class="mt-2 w-full">Chercher</x-button>
    </form>

    <div class="mt-4">
        @forelse ($experts as $expert)
            <a href="{{ route('members.show', $expert) }}" class="mb-3 flex gap-3 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                <x-avatar :user="$expert" size="h-12 w-12 text-sm" />
                <span class="min-w-0">
                    <span class="block font-semibold">{{ $expert->name }} <span class="text-xs font-medium text-brand-700">✓ identité vérifiée</span></span>
                    @if ($expert->expertise)<span class="mt-1 block text-sm text-stone-700">{{ $expert->expertise }}</span>@endif
                    <span class="mt-1 block text-xs text-stone-500">{{ $expert->rate ?? 'Tarif sur demande' }}@if ($expert->region) · {{ $expert->region }}@endif</span>
                </span>
            </a>
        @empty
            <x-card class="text-center text-sm text-stone-600">Aucun expert vérifié ne correspond à cette recherche pour le moment.</x-card>
        @endforelse
        {{ $experts->links() }}
    </div>

    @auth
        <p class="mt-4 text-sm text-stone-600">Vous êtes expert ? Ajoutez le rôle « Expert », vos services et votre tarif dans <a href="{{ route('profile.edit') }}" class="font-medium text-brand-700 underline">votre profil</a>, puis faites vérifier votre identité.</p>
    @endauth
</x-layout>
