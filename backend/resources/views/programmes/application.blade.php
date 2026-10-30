<x-layout title="Candidature">
    <a href="{{ $canReview ? route('programmes.applications', $application->opportunity) : route('opportunities.show', $application->opportunity) }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← {{ $application->opportunity->title }}</a>
    <div class="flex items-start justify-between gap-2">
        <h1 class="text-2xl font-bold leading-tight">{{ $application->project->title }}</h1>
        <x-status :status="$application->status" />
    </div>
    <p class="mt-1 text-sm text-stone-500">Candidature de {{ $application->user->name }} au programme « {{ $application->opportunity->title }} », le {{ $application->created_at->translatedFormat('d F Y') }}</p>

    @if ($application->feedback)
        <x-card class="mt-4 text-sm"><span class="font-semibold">Retour de l'organisation :</span> {{ $application->feedback }}</x-card>
    @endif

    <x-card class="mt-4">
        <p class="text-sm">{{ $application->project->summary }}</p>
        <a href="{{ route('projects.show', $application->project) }}" class="mt-2 inline-block text-sm font-medium text-brand-700 underline">Voir la fiche du projet</a>
    </x-card>

    @foreach ($application->answers as $item)
        <h2 class="mb-1 mt-5 font-semibold">{{ $item['question'] }}</h2>
        <p class="whitespace-pre-line text-sm text-stone-800">{{ $item['answer'] }}</p>
    @endforeach

    @if ($application->business_plan)
        <details class="mt-5 rounded-2xl border border-stone-200 bg-white p-4">
            <summary class="cursor-pointer font-semibold">Business plan joint</summary>
            @foreach ($application->business_plan as $section)
                @if (filled($section['content'] ?? null))
                    <h3 class="mt-3 text-sm font-semibold">{{ $section['title'] }}</h3>
                    <p class="whitespace-pre-line text-sm text-stone-700">{{ $section['content'] }}</p>
                @endif
            @endforeach
        </details>
    @endif

    @if ($canReview)
        <x-card class="mt-5">
            <h2 class="font-semibold">Décision</h2>
            <form method="POST" action="{{ route('applications.decide', $application) }}" class="mt-2">
                @csrf
                <x-select name="status" label="Statut" :options="collect($decisions)->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()" :value="$application->status->value" />
                <x-field name="feedback" type="textarea" rows="2" label="Message au porteur (facultatif)" :value="$application->feedback" maxlength="2000" />
                <x-button class="w-full">Enregistrer et prévenir le porteur</x-button>
            </form>
            @if ($application->status->value === 'retenue' && $cohorts->isNotEmpty())
                <form method="POST" action="{{ route('cohorts.add', $cohorts->first()) }}" class="mt-3" id="ajout-cohorte">
                    @csrf
                    <input type="hidden" name="application_id" value="{{ $application->id }}">
                    <x-button variant="secondary" class="w-full">Ajouter à la cohorte « {{ $cohorts->first()->name }} »</x-button>
                </form>
            @endif
        </x-card>
    @endif
</x-layout>
