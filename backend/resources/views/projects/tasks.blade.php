<x-layout title="Tâches · {{ $project->title }}">
    <a href="{{ route('projects.show', $project) }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← {{ $project->title }}</a>

    <h1 class="text-xl font-bold">Tableau de tâches</h1>
    <p class="mb-4 text-sm text-stone-500">Partagé entre le porteur et ses partenaires : {{ $members->pluck('name')->join(', ', ' et ') }}. Les prochaines étapes acceptées dans une conversation s'y ajoutent d'elles-mêmes.</p>

    <x-card>
        <details @if ($errors->any()) open @endif>
            <summary class="cursor-pointer py-1 font-semibold text-brand-700">Ajouter une tâche</summary>
            <form method="POST" action="{{ route('tasks.store', $project) }}" class="mt-3">
                @csrf
                <x-field name="title" label="Tâche" placeholder="Ex. : demander trois devis d'emballage" maxlength="200" />
                <x-field name="due_on" type="date" label="Échéance (facultatif)" />
                <x-select name="assignee_id" label="Confiée à" placeholder="Personne pour le moment"
                          :options="$members->mapWithKeys(fn ($member) => [$member->id => $member->is(auth()->user()) ? 'Moi' : $member->name])->all()" />
                <x-button class="w-full">Ajouter</x-button>
            </form>
        </details>
    </x-card>

    @foreach ($statuses as $status)
        <section class="mt-5" aria-labelledby="colonne-{{ $status->value }}">
            <h2 id="colonne-{{ $status->value }}" class="mb-2 text-base font-bold">{{ $status->label() }} <span class="font-normal text-stone-400">({{ $board[$status->value]->count() }})</span></h2>

            @forelse ($board[$status->value] as $task)
                <article class="mb-2 rounded-2xl border border-stone-200 bg-white p-3 text-sm shadow-sm">
                    <p class="font-medium {{ $status->value === 'fait' ? 'text-stone-500 line-through' : 'text-stone-900' }}">{{ $task->title }}</p>
                    <p class="mt-1 text-xs text-stone-500">
                        {{ $task->assignee ? 'Confiée à '.$task->assignee->name : 'Non attribuée' }}
                        @if ($task->due_on) · avant le {{ $task->due_on->translatedFormat('d F') }}@endif
                        @if ($task->isOverdue())<span class="font-medium text-red-700"> · en retard</span>@endif
                        @if ($task->agreement_id) · étape convenue @endif
                    </p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        @foreach ($statuses as $target)
                            @continue($target === $status)
                            <form method="POST" action="{{ route('tasks.move', $task) }}">
                                @csrf
                                <input type="hidden" name="status" value="{{ $target->value }}">
                                <button type="submit" class="min-h-10 rounded-lg border border-stone-300 px-3 text-xs font-medium text-stone-700">→ {{ $target->label() }}</button>
                            </form>
                        @endforeach
                        @can('delete', $task)
                            <form method="POST" action="{{ route('tasks.destroy', $task) }}" data-confirm="Retirer cette tâche du tableau ?" class="ml-auto">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="py-2 text-xs text-red-700 underline">Retirer</button>
                            </form>
                        @endcan
                    </div>
                </article>
            @empty
                <p class="text-sm text-stone-400">Aucune tâche.</p>
            @endforelse
        </section>
    @endforeach
</x-layout>
