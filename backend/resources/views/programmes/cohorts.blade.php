<x-layout title="Cohortes · {{ $organisation->name }}">
    <a href="{{ route('organisations.manage', $organisation) }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← {{ $organisation->name }}</a>
    <h1 class="text-2xl font-bold">Cohortes</h1>
    <p class="mt-1 text-sm text-stone-600">Suivez les projets que vous accompagnez : score de préparation, besoins ouverts, dernières mises à jour et vos points de suivi.</p>

    <div class="mt-4">
        @foreach ($cohorts as $cohort)
            <a href="{{ route('cohorts.show', $cohort) }}" class="mb-2 block rounded-xl border border-stone-200 bg-white p-3 text-sm">
                <span class="font-semibold">{{ $cohort->name }}</span>
                <span class="block text-xs text-stone-500">{{ $cohort->members_count }} projet{{ $cohort->members_count > 1 ? 's' : '' }}@if ($cohort->starts_on) · depuis le {{ $cohort->starts_on->translatedFormat('d F Y') }}@endif</span>
            </a>
        @endforeach
    </div>

    <x-card class="mt-4">
        <details @if ($errors->any()) open @endif>
            <summary class="cursor-pointer py-1 font-semibold text-brand-700">Créer une cohorte</summary>
            <form method="POST" action="{{ route('cohorts.store', $organisation) }}" class="mt-3">
                @csrf
                <x-field name="name" label="Nom" placeholder="Ex. : Cohorte 2027" maxlength="150" />
                <x-field name="starts_on" type="date" label="Début (facultatif)" />
                <x-field name="ends_on" type="date" label="Fin (facultatif)" />
                @if ($programmes->isNotEmpty())
                    <x-select name="opportunity_id" label="Programme lié (facultatif)" :options="$programmes->all()" placeholder="Aucun" />
                @endif
                <x-button class="w-full">Créer</x-button>
            </form>
        </details>
    </x-card>
</x-layout>
