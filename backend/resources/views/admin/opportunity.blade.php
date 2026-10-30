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
