<x-layout :title="$event->title" :description="\Illuminate\Support\Str::limit($event->description, 180)" :noindex="! $event->isSpeedMeeting()">
    <a href="{{ route('events.index') }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← Sessions de pitch</a>

    <p class="text-sm font-medium text-brand-700">{{ $event->starts_at->translatedFormat('l d F Y · H\hi') }} · {{ $event->mode === \App\Enums\MeetingMode::Visio ? 'En ligne' : 'En présentiel' }}</p>
    <h1 class="mt-1 text-2xl font-bold leading-tight">{{ $event->title }}</h1>
    <p class="mt-1 text-sm text-stone-500">
        Organisée par
        @if ($event->organisation)
            <a href="{{ route('organisations.show', $event->organisation) }}" class="underline">{{ $event->organisation->name }}</a>
        @else
            l'équipe IDEE
        @endif
        · {{ $attendees }} inscrit{{ $attendees > 1 ? 's' : '' }}
    </p>
    @if ($event->isCancelled())
        <p class="mt-3 rounded-xl bg-red-50 p-3 text-sm font-medium text-red-800">Cette session est annulée.</p>
    @endif
    <p class="mt-3 whitespace-pre-line text-stone-700">{{ $event->description }}</p>

    <x-card class="mt-4 text-sm">
        <p><span class="font-semibold">{{ $event->mode === \App\Enums\MeetingMode::Visio ? 'Lien' : 'Lieu' }} :</span>
            @if ($showPlace && $event->place)
                @if (str_starts_with($event->place, 'https://'))<a href="{{ $event->place }}" target="_blank" rel="noopener" class="underline">{{ $event->place }}</a>@else{{ $event->place }}@endif
            @else
                communiqué aux inscrits
            @endif
        </p>
        @if ($event->pitch_slots)
            <p class="mt-1"><span class="font-semibold">Présentations :</span> {{ $event->pitch_slots }} projets @if ($event->apply_until) · candidatures jusqu'au {{ $event->apply_until->translatedFormat('d F') }}@endif</p>
        @endif
    </x-card>

    @auth
        @if (! $event->isCancelled() && $event->starts_at->isFuture())
            <x-card class="mt-3">
                @if ($registration)
                    <p class="font-semibold">{{ $registration->status->label() }}@if ($registration->project) · {{ $registration->project->title }}@endif</p>
                    <form method="POST" action="{{ route('events.unregister', $event) }}" class="mt-2">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="py-2 text-sm text-stone-600 underline">Annuler mon inscription</button>
                    </form>
                @elseif ($event->isSpeedMeeting())
                    {{-- EV-02 : chacun indique de quel côté il vient. --}}
                    <form method="POST" action="{{ route('events.register', $event) }}">
                        @csrf
                        <fieldset class="mb-3 space-y-2">
                            <legend class="mb-1 text-sm font-medium">Je viens comme</legend>
                            <label class="flex min-h-12 items-center gap-2 rounded-xl border border-stone-200 px-3"><input type="radio" name="side" value="porteur" checked class="h-5 w-5 accent-brand-700"> porteur de projet</label>
                            <label class="flex min-h-12 items-center gap-2 rounded-xl border border-stone-200 px-3"><input type="radio" name="side" value="partenaire" class="h-5 w-5 accent-brand-700"> partenaire : investisseur, expert, client, mentor</label>
                        </fieldset>
                        <x-button class="w-full">M'inscrire aux rencontres</x-button>
                    </form>
                @else
                    <form method="POST" action="{{ route('events.register', $event) }}">
                        @csrf
                        <x-button class="w-full">M'inscrire pour assister</x-button>
                    </form>
                    @if ($event->acceptsPitches() && $myProjects->isNotEmpty())
                        <details class="mt-3">
                            <summary class="cursor-pointer py-2 font-medium text-brand-700">Candidater pour présenter mon projet</summary>
                            <form method="POST" action="{{ route('events.register', $event) }}" class="mt-2">
                                @csrf
                                <x-select name="project_id" label="Projet à présenter" :options="$myProjects->pluck('title', 'id')->all()" :value="$myProjects->first()->id" />
                                <x-button variant="secondary" class="w-full">Envoyer ma candidature</x-button>
                            </form>
                        </details>
                    @endif
                @endif
            </x-card>
        @endif
    @else
        <x-button :href="route('login')" variant="secondary" class="mt-3 w-full">Se connecter pour s'inscrire</x-button>
    @endauth

    @if ($event->isSpeedMeeting() && ($pairings->isNotEmpty() || $canManage))
        <section id="binomes" class="mt-6" aria-labelledby="titre-binomes">
            <h2 id="titre-binomes" class="mb-2 text-lg font-bold">{{ $canManage ? 'Binômes' : 'Mes rencontres' }}</h2>
            @foreach ($pairings as $pairing)
                <div class="mb-2 rounded-xl border border-stone-200 bg-white p-3 text-sm">
                    <p class="font-medium">Tour {{ $pairing->round }} · {{ $pairing->starts_at->translatedFormat('H\hi') }}</p>
                    <p class="text-stone-600">
                        @if ($canManage)
                            {{ $pairing->first->name }} et {{ $pairing->second->name }}
                        @else
                            Avec <a href="{{ route('members.show', $pairing->partnerOf(auth()->user())) }}" class="underline">{{ $pairing->partnerOf(auth()->user())->name }}</a>
                        @endif
                    </p>
                    <a href="{{ $pairing->room_url }}" target="_blank" rel="noopener" class="mt-1 inline-block font-medium text-brand-700 underline">Rejoindre la visio</a>
                </div>
            @endforeach
            @if ($canManage && ! $event->isCancelled())
                <form method="POST" action="{{ route('events.pair', $event) }}" @if ($pairings->isNotEmpty()) data-confirm="Refaire les binômes ? Les participants recevront le nouveau programme." @endif>
                    @csrf
                    <x-button variant="secondary" class="w-full">{{ $pairings->isEmpty() ? 'Former les binômes' : 'Refaire les binômes' }}</x-button>
                </form>
            @endif
        </section>
    @endif

    @if ($canManage && ! $event->isSpeedMeeting())
        <section id="candidatures" class="mt-6" aria-labelledby="titre-candidatures">
            <h2 id="titre-candidatures" class="mb-2 text-lg font-bold">Candidatures <span class="font-normal text-stone-400">({{ $candidates->where('status.value', 'retenu')->count() }} / {{ $event->pitch_slots }} retenues)</span></h2>
            @forelse ($candidates as $candidate)
                <x-card class="text-sm">
                    <div class="flex items-start justify-between gap-2">
                        @if ($candidate->project)
                            <a href="{{ route('projects.show', $candidate->project) }}" class="font-semibold underline">{{ $candidate->project->title }}</a>
                        @else
                            <span class="font-semibold">Projet retiré</span>
                        @endif
                        <span class="text-xs text-stone-500">{{ $candidate->status->label() }}</span>
                    </div>
                    <p class="mt-1 text-xs text-stone-500">par {{ $candidate->user->name }}</p>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <form method="POST" action="{{ route('events.decide', $candidate) }}">
                            @csrf
                            <input type="hidden" name="accepted" value="1">
                            <x-button class="w-full min-h-10 py-2 text-sm">Retenir</x-button>
                        </form>
                        <form method="POST" action="{{ route('events.decide', $candidate) }}">
                            @csrf
                            <input type="hidden" name="accepted" value="0">
                            <x-button variant="secondary" class="w-full min-h-10 py-2 text-sm">Ne pas retenir</x-button>
                        </form>
                    </div>
                </x-card>
            @empty
                <p class="text-sm text-stone-500">Aucune candidature pour le moment.</p>
            @endforelse
            @unless ($event->isCancelled())
                <form method="POST" action="{{ route('events.cancel', $event) }}" data-confirm="Annuler la session et prévenir les inscrits ?" class="mt-4 text-center">
                    @csrf
                    <button type="submit" class="py-2 text-sm text-red-700 underline">Annuler la session</button>
                </form>
            @endunless
        </section>
    @endif

    <p class="mt-6 text-xs text-stone-500">Une présentation n'est pas une offre de titres au public : aucun investissement ne se conclut ni ne se règle sur IDEE. Les échanges avec un investisseur se poursuivent directement entre vous.</p>
</x-layout>
