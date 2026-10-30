<x-layout title="Projets proches · {{ $project->title }}">
    <a href="{{ route('projects.show', $project) }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← {{ $project->title }}</a>

    <h1 class="text-xl font-bold">Projets proches</h1>
    <p class="mt-1 text-sm text-stone-600">Rapprochez-vous des porteurs de projets similaires pour mutualiser un fournisseur, un local ou des clients. Seules les fiches publiques sont montrées ; une conversation ne s'ouvre qu'avec l'accord des deux porteurs.</p>

    <x-card class="mt-4">
        <form method="POST" action="{{ route('matches.toggle', $project) }}">
            @csrf
            <input type="hidden" name="open" value="{{ $project->open_to_matching ? 0 : 1 }}">
            <p class="text-sm">{{ $project->open_to_matching
                ? 'Les rapprochements sont activés pour ce projet.'
                : 'Les rapprochements sont désactivés : votre projet n\'est proposé à personne et vous ne voyez pas les projets proches.' }}</p>
            <x-button :variant="$project->open_to_matching ? 'secondary' : 'primary'" class="mt-3 w-full">
                {{ $project->open_to_matching ? 'Désactiver les rapprochements' : 'Activer les rapprochements' }}
            </x-button>
        </form>
    </x-card>

    @if ($received->isNotEmpty())
        <h2 class="mb-2 mt-6 text-lg font-bold">Propositions reçues</h2>
        @foreach ($received as $match)
            <x-card class="text-sm">
                <div class="flex items-start justify-between gap-2">
                    <p class="font-semibold">{{ $match->project->title }}</p>
                    <x-status :status="$match->status" />
                </div>
                <p class="mt-1 text-xs text-stone-500">{{ $match->project->sector?->name }}@if ($match->project->region) · {{ $match->project->region }}@endif · proposé par {{ $match->requester->name }}</p>
                <p class="mt-2 line-clamp-3 text-stone-700">{{ $match->project->summary }}</p>
                @if ($match->message)<p class="mt-2 whitespace-pre-line rounded-xl bg-stone-50 p-2">{{ $match->message }}</p>@endif
                @if ($match->status->value === 'proposee')
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <form method="POST" action="{{ route('matches.respond', $match) }}">
                            @csrf
                            <input type="hidden" name="accepted" value="1">
                            <x-button class="w-full">Accepter</x-button>
                        </form>
                        <form method="POST" action="{{ route('matches.respond', $match) }}">
                            @csrf
                            <input type="hidden" name="accepted" value="0">
                            <x-button variant="secondary" class="w-full">Décliner</x-button>
                        </form>
                    </div>
                @elseif ($match->contactRequest)
                    <a href="{{ route('contact-requests.show', $match->contactRequest) }}" class="mt-2 inline-block font-medium text-brand-700 underline">Ouvrir la conversation</a>
                @endif
            </x-card>
        @endforeach
    @endif

    @if ($project->open_to_matching)
        <h2 class="mb-2 mt-6 text-lg font-bold">Suggestions</h2>
        @forelse ($suggestions as $candidate)
            <x-card class="text-sm">
                <a href="{{ route('projects.show', $candidate) }}" class="font-semibold text-brand-800 underline">{{ $candidate->title }}</a>
                <p class="mt-1 text-xs text-stone-500">{{ $candidate->sector?->name }}@if ($candidate->region) · {{ $candidate->region }}@endif</p>
                <p class="mt-2 text-xs text-brand-800">{{ implode(' · ', $candidate->match_reasons) }}</p>
                <details class="mt-2">
                    <summary class="cursor-pointer py-2 font-medium text-brand-700">Proposer un rapprochement</summary>
                    <form method="POST" action="{{ route('matches.store', $project) }}" class="mt-2">
                        @csrf
                        <input type="hidden" name="target_project_id" value="{{ $candidate->id }}">
                        <x-field name="message" type="textarea" rows="2" label="Message (facultatif)" maxlength="500"
                                 placeholder="Ex. : nous pourrions acheter nos emballages ensemble" />
                        <x-button class="w-full">Proposer</x-button>
                    </form>
                </details>
            </x-card>
        @empty
            <p class="text-sm text-stone-500">Aucun projet proche pour le moment parmi ceux dont le porteur a activé les rapprochements. Revenez plus tard.</p>
        @endforelse
    @endif

    @if ($sent->isNotEmpty())
        <h2 class="mb-2 mt-6 text-lg font-bold">Propositions envoyées</h2>
        @foreach ($sent as $match)
            <div class="mb-2 flex items-center justify-between gap-2 rounded-xl border border-stone-200 bg-white p-3 text-sm">
                @if ($match->contactRequest)
                    <a href="{{ route('contact-requests.show', $match->contactRequest) }}" class="font-medium text-brand-800 underline">{{ $match->targetProject->title }}</a>
                @else
                    <span class="font-medium">{{ $match->targetProject->title }}</span>
                @endif
                <x-status :status="$match->status" />
            </div>
        @endforeach
    @endif
</x-layout>
