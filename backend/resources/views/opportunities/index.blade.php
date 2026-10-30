<x-layout title="Programmes et financements">
    <a href="{{ route('journeys.index') }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← Parcours</a>
    <h1 class="text-2xl font-bold">Programmes et financements</h1>
    <p class="mt-1 text-stone-600">Prêts, garanties, investisseurs et programmes d'accompagnement ouverts aux entrepreneurs du Sénégal, avec leurs critères.</p>

    @if ($applications->isNotEmpty())
        <h2 class="mb-2 mt-5 text-sm font-semibold uppercase tracking-wide text-stone-500">Mes candidatures</h2>
        @foreach ($applications as $application)
            <a href="{{ route('opportunities.show', $application->opportunity) }}" class="mb-2 flex items-center justify-between gap-2 rounded-xl border border-stone-200 bg-white p-3 text-sm">
                <span class="font-medium">{{ $application->opportunity->title }}</span>
                <span class="shrink-0 rounded-full bg-stone-100 px-2 py-0.5 text-xs">{{ $application->status->label() }}</span>
            </a>
        @endforeach
    @endif

    <form method="GET" action="{{ route('opportunities.index') }}" role="search" class="mt-5">
        <label for="recherche-opportunite" class="sr-only">Rechercher</label>
        <input id="recherche-opportunite" name="q" type="search" value="{{ request('q') }}" placeholder="Rechercher un organisme, un programme…"
               class="block w-full rounded-xl border border-stone-300 px-3 py-3 text-base focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-100">
        @if ($kind)<input type="hidden" name="type" value="{{ $kind->value }}">@endif
    </form>

    <nav aria-label="Types d'opportunités" class="mt-3 flex gap-2 overflow-x-auto pb-1 text-sm">
        <a href="{{ route('opportunities.index') }}" class="shrink-0 rounded-full border px-3 py-1.5 {{ $kind ? 'border-stone-300 bg-white' : 'border-brand-700 bg-brand-700 text-white' }}">Tout</a>
        @foreach ($kinds as $item)
            <a href="{{ route('opportunities.index', ['type' => $item->value]) }}" class="shrink-0 rounded-full border px-3 py-1.5 {{ $kind === $item ? 'border-brand-700 bg-brand-700 text-white' : 'border-stone-300 bg-white' }}">{{ $item->label() }}</a>
        @endforeach
    </nav>

    <div class="mt-4">
        @forelse ($opportunities as $opportunity)
            <a href="{{ route('opportunities.show', $opportunity) }}" class="mb-3 block rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                <span class="text-xs font-medium text-brand-700">{{ $opportunity->kind->label() }} · {{ $opportunity->organisation }}</span>
                <span class="mt-1 block font-semibold leading-snug">{{ $opportunity->title }}</span>
                @if ($opportunity->amount)<span class="mt-1 block text-sm text-stone-700">{{ $opportunity->amount }}</span>@endif
                <span class="mt-1 block text-xs text-stone-500">{{ $opportunity->deadline ? 'Date limite : '.$opportunity->deadline->translatedFormat('d F Y') : $opportunity->deadline_note }}</span>
            </a>
        @empty
            <x-card class="text-center text-sm text-stone-600">Aucune opportunité ouverte ne correspond à cette recherche.</x-card>
        @endforelse
    </div>

    <p class="mt-4 text-xs text-stone-500">Les démarches auprès des organismes officiels sont gratuites. Méfiez-vous de toute personne qui demande de l'argent pour « faciliter » un dossier, et signalez-la.</p>
</x-layout>
