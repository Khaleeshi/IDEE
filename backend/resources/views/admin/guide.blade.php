<x-layout title="Modifier une fiche">
    <x-admin-nav />
    <a href="{{ route('admin.guides') }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← Fiches pratiques</a>
    <p class="text-sm text-stone-500">{{ $step->journey->title }}</p>

    <form method="POST" action="{{ route('admin.guides.update', $step) }}" class="mt-2">
        @csrf
        @method('PUT')
        <x-field name="title" label="Titre" :value="$step->title" required />
        <x-field name="body" type="textarea" rows="5" label="Explication" :value="$step->body" required />
        <x-field name="documents" type="textarea" rows="4" label="Pièces à réunir (une par ligne)" :value="implode("\n", $step->documents ?? [])" />
        <x-field name="organisation" label="Organisme" :value="$step->organisation" />
        <x-field name="cost" label="Coût indicatif" :value="$step->cost" />
        <x-field name="delay" label="Délai indicatif" :value="$step->delay" />
        <x-field name="source_label" label="Nom de la source" :value="$step->source_label" />
        <x-field name="source_url" type="url" label="Adresse de la source" :value="$step->source_url" />
        <label class="mb-2 flex min-h-11 items-center gap-2 text-sm"><input type="hidden" name="needs_expert" value="0"><input type="checkbox" name="needs_expert" value="1" @checked($step->needs_expert) class="h-5 w-5 accent-brand-700"> À valider avec un expert</label>
        <label class="mb-4 flex min-h-11 items-center gap-2 text-sm"><input type="hidden" name="checked" value="0"><input type="checkbox" name="checked" value="1" class="h-5 w-5 accent-brand-700"> J'ai vérifié cette fiche aujourd'hui auprès de la source</label>
        <x-button class="w-full">Enregistrer</x-button>
    </form>
</x-layout>
