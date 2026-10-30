<x-layout title="Modifier une fiche">
    <x-admin-nav />
    <a href="{{ route('admin.guides') }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← Fiches pratiques</a>
    <p class="text-sm text-stone-500">{{ $step->journey->title }}</p>

    <form method="POST" action="{{ route('admin.guides.update', $step) }}" enctype="multipart/form-data" class="mt-2">
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
        <fieldset class="mb-4 rounded-xl border border-stone-200 p-3">
            <legend class="px-1 text-sm font-semibold">En wolof (PG-07)</legend>
            <p class="mb-2 text-xs text-stone-500">À faire relire par un locuteur natif. Sans texte wolof, la fiche s'affiche en français.</p>
            <x-field name="title_wo" label="Titre en wolof" :value="$step->title_wo" />
            <x-field name="body_wo" type="textarea" rows="5" label="Explication en wolof" :value="$step->body_wo" />
            <label for="audio-wolof" class="block text-sm font-medium text-stone-800">Enregistrement audio en wolof (MP3, M4A, OGG)</label>
            @if ($step->audioUrl())
                <audio controls preload="none" src="{{ $step->audioUrl() }}" class="mt-1 w-full"></audio>
                <label class="mt-1 flex min-h-10 items-center gap-2 text-sm"><input type="hidden" name="remove_audio" value="0"><input type="checkbox" name="remove_audio" value="1" class="h-5 w-5 accent-brand-700"> Retirer l'enregistrement</label>
            @endif
            <input id="audio-wolof" name="audio" type="file" accept="audio/*" class="mt-1 block w-full text-sm">
            @error('audio')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </fieldset>
        <label class="mb-2 flex min-h-11 items-center gap-2 text-sm"><input type="hidden" name="needs_expert" value="0"><input type="checkbox" name="needs_expert" value="1" @checked($step->needs_expert) class="h-5 w-5 accent-brand-700"> À valider avec un expert</label>
        <label class="mb-4 flex min-h-11 items-center gap-2 text-sm"><input type="hidden" name="checked" value="0"><input type="checkbox" name="checked" value="1" class="h-5 w-5 accent-brand-700"> J'ai vérifié cette fiche aujourd'hui auprès de la source</label>
        <x-button class="w-full">Enregistrer</x-button>
    </form>
</x-layout>
