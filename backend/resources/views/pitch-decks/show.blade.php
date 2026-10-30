<x-layout title="Présentation du projet" :refresh="$deck?->status->value === 'en_cours' ? 10 : null">
    <a href="{{ route('projects.show', $project) }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← {{ $project->title }}</a>
    <h1 class="text-2xl font-bold">Présentation du projet</h1>
    <p class="mt-1 text-sm text-stone-600">Une présentation en neuf diapositives pour vos rendez-vous avec des partenaires, des jurys ou des investisseurs.</p>

    @if ($deck === null)
        <x-card class="mt-4">
            <p class="text-sm text-stone-600">L'assistant rédige votre présentation à partir de votre fiche projet, de vos besoins et de vos réponses à ses questions. Vous la corrigez ensuite.</p>
            @if ($enabled)
                <form method="POST" action="{{ route('pitch-decks.generate', $project) }}" class="mt-3">
                    @csrf
                    <x-button class="w-full">Rédiger ma présentation</x-button>
                </form>
            @else
                <p class="mt-3 text-sm font-medium text-stone-500">L'assistant IA arrive bientôt sur IDEE.</p>
            @endif
        </x-card>
    @elseif ($deck->status->value === 'en_cours')
        <x-card class="mt-4 text-center">
            <p class="font-medium">L'assistant rédige la version {{ $deck->version }}…</p>
            <p class="mt-1 text-sm text-stone-500">Cette page se met à jour toute seule. Vous pouvez aussi revenir plus tard.</p>
        </x-card>
    @elseif ($deck->status->value === 'echec')
        <x-card class="mt-4 text-sm"><p class="text-red-700">{{ $deck->error }}</p></x-card>
    @else
        <p class="mt-2 text-sm text-stone-500">Version {{ $deck->version }}@if ($deck->edited_at) · modifiée le {{ $deck->edited_at->translatedFormat('d F Y') }}@endif</p>
        <p class="mt-2 rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-900">Relisez chaque diapositive et remplacez les « à compléter » par vos vrais chiffres.</p>

        <form method="POST" action="{{ route('pitch-decks.update', $deck) }}" class="mt-4">
            @csrf
            @method('PUT')
            @foreach ($deck->content['slides'] as $index => $slide)
                <x-card class="mt-3">
                    <p class="text-xs font-medium text-stone-500">Diapositive {{ $index + 2 }}</p>
                    <label for="titre-{{ $slide['key'] }}" class="sr-only">Titre</label>
                    <input id="titre-{{ $slide['key'] }}" name="slides[{{ $slide['key'] }}][title]" value="{{ $slide['title'] }}" maxlength="200"
                           class="mt-1 block w-full rounded-xl border border-stone-300 px-3 py-2 text-base font-semibold">
                    <label for="points-{{ $slide['key'] }}" class="mt-2 block text-sm text-stone-600">Points, un par ligne</label>
                    <textarea id="points-{{ $slide['key'] }}" name="slides[{{ $slide['key'] }}][bullets]" rows="4" maxlength="2000"
                              class="mt-1 block w-full rounded-xl border border-stone-300 px-3 py-2 text-base">{{ implode("\n", $slide['bullets']) }}</textarea>
                    <label for="notes-{{ $slide['key'] }}" class="mt-2 block text-sm text-stone-600">Notes pour l'oral</label>
                    <textarea id="notes-{{ $slide['key'] }}" name="slides[{{ $slide['key'] }}][notes]" rows="3" maxlength="3000"
                              class="mt-1 block w-full rounded-xl border border-stone-300 px-3 py-2 text-sm">{{ $slide['notes'] }}</textarea>
                </x-card>
            @endforeach
            <x-button class="mt-4 w-full">Enregistrer mes corrections</x-button>
        </form>

        <x-button :href="route('pitch-decks.print', $project)" variant="secondary" class="mt-3 w-full" target="_blank">Diapositives à projeter ou à enregistrer en PDF</x-button>
    @endif

    @if ($deck && $deck->status->value !== 'en_cours' && $enabled)
        <form method="POST" action="{{ route('pitch-decks.generate', $project) }}" data-confirm="Rédiger une nouvelle version ? La version actuelle reste enregistrée." class="mt-4">
            @csrf
            <x-button variant="ghost" class="w-full">Rédiger une nouvelle version</x-button>
        </form>
    @endif
</x-layout>
