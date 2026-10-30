{{-- Avancer ensemble : prochaines étapes (CO-01), rendez-vous (MR-07), collaborations confirmées (CO-03, FC-06). --}}
@php
    $me = auth()->user();
    $other = $contactRequest->otherParty($me);
    $field = 'mt-1 block w-full rounded-xl border border-stone-300 px-3 py-3 text-base focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-100';
@endphp

<section id="collaboration" class="mt-6" aria-labelledby="titre-collaboration">
    <h2 id="titre-collaboration" class="text-lg font-bold">Avancer ensemble</h2>
    <p class="mb-3 text-sm text-stone-500">Chaque proposition compte une fois acceptée par les deux personnes.</p>

    @if ($canSeeTasks)
        <a href="{{ route('tasks.index', $contactRequest->project) }}" class="mb-3 block rounded-xl border border-brand-100 bg-brand-50 p-3 text-sm font-medium text-brand-800">Voir le tableau de tâches du projet →</a>
    @endif

    {{-- Prochaines étapes --}}
    <x-card>
        <h3 class="font-semibold">Prochaines étapes</h3>
        @forelse ($contactRequest->agreements as $agreement)
            <div class="mt-3 border-t border-stone-100 pt-3 text-sm">
                <div class="flex items-start justify-between gap-2">
                    <p class="font-medium text-stone-900">{{ $agreement->objective }}</p>
                    <x-status :status="$agreement->status" />
                </div>
                <p class="mt-1 text-stone-500">
                    Responsable : {{ $agreement->owner->name }} · avant le {{ $agreement->due_on->translatedFormat('d F Y') }}
                    @if ($agreement->isOverdue())<span class="font-medium text-red-700"> · en retard</span>@endif
                </p>
                @if ($agreement->status->value === 'proposee' && $agreement->proposer_id !== $me->id)
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <form method="POST" action="{{ route('agreements.respond', $agreement) }}">
                            @csrf
                            <input type="hidden" name="accepted" value="1">
                            <x-button class="w-full">Accepter</x-button>
                        </form>
                        <form method="POST" action="{{ route('agreements.respond', $agreement) }}">
                            @csrf
                            <input type="hidden" name="accepted" value="0">
                            <x-button variant="secondary" class="w-full">Refuser</x-button>
                        </form>
                    </div>
                @elseif ($agreement->status->value === 'proposee')
                    <p class="mt-1 text-xs text-amber-800">En attente de la réponse de {{ $other?->name }}.</p>
                @elseif ($agreement->status->value === 'acceptee')
                    <form method="POST" action="{{ route('agreements.complete', $agreement) }}" class="mt-2">
                        @csrf
                        <x-button variant="secondary" class="w-full">C'est fait</x-button>
                    </form>
                @endif
            </div>
        @empty
            <p class="mt-1 text-sm text-stone-500">Convenez de ce que chacun fait ensuite, et pour quand.</p>
        @endforelse

        <details class="mt-3" @if ($errors->hasAny(['objective', 'due_on', 'owner_id'])) open @endif>
            <summary class="cursor-pointer py-2 font-medium text-brand-700">Proposer une prochaine étape</summary>
            <form method="POST" action="{{ route('agreements.store', $contactRequest) }}" class="mt-2">
                @csrf
                <x-field name="objective" label="Objectif" placeholder="Ex. : envoyer le devis du séchoir" maxlength="300" />
                <x-field name="due_on" type="date" label="Échéance" :min="now()->toDateString()" />
                <x-select name="owner_id" label="Responsable" :options="[$me->id => 'Moi', $other?->id => $other?->name]" :value="$me->id" />
                <x-button class="w-full">Proposer</x-button>
            </form>
        </details>
    </x-card>

    {{-- Rendez-vous --}}
    <x-card class="mt-3">
        <h3 class="font-semibold">Rendez-vous</h3>
        @forelse ($contactRequest->meetings as $meeting)
            <div class="mt-3 border-t border-stone-100 pt-3 text-sm">
                <div class="flex items-start justify-between gap-2">
                    <p class="font-medium text-stone-900">{{ $meeting->starts_at->translatedFormat('l d F à H\hi') }}</p>
                    <x-status :status="$meeting->status" />
                </div>
                <p class="mt-1 text-stone-600">
                    {{ $meeting->mode->label() }}
                    @if ($meeting->place)
                        · @if ($meeting->placeIsLink())<a href="{{ $meeting->place }}" class="underline" target="_blank" rel="noopener">rejoindre la visio</a>@else{{ $meeting->place }}@endif
                    @endif
                </p>
                @if ($meeting->agenda)
                    <p class="mt-1 whitespace-pre-line text-stone-500">{{ $meeting->agenda }}</p>
                @endif
                @if ($meeting->status->value === 'proposee' && $meeting->proposer_id !== $me->id)
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <form method="POST" action="{{ route('meetings.respond', $meeting) }}">
                            @csrf
                            <input type="hidden" name="accepted" value="1">
                            <x-button class="w-full">Confirmer</x-button>
                        </form>
                        <form method="POST" action="{{ route('meetings.respond', $meeting) }}">
                            @csrf
                            <input type="hidden" name="accepted" value="0">
                            <x-button variant="secondary" class="w-full">Décliner</x-button>
                        </form>
                    </div>
                @endif
                @if ($assistantEnabled && in_array($meeting->status->value, ['proposee', 'confirmee'], true) && $meeting->starts_at->isFuture())
                    {{-- IA-05 : l'assistant prépare les questions et les points à aborder. --}}
                    <form method="POST" action="{{ route('meetings.prepare', $meeting) }}" class="mt-2">
                        @csrf
                        <x-button variant="ghost" class="w-full min-h-10 py-2 text-sm">Préparer ce rendez-vous avec l'assistant</x-button>
                    </form>
                @endif
                @if (in_array($meeting->status->value, ['proposee', 'confirmee'], true) && $meeting->starts_at->isFuture())
                    <form method="POST" action="{{ route('meetings.cancel', $meeting) }}" data-confirm="Annuler ce rendez-vous ?" class="mt-1">
                        @csrf
                        <button type="submit" class="py-2 text-xs text-red-700 underline">Annuler le rendez-vous</button>
                    </form>
                @endif
            </div>
        @empty
            <p class="mt-1 text-sm text-stone-500">Proposez un créneau ; un rappel est envoyé aux deux la veille.</p>
        @endforelse

        <details class="mt-3" @if ($errors->hasAny(['starts_at', 'mode', 'place', 'agenda'])) open @endif>
            <summary class="cursor-pointer py-2 font-medium text-brand-700">Proposer un rendez-vous</summary>
            <form method="POST" action="{{ route('meetings.store', $contactRequest) }}" class="mt-2">
                @csrf
                <x-field name="starts_at" type="datetime-local" label="Jour et heure (heure de Dakar)" />
                <fieldset class="mb-4">
                    <legend class="block text-sm font-medium text-stone-800">Forme</legend>
                    <div class="mt-1 grid grid-cols-2 gap-2">
                        @foreach (\App\Enums\MeetingMode::cases() as $mode)
                            <label class="flex min-h-12 items-center gap-2 rounded-xl border border-stone-200 px-3">
                                <input type="radio" name="mode" value="{{ $mode->value }}" @checked(old('mode', 'visio') === $mode->value) class="h-5 w-5 accent-brand-700">
                                <span>{{ $mode->label() }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('mode')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </fieldset>
                <x-field name="place" label="Lieu, ou lien de la visio" placeholder="Ex. : CTIC Dakar, ou https://meet.google.com/…" maxlength="300" />
                <x-field name="agenda" type="textarea" rows="3" label="Ordre du jour (facultatif)" placeholder="Points à aborder, questions à préparer…" maxlength="2000" />
                <x-button class="w-full">Proposer</x-button>
            </form>
        </details>
    </x-card>

    {{-- Collaborations confirmées --}}
    <x-card class="mt-3">
        <h3 class="font-semibold">Collaboration</h3>
        @foreach ($contactRequest->collaborations as $collaboration)
            <div class="mt-3 border-t border-stone-100 pt-3 text-sm">
                <div class="flex items-start justify-between gap-2">
                    <p class="font-medium text-stone-900">{{ $collaboration->summary }}</p>
                    <x-status :status="$collaboration->status" />
                </div>
                @if ($collaboration->status->value === 'en_attente' && $collaboration->partner_id === $me->id)
                    <p class="mt-1 text-stone-500">{{ $collaboration->declarer->name }} vous demande de confirmer. Une fois confirmée, elle apparaît sur vos deux profils{{ $collaboration->project_id ? ' et dans le fil' : '' }}.</p>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <form method="POST" action="{{ route('collaborations.respond', $collaboration) }}">
                            @csrf
                            <input type="hidden" name="accepted" value="1">
                            <x-button class="w-full">Confirmer</x-button>
                        </form>
                        <form method="POST" action="{{ route('collaborations.respond', $collaboration) }}">
                            @csrf
                            <input type="hidden" name="accepted" value="0">
                            <x-button variant="secondary" class="w-full">Ce n'est pas exact</x-button>
                        </form>
                    </div>
                @elseif ($collaboration->status->value === 'en_attente')
                    <p class="mt-1 text-xs text-amber-800">En attente de la confirmation de {{ $collaboration->partner->name }}.</p>
                @endif
            </div>
        @endforeach

        <details class="mt-2" @if ($errors->has('summary')) open @endif>
            <summary class="cursor-pointer py-2 font-medium text-brand-700">Déclarer ce que vous avez accompli ensemble</summary>
            <form method="POST" action="{{ route('collaborations.store', $contactRequest) }}" class="mt-2">
                @csrf
                <x-field name="summary" type="textarea" rows="2" label="Ce que vous avez accompli" maxlength="300"
                         placeholder="Ex. : premier lot de 200 kg livré à Thiès"
                         hint="{{ $other?->name }} devra confirmer avant toute publication." />
                <x-button class="w-full">Déclarer</x-button>
            </form>
        </details>
    </x-card>
</section>
