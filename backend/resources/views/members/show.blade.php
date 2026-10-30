<x-layout :title="$member->name">
    <x-card>
        <div class="flex items-center gap-3">
            <x-avatar :user="$member" size="h-16 w-16 text-xl" />
            <h1 class="text-2xl font-bold">{{ $member->name }}</h1>
        </div>
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

        @auth
            @unless ($member->is(auth()->user()))
                <form method="POST" action="{{ route('follows.member', $member) }}" class="mt-3">
                    @csrf
                    <x-button :variant="$following ? 'secondary' : 'primary'" class="min-h-10 px-4 py-2 text-sm">{{ $following ? '✓ Suivi' : 'Suivre' }}</x-button>
                </form>
            @endunless
        @endauth

        @if ($member->availability)
            <p class="mt-3 text-sm font-medium {{ $member->availability->value === 'indisponible' ? 'text-stone-500' : 'text-brand-800' }}">{{ $member->availability->label() }}</p>
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
    @auth
        @unless ($member->is(auth()->user()))
            <x-report-form type="membre" :id="$member->id" label="Signaler ce profil" class="mt-6 text-center" />
        @endunless
    @endauth
</x-layout>
