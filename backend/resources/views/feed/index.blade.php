<x-layout>
    @guest
        <section class="mb-6 rounded-2xl bg-brand-700 px-5 py-6 text-white">
            <h1 class="text-2xl font-bold leading-tight">Ton projet ne reste plus un dossier sans réponse.</h1>
            <p class="mt-2 text-brand-50">Publie ce qui te manque : financement, associé, compétence, clients. Le réseau te répond.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('login') }}" class="inline-flex min-h-12 items-center rounded-xl bg-white px-5 font-semibold text-brand-800">Rejoindre IDEE</a>
                <a href="{{ route('journeys.index') }}" class="inline-flex min-h-12 items-center rounded-xl border border-white/60 px-5 font-semibold text-white">Parcours guidés</a>
            </div>
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
                <x-field name="amount_min" type="number" inputmode="numeric" min="0" label="Montant recherché, à partir de (FCFA)" :value="$filters['amount_min'] ?? null" />
                <x-field name="amount_max" type="number" inputmode="numeric" min="0" label="Montant recherché, jusqu'à (FCFA)" :value="$filters['amount_max'] ?? null" />
            </div>
        </details>
    </form>

    @php($tabs = ['activite' => ['Activité', route('home')], 'projets' => ['Projets', route('home', ['vue' => 'projets'])]] + (auth()->check() ? ['suivis' => ['Suivis', route('home', ['vue' => 'suivis'])]] : []))
    <nav aria-label="Explorer le réseau" class="mb-3 flex gap-2 overflow-x-auto pb-1 text-sm">
        <a href="{{ route('groups.index') }}" class="shrink-0 rounded-full border border-stone-300 bg-white px-3 py-1.5">Groupes</a>
        <a href="{{ route('events.index') }}" class="shrink-0 rounded-full border border-stone-300 bg-white px-3 py-1.5">Pitchs</a>
        <a href="{{ route('opportunities.index') }}" class="shrink-0 rounded-full border border-stone-300 bg-white px-3 py-1.5">Financements</a>
        <a href="{{ route('experts.index') }}" class="shrink-0 rounded-full border border-stone-300 bg-white px-3 py-1.5">Experts</a>
        <a href="{{ route('organisations.index') }}" class="shrink-0 rounded-full border border-stone-300 bg-white px-3 py-1.5">Organisations</a>
    </nav>

    <nav aria-label="Affichage de l'accueil" class="mb-4 grid rounded-xl bg-stone-100 p-1 text-sm font-medium {{ count($tabs) === 3 ? 'grid-cols-3' : 'grid-cols-2' }}">
        @foreach ($tabs as $key => [$label, $href])
            <a href="{{ $href }}" @if ($tab === $key) aria-current="page" @endif
               class="rounded-lg py-2 text-center {{ $tab === $key ? 'bg-white text-brand-800 shadow-sm' : 'text-stone-600' }}">{{ $label }}</a>
        @endforeach
    </nav>

    @if ($recommendations->isNotEmpty())
        <section class="mb-4 rounded-2xl border border-brand-100 bg-brand-50 p-4" aria-labelledby="pour-vous">
            <h2 id="pour-vous" class="font-semibold text-brand-900">Pour vous aujourd'hui</h2>
            <ul class="mt-2 divide-y divide-brand-100">
                @foreach ($recommendations as $pick)
                    <li class="py-2">
                        <a href="{{ route('projects.show', $pick['need']->project) }}" class="font-medium text-stone-900 underline">{{ $pick['need']->title }}</a>
                        <span class="block text-xs text-stone-600">{{ $pick['need']->project->title }}</span>
                        <span class="mt-1 block text-xs text-brand-800">Pourquoi : {{ implode(' ', $pick['reasons']) }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($tab !== 'projets')
        @forelse ($activities as $activity)
            <x-activity-item :activity="$activity" />
        @empty
            <x-card class="text-center text-stone-600">
                <p>{{ $tab === 'suivis' ? 'Rien de neuf chez ce que vous suivez. Suivez des projets, des personnes ou des secteurs depuis leur page.' : 'Le fil est encore calme. Publiez un projet ou un appel à action pour le lancer.' }}</p>
                @auth
                    <x-button :href="route('projects.create')" class="mt-3">Publier mon projet</x-button>
                @endauth
            </x-card>
        @endforelse

        {{ $activities->links() }}
    @else
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-stone-500">
            {{ $projects->total() }} {{ $projects->total() > 1 ? 'projets cherchent' : 'projet cherche' }} du soutien
        </h2>

        @if ($sector)
            @auth
                <form method="POST" action="{{ route('follows.sector', $sector) }}" class="mb-3">
                    @csrf
                    <x-button variant="secondary" class="w-full">{{ $followingSector ? 'Ne plus suivre' : 'Suivre' }} le secteur {{ $sector->name }}</x-button>
                </form>
            @endauth
        @endif

        @auth
            @if ($filters !== [])
                <form method="POST" action="{{ route('alerts.store') }}" class="mb-3">
                    @csrf
                    @foreach ($filters as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <x-button variant="secondary" class="w-full">🔔 M'alerter des nouveaux projets de cette recherche</x-button>
                </form>
            @endif
        @endauth

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
    @endif
</x-layout>
