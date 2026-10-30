<x-layout title="Groupes" description="Les groupes d'IDEE par secteur d'activité et par région du Sénégal.">
    <h1 class="text-2xl font-bold">Groupes</h1>
    <p class="mt-1 text-stone-600">Échangez avec les entrepreneurs de votre secteur ou de votre région : fournisseurs, salons, bonnes adresses, entraide.</p>

    @foreach (['secteur' => 'Par secteur', 'region' => 'Par région'] as $type => $title)
        <h2 class="mb-2 mt-6 text-lg font-bold">{{ $title }}</h2>
        <ul class="grid grid-cols-2 gap-2 text-sm">
            @foreach ($groups[$type] as $group)
                <li>
                    <a href="{{ route('groups.show', [$group['type'], $group['key']]) }}" class="block h-full rounded-xl border border-stone-200 bg-white p-3">
                        <span class="block font-medium text-stone-900">{{ $group['name'] }}</span>
                        <span class="text-xs text-stone-500">{{ $group['projects'] }} projet{{ $group['projects'] > 1 ? 's' : '' }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endforeach
</x-layout>
