<x-layout :title="'Calendrier · '.$project->title">
    <a href="{{ route('projects.show', $project) }}" class="mb-2 inline-block text-sm text-stone-500">← {{ $project->title }}</a>
    <h1 class="text-2xl font-bold">Calendrier du projet</h1>
    <p class="mb-4 text-sm text-stone-600">Tâches à échéance, étapes convenues avec vos partenaires et rendez-vous confirmés, au même endroit.</p>

    @forelse ($items as $month => $entries)
        <h2 class="mb-2 mt-5 font-semibold">{{ $month }}</h2>
        <ul class="divide-y divide-stone-100 rounded-2xl border border-stone-200 bg-white">
            @foreach ($entries as $entry)
                <li class="flex gap-3 p-3">
                    <span class="w-12 shrink-0 text-center">
                        <span class="block text-lg font-bold {{ $entry['late'] ? 'text-red-700' : 'text-brand-800' }}">{{ $entry['date']->format('d') }}</span>
                        <span class="block text-xs text-stone-500">{{ $entry['date']->translatedFormat('D') }}</span>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-xs font-medium {{ $entry['late'] ? 'text-red-700' : 'text-stone-500' }}">{{ $entry['kind'] }}@if ($entry['late']) · en retard @endif</span>
                        <span class="block font-medium">{{ $entry['title'] }}</span>
                        @if ($entry['detail'])<span class="block text-xs text-stone-500">{{ $entry['detail'] }}</span>@endif
                    </span>
                </li>
            @endforeach
        </ul>
    @empty
        <x-card class="text-sm text-stone-600">Rien de prévu. Ajoutez des échéances à vos tâches, ou convenez d'une étape avec un partenaire.</x-card>
    @endforelse

    <x-button :href="route('tasks.index', $project)" variant="secondary" class="mt-4 w-full">Tableau de tâches</x-button>
</x-layout>
