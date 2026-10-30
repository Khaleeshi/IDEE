<x-layout :title="$project->exists ? 'Modifier le projet' : 'Publier un projet'">
    <h1 class="mb-1 text-2xl font-bold">{{ $project->exists ? 'Modifier le projet' : 'Publier un projet' }}</h1>
    <p class="mb-6 text-stone-600">Quelques phrases suffisent pour commencer. Vous pourrez compléter plus tard.</p>

    <x-card>
        <form method="POST" action="{{ $project->exists ? route('projects.update', $project) : route('projects.store') }}">
            @csrf
            @if ($project->exists) @method('PUT') @endif

            <x-field name="title" label="Nom du projet" :value="$project->title" required maxlength="150"
                     placeholder="Ex. : Marque de cosmétiques au karité" />
            <x-field name="summary" type="textarea" rows="5" label="Résumé public" :value="$project->summary" required
                     hint="Visible par tous : le problème, votre solution, vos clients. Ne mettez rien de confidentiel." />
            <x-field name="details" type="textarea" rows="5" label="Détails privés (facultatif)" :value="$project->details"
                     hint="Visibles par vous seul : chiffres, fournisseurs, idées à protéger." />
            <x-select name="sector_id" label="Secteur" :options="$sectors" :value="$project->sector_id" placeholder="Choisir un secteur" />
            <x-field name="region" label="Région" :value="$project->region" placeholder="Ex. : Dakar, Thiès, Ziguinchor…" />
            <x-select name="stage" label="Où en est le projet ?" :options="$stages" :value="$project->stage?->value ?? 'idee'" />

            <x-button class="w-full">{{ $project->exists ? 'Enregistrer' : 'Publier le projet' }}</x-button>
        </form>
    </x-card>

    <p class="text-xs text-stone-500">Chaque version de votre projet est datée et scellée : elle prouve l'antériorité de votre texte.</p>
</x-layout>
