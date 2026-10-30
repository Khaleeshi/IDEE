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
                <div class="mt-3 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('follows.member', $member) }}">
                        @csrf
                        <x-button :variant="$following ? 'secondary' : 'primary'" class="min-h-10 px-4 py-2 text-sm">{{ $following ? '✓ Suivi' : 'Suivre' }}</x-button>
                    </form>
                    @if (($member->contact_preferences['accepts_requests'] ?? true) !== false)
                        <x-button :href="route('members.contact', $member)" variant="secondary" class="min-h-10 px-4 py-2 text-sm">Contacter à propos de mon projet</x-button>
                    @endif
                </div>
            @endunless
        @endauth

        @if ($member->availability)
            <p class="mt-3 text-sm font-medium {{ $member->availability->value === 'indisponible' ? 'text-stone-500' : 'text-brand-800' }}">{{ $member->availability->label() }}</p>
        @endif

        @if ($member->bio)
            <p class="mt-3 whitespace-pre-line text-stone-700">{{ $member->bio }}</p>
        @endif

        @if ($member->expertise && $member->hasVerifiedIdentity())
            {{-- SE-01 : services et tarif indicatif d'un expert vérifié. --}}
            <div class="mt-3 rounded-xl bg-stone-50 p-3 text-sm">
                <p><span class="text-stone-500">Services :</span> {{ $member->expertise }}</p>
                <p class="mt-1"><span class="text-stone-500">Tarif indicatif :</span> {{ $member->rate ?? 'sur demande' }}</p>
                @auth
                    @unless ($member->is(auth()->user()))
                        <x-button :href="route('quotes.create', $member)" variant="secondary" class="mt-2 w-full min-h-10 py-2 text-sm">Demander un devis</x-button>
                    @endunless
                @endauth
            </div>
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

    @if ($organisations->isNotEmpty())
        <p class="mt-3 text-sm text-stone-600">Membre de
            @foreach ($organisations as $organisation)
                <a href="{{ route('organisations.show', $organisation) }}" class="font-medium underline">{{ $organisation->name }}</a>@if ($organisation->isVerified()) ✓@endif{{ $loop->last ? '' : ',' }}
            @endforeach
        </p>
    @endif

    <h2 class="mb-3 mt-6 text-lg font-bold">Projets</h2>
    @forelse ($projects as $project)
        <x-project-card :project="$project" />
    @empty
        <p class="text-sm text-stone-600">Aucun projet actif.</p>
    @endforelse

    {{-- CO-03 : collaborations confirmées par les deux parties. --}}
    @if ($collaborations->isNotEmpty())
        <h2 class="mb-3 mt-6 text-lg font-bold">Collaborations confirmées</h2>
        <ul class="space-y-2">
            @foreach ($collaborations as $collaboration)
                @php($partner = $collaboration->otherThan($member))
                <li class="rounded-xl border border-stone-200 bg-white p-3 text-sm">
                    <p class="font-medium text-stone-900">{{ $collaboration->summary }}</p>
                    <p class="mt-1 text-xs text-stone-500">
                        Avec <a href="{{ route('members.show', $partner) }}" class="underline">{{ $partner->name }}</a>
                        @if ($collaboration->project) · <a href="{{ route('projects.show', $collaboration->project) }}" class="underline">{{ $collaboration->project->title }}</a>@endif
                        · {{ $collaboration->confirmed_at->translatedFormat('F Y') }}
                    </p>
                </li>
            @endforeach
        </ul>
    @endif
    @auth
        @unless ($member->is(auth()->user()))
            <x-report-form type="membre" :id="$member->id" label="Signaler ce profil" class="mt-6 text-center" />
        @endunless
    @endauth
</x-layout>
