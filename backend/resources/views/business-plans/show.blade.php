<x-layout title="Business plan" :refresh="$plan?->status->value === 'en_cours' ? 10 : null">
    <a href="{{ route('projects.show', $project) }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← {{ $project->title }}</a>
    <h1 class="text-2xl font-bold">Business plan</h1>

    @if ($plan === null)
        <x-card class="mt-4">
            <p class="text-sm text-stone-600">L'assistant rédige un premier business plan en une à deux minutes, à partir de votre fiche projet, de vos besoins et de vos réponses à ses questions.</p>
            @if ($enabled)
                <form method="POST" action="{{ route('business-plans.generate', $project) }}" class="mt-3">
                    @csrf
                    <x-button class="w-full">Rédiger mon business plan</x-button>
                </form>
            @else
                <p class="mt-3 text-sm font-medium text-stone-500">L'assistant IA arrive bientôt sur IDEE.</p>
            @endif
        </x-card>
    @elseif ($plan->status->value === 'en_cours')
        <x-card class="mt-4 text-center">
            <p class="font-medium">L'assistant rédige la version {{ $plan->version }}…</p>
            <p class="mt-1 text-sm text-stone-500">Cette page se met à jour toute seule. Vous pouvez aussi revenir plus tard.</p>
        </x-card>
    @elseif ($plan->status->value === 'echec')
        <x-card class="mt-4 text-sm"><p class="text-red-700">{{ $plan->error }}</p></x-card>
    @else
        <p class="mt-1 text-sm text-stone-500">
            Version {{ $plan->version }} · rédigée le {{ $plan->generated_at?->translatedFormat('d F Y') }}
            @if ($plan->edited_at) · modifiée le {{ $plan->edited_at->translatedFormat('d F Y') }}@endif
        </p>
        <p class="mt-2 rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-900">Relisez et corrigez chaque section : l'assistant peut se tromper, et les chiffres sont des hypothèses à vérifier.</p>

        <form method="POST" action="{{ route('business-plans.update', $plan) }}" class="mt-4">
            @csrf
            @method('PUT')
            @foreach ($plan->sections as $section)
                <label for="section-{{ $section['key'] }}" class="mt-4 block font-semibold">{{ $section['title'] }}</label>
                <textarea id="section-{{ $section['key'] }}" name="sections[{{ $section['key'] }}]" rows="6" maxlength="6000"
                          class="mt-1 block w-full rounded-xl border border-stone-300 px-3 py-3 text-base">{{ $section['content'] }}</textarea>
            @endforeach
            <x-button class="mt-4 w-full">Enregistrer mes corrections</x-button>
        </form>

        @if ($plan->assumptions)
            <h2 class="mb-2 mt-6 font-bold">Hypothèses à vérifier</h2>
            <ul class="space-y-2 text-sm">
                @foreach ($plan->assumptions as $assumption)
                    <li class="rounded-xl border border-stone-200 bg-white p-3">
                        <span class="font-medium">{{ $assumption['label'] ?? '' }} : {{ $assumption['value'] ?? '' }}</span>
                        <span class="block text-stone-500">{{ $assumption['basis'] ?? '' }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        <x-button :href="route('business-plans.print', $project)" variant="secondary" class="mt-4 w-full" target="_blank">Version à imprimer ou à enregistrer en PDF</x-button>
        <x-button :href="route('business-plans.word', $project)" variant="secondary" class="mt-2 w-full">Télécharger en Word</x-button>
    @endif

    @if ($plan && $plan->status->value !== 'en_cours' && $enabled)
        <form method="POST" action="{{ route('business-plans.generate', $project) }}" data-confirm="Rédiger une nouvelle version ? La version actuelle reste dans l'historique." class="mt-4">
            @csrf
            <x-button variant="ghost" class="w-full">Rédiger une nouvelle version</x-button>
        </form>
    @endif

    @if ($versions->count() > 1)
        <p class="mt-4 text-xs text-stone-500">{{ $versions->count() }} versions enregistrées.</p>
    @endif
</x-layout>
