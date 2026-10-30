<x-layout :title="$programme->exists ? 'Modifier un programme' : 'Publier un programme'">
    <a href="{{ route('organisations.manage', $organisation) }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← {{ $organisation->name }}</a>
    <h1 class="text-2xl font-bold">{{ $programme->exists ? 'Modifier le programme' : 'Publier un programme' }}</h1>
    <p class="mt-1 text-sm text-stone-600">Il apparaît dans le répertoire des programmes et financements, au nom de {{ $organisation->name }}. Les démarches doivent rester gratuites pour les candidats.</p>

    <form method="POST" action="{{ $programme->exists ? route('programmes.update', $programme) : route('programmes.store', $organisation) }}" class="mt-4">
        @csrf
        @if ($programme->exists) @method('PUT') @endif
        <x-field name="title" label="Nom du programme" :value="$programme->title" required maxlength="255" />
        <x-select name="kind" label="Type" :options="collect($kinds)->mapWithKeys(fn ($kind) => [$kind->value => $kind->label()])->all()" :value="$programme->kind?->value" />
        <x-field name="summary" type="textarea" rows="4" label="Présentation" :value="$programme->summary" required maxlength="3000" />
        <x-field name="criteria" type="textarea" rows="3" label="Critères (facultatif)" :value="$programme->criteria" maxlength="3000" />
        <fieldset class="mb-4">
            <legend class="text-sm font-medium text-stone-800">Stades acceptés (aucun = tous)</legend>
            <div class="mt-1 grid grid-cols-2 gap-1 text-sm">
                @foreach ($stages as $stage)
                    <label class="flex min-h-10 items-center gap-2"><input type="checkbox" name="stages[]" value="{{ $stage->value }}" @checked(in_array($stage->value, old('stages', $programme->stages ?? []), true)) class="h-5 w-5 accent-brand-700"> {{ $stage->label() }}</label>
                @endforeach
            </div>
        </fieldset>
        <fieldset class="mb-4">
            <legend class="text-sm font-medium text-stone-800">Secteurs visés (aucun = tous)</legend>
            <div class="mt-1 grid grid-cols-2 gap-1 text-sm">
                @foreach ($sectors as $slug => $name)
                    <label class="flex min-h-10 items-center gap-2"><input type="checkbox" name="sectors[]" value="{{ $slug }}" @checked(in_array($slug, old('sectors', $programme->sectors ?? []), true)) class="h-5 w-5 accent-brand-700"> {{ $name }}</label>
                @endforeach
            </div>
        </fieldset>
        <x-field name="max_amount_fcfa" type="number" inputmode="numeric" min="0" label="Plafond de financement en FCFA (facultatif)" :value="$programme->max_amount_fcfa" />
        <x-field name="amount" label="Montant ou avantages (facultatif)" :value="$programme->amount" placeholder="Ex. : jusqu'à 5 millions FCFA" maxlength="255" />
        <x-field name="deadline" type="date" label="Date limite (facultatif)" :value="$programme->deadline?->toDateString()" />
        <x-field name="deadline_note" label="Calendrier, sans date limite" :value="$programme->deadline_note" placeholder="Ex. : candidatures toute l'année" maxlength="255" />
        <x-field name="url" type="url" label="Lien pour candidater (facultatif)" :value="$programme->url" placeholder="https://" maxlength="500" />
        {{-- OR-02 : candidatures envoyées depuis un dossier IDEE. --}}
        <fieldset class="mb-4 rounded-xl border border-stone-200 p-3">
            <legend class="px-1 text-sm font-semibold">Candidatures sur IDEE</legend>
            <label class="flex min-h-11 items-center gap-2 text-sm"><input type="hidden" name="accepts_applications" value="0"><input type="checkbox" name="accepts_applications" value="1" @checked(old('accepts_applications', $programme->accepts_applications)) class="h-5 w-5 accent-brand-700"> Recevoir les candidatures depuis IDEE</label>
            <p class="mb-2 text-xs text-stone-500">Chaque candidat répond à trois questions communes (avancement, besoins, attentes) et peut joindre son business plan.</p>
            <x-field name="application_questions" type="textarea" rows="3" label="Vos questions en plus (trois au plus, une par ligne)" :value="implode("\n", $programme->application_questions ?? [])" />
        </fieldset>
        <x-button class="w-full">{{ $programme->exists ? 'Enregistrer' : 'Publier' }}</x-button>
    </form>

    @if ($programme->exists)
        <form method="POST" action="{{ route('programmes.destroy', $programme) }}" data-confirm="Retirer ce programme du répertoire ?" class="mt-6 text-center">
            @csrf
            @method('DELETE')
            <button type="submit" class="py-3 text-sm text-red-700 underline">Retirer du répertoire</button>
        </form>
    @endif
</x-layout>
