<x-layout :title="$journey->title">
    <a href="{{ route('journeys.index') }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← Parcours</a>

    <p class="text-sm text-stone-500">{{ $journey->situation }} · {{ $journey->situation_hint }}</p>
    <h1 class="mt-1 text-2xl font-bold leading-tight">{{ $journey->title }}</h1>
    <p class="mt-2 text-stone-700">{{ $journey->summary }}</p>

    @if ($hasWolof)
        <nav aria-label="Langue des fiches" class="mt-3 inline-flex rounded-xl bg-stone-100 p-1 text-sm font-medium">
            <a href="{{ route('journeys.show', $journey) }}" @if (! $wolof) aria-current="true" @endif class="rounded-lg px-3 py-1.5 {{ $wolof ? 'text-stone-600' : 'bg-white text-brand-800 shadow-sm' }}">Français</a>
            <a href="{{ route('journeys.show', [$journey, 'langue' => 'wo']) }}" lang="wo" @if ($wolof) aria-current="true" @endif class="rounded-lg px-3 py-1.5 {{ $wolof ? 'bg-white text-brand-800 shadow-sm' : 'text-stone-600' }}">Wolof</a>
        </nav>
    @endif

    @auth
        @if ($following)
            <div class="mt-4">
                <p class="text-sm font-medium">{{ $doneCount }} étape{{ $doneCount > 1 ? 's' : '' }} terminée{{ $doneCount > 1 ? 's' : '' }} sur {{ $journey->steps->count() }}</p>
                <x-progress :done="$doneCount" :total="$journey->steps->count()" class="mt-2" />
            </div>
        @else
            <form method="POST" action="{{ route('journeys.start', $journey) }}" class="mt-4">
                @csrf
                <x-button class="w-full">Commencer ce parcours</x-button>
            </form>
            <p class="mt-2 text-xs text-stone-500">Les étapes rejoignent vos démarches : vous notez où vous en êtes et vous réglez des rappels.</p>
        @endif
    @else
        <x-card class="mt-4 text-sm text-stone-700">
            <a href="{{ route('login') }}" class="font-semibold text-brand-700 underline">Connectez-vous</a>
            pour suivre votre avancement étape par étape et recevoir des rappels.
        </x-card>
    @endauth

    <ol class="mt-6 space-y-3">
        @foreach ($journey->steps as $step)
            @php($item = $progress->get($step->id))
            <li id="etape-{{ $step->id }}">
                <details class="rounded-2xl border border-stone-200 bg-white shadow-sm" @if ($loop->first && ! $following) open @endif>
                    <summary class="flex cursor-pointer items-start gap-3 p-4">
                        <span aria-hidden="true" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-sm font-semibold {{ $item?->status->isDone() ? 'bg-brand-700 text-white' : 'bg-stone-100 text-stone-700' }}">
                            {{ $item?->status->isDone() ? '✓' : $loop->iteration }}
                        </span>
                        <span class="flex-1">
                            <span class="block font-semibold leading-snug" @if ($wolof && $step->title_wo) lang="wo" @endif>{{ $wolof && $step->title_wo ? $step->title_wo : $step->title }}</span>
                            <span class="mt-1 flex flex-wrap gap-1">
                                @if ($item)<x-status :status="$item->status" />@endif
                                @if ($step->needs_expert)
                                    <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-800">À valider avec un expert</span>
                                @endif
                            </span>
                        </span>
                    </summary>

                    <div class="border-t border-stone-100 px-4 pb-4 pt-3 text-sm">
                        @if ($wolof && $step->hasWolof())
                            <p class="text-stone-700" lang="wo">{{ $step->body_wo }}</p>
                        @else
                            @if ($wolof)<p class="mb-2 text-xs text-stone-500">Cette fiche n'est pas encore traduite en wolof.</p>@endif
                            <p class="text-stone-700">{{ $step->body }}</p>
                        @endif
                        @if ($step->audioUrl())
                            <p class="mt-3 text-xs font-medium text-stone-600">Écouter en wolof</p>
                            <audio controls preload="none" src="{{ $step->audioUrl() }}" class="mt-1 w-full"></audio>
                        @endif

                        @if ($step->documents)
                            <h3 class="mt-3 font-semibold">Pièces à réunir</h3>
                            <ul class="mt-1 list-disc space-y-1 pl-5 text-stone-700">
                                @foreach ($step->documents as $document)
                                    <li>{{ $document }}</li>
                                @endforeach
                            </ul>
                        @endif

                        @if ($step->organisation || $step->cost || $step->delay)
                            <dl class="mt-3 space-y-1">
                                @if ($step->organisation)
                                    <div><dt class="inline text-stone-500">Organisme :</dt> <dd class="inline">{{ $step->organisation }}</dd></div>
                                @endif
                                @if ($step->cost)
                                    <div><dt class="inline text-stone-500">Coût indicatif :</dt> <dd class="inline">{{ $step->cost }}</dd></div>
                                @endif
                                @if ($step->delay)
                                    <div><dt class="inline text-stone-500">Délai indicatif :</dt> <dd class="inline">{{ $step->delay }}</dd></div>
                                @endif
                            </dl>
                        @endif

                        <p class="mt-3 text-xs text-stone-500">
                            @if ($step->source_url)
                                Source : <a href="{{ $step->source_url }}" class="underline" target="_blank" rel="noopener">{{ $step->source_label }}</a>@if ($step->checked_on), consultée le {{ $step->checked_on->translatedFormat('d F Y') }}@endif
                                @if ($step->isStale()) · <span class="font-medium text-amber-800">information à revérifier</span>@endif
                            @else
                                Conseil de l'équipe IDEE.
                            @endif
                        </p>

                        <x-step-action :step="$step" />

                        @auth
                            <form method="POST" action="{{ route('journey-steps.update', $step) }}" class="mt-4 rounded-xl bg-stone-50 p-3">
                                @csrf
                                @method('PUT')
                                <fieldset>
                                    <legend class="font-medium text-stone-800">Où en êtes-vous ?</legend>
                                    <div class="mt-2 grid grid-cols-2 gap-2">
                                        @foreach ($statuses as $status)
                                            <label class="flex min-h-11 items-center gap-2 rounded-lg border border-stone-200 bg-white px-2">
                                                <input type="radio" name="status" value="{{ $status->value }}" @checked(($item?->status ?? $statuses[0]) === $status) class="h-5 w-5 accent-brand-700">
                                                <span>{{ $status->label() }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>

                                <label for="rappel-{{ $step->id }}" class="mt-3 block font-medium text-stone-800">Me le rappeler le (facultatif)</label>
                                <input id="rappel-{{ $step->id }}" name="remind_on" type="date" value="{{ $item?->remind_on?->toDateString() }}" min="{{ today()->toDateString() }}"
                                       class="mt-1 block w-full rounded-xl border border-stone-300 px-3 py-3 text-base focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-100">

                                <label for="note-{{ $step->id }}" class="mt-3 block font-medium text-stone-800">Note personnelle (facultatif)</label>
                                <textarea id="note-{{ $step->id }}" name="note" rows="2" maxlength="1000" placeholder="Ex. : dossier déposé le 3 novembre"
                                          class="mt-1 block w-full rounded-xl border border-stone-300 px-3 py-3 text-base focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-100">{{ $item?->note }}</textarea>

                                <x-button class="mt-3 w-full">Enregistrer</x-button>
                            </form>

                            @if ($helpProject)
                                <a href="{{ route('projects.show', [$helpProject, 'etape' => $step->id]) }}#nouveau-besoin" class="mt-2 inline-block py-2 font-medium text-brand-700 underline">Demander de l'aide pour cette étape</a>
                            @else
                                <a href="{{ route('projects.create') }}" class="mt-2 inline-block py-2 font-medium text-brand-700 underline">Publier un projet pour demander de l'aide</a>
                            @endif
                        @endauth
                    </div>
                </details>
            </li>
        @endforeach
    </ol>

    @if ($following)
        <form method="POST" action="{{ route('journeys.leave', $journey) }}" data-confirm="Retirer ce parcours de vos démarches ? Votre avancement sera effacé." class="mt-8 text-center">
            @csrf
            @method('DELETE')
            <button type="submit" class="py-3 text-sm text-stone-600 underline">Retirer ce parcours de mes démarches</button>
        </form>
    @endif

    <x-journey-notice />
</x-layout>
