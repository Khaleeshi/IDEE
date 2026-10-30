<x-layout :title="$cohort->name">
    <a href="{{ route('cohorts.index', $cohort->organisation) }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← Cohortes</a>
    <h1 class="text-2xl font-bold">{{ $cohort->name }}</h1>
    <p class="mt-1 text-sm text-stone-500">{{ $members->count() }} projet{{ $members->count() > 1 ? 's' : '' }}@if ($cohort->ends_on) · jusqu'au {{ $cohort->ends_on->translatedFormat('d F Y') }}@endif</p>

    @if ($retained->isNotEmpty())
        <x-card class="mt-4">
            <form method="POST" action="{{ route('cohorts.add', $cohort) }}">
                @csrf
                <x-select name="application_id" label="Ajouter un projet retenu" :options="$retained->mapWithKeys(fn ($application) => [$application->id => $application->project->title])->all()" />
                <x-button variant="secondary" class="w-full">Ajouter à la cohorte</x-button>
            </form>
        </x-card>
    @endif

    @forelse ($members as $member)
        @php($project = $member->project)
        <x-card class="mt-3" id="projet-{{ $member->id }}">
            <div class="flex items-start justify-between gap-2">
                <a href="{{ route('projects.show', $project) }}" class="font-semibold underline">{{ $project->title }}</a>
                @if ($member->latestCheckin)
                    <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium {{ match ($member->latestCheckin->status->value) { 'bonne_voie' => 'bg-brand-50 text-brand-800', 'a_risque' => 'bg-amber-50 text-amber-800', default => 'bg-red-50 text-red-800' } }}">{{ $member->latestCheckin->status->label() }}</span>
                @endif
            </div>
            <dl class="mt-2 grid grid-cols-3 gap-2 text-center text-xs">
                <div class="rounded-xl bg-stone-50 p-2"><dt class="text-stone-500">Score</dt><dd class="text-base font-bold">{{ $project->readiness_score }}</dd></div>
                <div class="rounded-xl bg-stone-50 p-2"><dt class="text-stone-500">Besoins ouverts</dt><dd class="text-base font-bold">{{ $project->openNeeds->count() }}</dd></div>
                <div class="rounded-xl bg-stone-50 p-2"><dt class="text-stone-500">Dernière activité</dt><dd class="text-sm font-semibold">{{ $project->last_activity_at?->diffForHumans(short: true) ?? '—' }}</dd></div>
            </dl>
            @if ($member->latestCheckin)
                <p class="mt-2 text-sm text-stone-700">{{ $member->latestCheckin->note }} <span class="text-xs text-stone-400">· {{ $member->latestCheckin->created_at->translatedFormat('d F') }}</span></p>
            @endif
            <details class="mt-2">
                <summary class="cursor-pointer py-2 text-sm font-medium text-brand-700">Noter un point de suivi</summary>
                <form method="POST" action="{{ route('cohorts.checkin', $member) }}" class="mt-2">
                    @csrf
                    <x-select name="status" label="Où en est le projet ?" :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()" value="bonne_voie" />
                    <x-field name="note" type="textarea" rows="2" label="Note" maxlength="2000" placeholder="Ex. : premier client signé, recherche d'un local en cours" />
                    <x-button variant="secondary" class="w-full">Enregistrer</x-button>
                </form>
            </details>
        </x-card>
    @empty
        <x-card class="mt-4 text-sm text-stone-600">Aucun projet dans la cohorte. Ajoutez les projets retenus à vos programmes.</x-card>
    @endforelse
</x-layout>
