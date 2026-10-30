<x-layout title="Mes projets">
    <div class="mb-4 flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Mes projets</h1>
        <x-button :href="route('projects.create')" class="min-h-10 px-4 py-2 text-sm">+ Publier</x-button>
    </div>

    @forelse ($projects as $project)
        <div class="mb-1 flex items-center gap-2 px-1 text-xs text-stone-500">
            <span>Préparation : <strong class="text-brand-800">{{ $project->readiness_score }}/100</strong></span>
            @if ($project->status->value !== 'actif')<x-status :status="$project->status" />@endif
        </div>
        <x-project-card :project="$project" />
    @empty
        <x-card class="text-center">
            <p class="text-stone-600">Vous n'avez pas encore publié de projet.</p>
            <x-button :href="route('projects.create')" class="mt-3">Publier mon premier projet</x-button>
        </x-card>
    @endforelse
</x-layout>
