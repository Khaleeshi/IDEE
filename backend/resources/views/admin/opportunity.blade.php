<x-layout :title="$opportunity->exists ? 'Modifier une opportunité' : 'Nouvelle opportunité'">
    <x-admin-nav />
    <a href="{{ route('admin.opportunities') }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← Opportunités</a>

    <form method="POST" action="{{ $opportunity->exists ? route('admin.opportunities.update', $opportunity) : route('admin.opportunities.store') }}">
        @csrf
        @if ($opportunity->exists) @method('PUT') @endif
        <x-field name="title" label="Titre" :value="$opportunity->title" required />
        <x-field name="organisation" label="Organisme" :value="$opportunity->organisation" required />
        <x-select name="kind" label="Type" :options="collect($kinds)->mapWithKeys(fn ($kind) => [$kind->value => $kind->label()])" :value="$opportunity->kind?->value" />
        <x-field name="summary" type="textarea" rows="4" label="Présentation" :value="$opportunity->summary" required />
        <x-field name="criteria" type="textarea" rows="4" label="Critères" :value="$opportunity->criteria" />
        <fieldset class="mb-4">
            <legend class="text-sm font-medium text-stone-800">Stades acceptés (aucun = tous)</legend>
            <div class="mt-1 grid grid-cols-2 gap-1 text-sm">
                @foreach ($stages as $stage)
                    <label class="flex min-h-10 items-center gap-2"><input type="checkbox" name="stages[]" value="{{ $stage->value }}" @checked(in_array($stage->value, old('stages', $opportunity->stages ?? []), true)) class="h-5 w-5 accent-brand-700"> {{ $stage->label() }}</label>
                @endforeach
            </div>
        </fieldset>
        <fieldset class="mb-4">
            <legend class="text-sm font-medium text-stone-800">Secteurs visés (aucun = tous)</legend>
            <div class="mt-1 grid grid-cols-2 gap-1 text-sm">
                @foreach ($sectors as $slug => $name)
                    <label class="flex min-h-10 items-center gap-2"><input type="checkbox" name="sectors[]" value="{{ $slug }}" @checked(in_array($slug, old('sectors', $opportunity->sectors ?? []), true)) class="h-5 w-5 accent-brand-700"> {{ $name }}</label>
                @endforeach
            </div>
        </fieldset>
        <x-field name="max_amount_fcfa" type="number" inputmode="numeric" min="0" label="Plafond de financement en FCFA (facultatif)" :value="$opportunity->max_amount_fcfa" />
        <x-field name="amount" label="Montant" :value="$opportunity->amount" />
        <x-field name="deadline" type="date" label="Date limite" :value="$opportunity->deadline?->toDateString()" />
        <x-field name="deadline_note" label="Calendrier, sans date limite" :value="$opportunity->deadline_note" placeholder="Ex. : candidatures toute l'année" />
        <x-field name="url" type="url" label="Lien officiel" :value="$opportunity->url" />
        <x-field name="source_label" label="Nom de la source" :value="$opportunity->source_label" />
        <label class="mb-4 flex min-h-11 items-center gap-2 text-sm"><input type="hidden" name="is_published" value="0"><input type="checkbox" name="is_published" value="1" @checked($opportunity->is_published) class="h-5 w-5 accent-brand-700"> Publiée dans le répertoire</label>
        <x-button class="w-full">Enregistrer</x-button>
    </form>

    @if ($opportunity->exists)
        <form method="POST" action="{{ route('admin.opportunities.destroy', $opportunity) }}" data-confirm="Supprimer cette opportunité ?" class="mt-6 text-center">
            @csrf
            @method('DELETE')
            <button type="submit" class="py-3 text-sm text-red-700 underline">Supprimer</button>
        </form>
    @endif
</x-layout>
