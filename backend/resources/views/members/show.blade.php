<x-layout :title="$member->name">
    <x-card>
        <h1 class="text-2xl font-bold">{{ $member->name }}</h1>
        <p class="text-sm text-stone-500">
            {{ $member->region ?? 'Région non précisée' }} · {{ $member->verification_level->label() }} · membre depuis {{ $member->created_at->translatedFormat('F Y') }}
        </p>

        @if ($member->publicRoles())
            <ul class="mt-3 flex flex-wrap gap-2">
                @foreach ($member->publicRoles() as $role)
                    <li class="rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-800">{{ $role->label() }}</li>
                @endforeach
            </ul>
        @endif

        @if ($member->bio)
            <p class="mt-3 whitespace-pre-line text-stone-700">{{ $member->bio }}</p>
        @endif

        @if ($member->skills)
            <p class="mt-3 text-sm"><span class="text-stone-500">Compétences :</span> {{ implode(', ', $member->skills) }}</p>
        @endif
        @if ($member->languages)
            <p class="mt-1 text-sm"><span class="text-stone-500">Langues :</span> {{ implode(', ', $member->languages) }}</p>
        @endif

        <div class="mt-3 border-t border-stone-100 pt-3">
            <p class="text-sm font-medium">Réactivité</p>
            @include('members.partials.reactivity', ['reactivity' => $reactivity])
        </div>
    </x-card>

    <h2 class="mb-3 mt-6 text-lg font-bold">Projets</h2>
    @forelse ($projects as $project)
        <x-project-card :project="$project" />
    @empty
        <p class="text-sm text-stone-600">Aucun projet actif.</p>
    @endforelse
</x-layout>
