<x-layout>
    @guest
        <section class="mb-6 rounded-2xl bg-brand-700 px-5 py-6 text-white">
            <h1 class="text-2xl font-bold leading-tight">Ton projet ne reste plus un dossier sans réponse.</h1>
            <p class="mt-2 text-brand-50">Publie ce qui te manque : financement, associé, compétence, clients. Le réseau te répond.</p>
            <a href="{{ route('login') }}" class="mt-4 inline-flex min-h-12 items-center rounded-xl bg-white px-5 font-semibold text-brand-800">Rejoindre IDEE</a>
        </section>
    @endguest

    <form method="GET" action="{{ route('home') }}" role="search" class="mb-4">
        <label for="recherche" class="sr-only">Rechercher un projet</label>
        <div class="flex gap-2">
            <input id="recherche" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Rechercher un projet…"
                   class="block w-full rounded-xl border border-stone-300 px-3 py-3 text-base focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-100">
            <x-button class="shrink-0">Chercher</x-button>
        </div>

        <details class="mt-2" @if (array_filter($filters)) open @endif>
            <summary class="cursor-pointer py-2 text-sm font-medium text-brand-700">Filtres</summary>
            <div class="mt-2 grid gap-x-3 sm:grid-cols-2">
                <x-select name="need_type" label="Le projet cherche" :options="$needTypes" :value="$filters['need_type'] ?? null" placeholder="Tous les besoins" />
                <x-select name="sector" label="Secteur" :options="$sectors" :value="$filters['sector'] ?? null" placeholder="Tous les secteurs" />
                <x-select name="stage" label="Stade" :options="$stages" :value="$filters['stage'] ?? null" placeholder="Tous les stades" />
                <x-field name="region" label="Région" :value="$filters['region'] ?? null" placeholder="Ex. : Thiès" />
            </div>
        </details>
    </form>

    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-stone-500">
        {{ $projects->total() }} {{ $projects->total() > 1 ? 'projets cherchent' : 'projet cherche' }} du soutien
    </h2>

    @forelse ($projects as $project)
        <x-project-card :project="$project" />
    @empty
        <x-card class="text-center text-stone-600">
            <p>Aucun projet ne correspond à cette recherche.</p>
            @auth
                <x-button :href="route('projects.create')" class="mt-3">Publier mon projet</x-button>
            @endauth
        </x-card>
    @endforelse

    {{ $projects->links() }}
</x-layout>
