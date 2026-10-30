<x-layout :title="$project->exists ? 'Modifier le projet' : 'Publier un projet'">
    <h1 class="mb-1 text-2xl font-bold">{{ $project->exists ? 'Modifier le projet' : 'Publier un projet' }}</h1>
    <p class="mb-6 text-stone-600">Quelques phrases suffisent pour commencer. Vous pourrez compléter plus tard.</p>

    @unless ($project->exists)
        {{-- Création guidée en cinq questions (PR-01). --}}
        <p class="mb-4 rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-800">Astuce : touchez le micro de votre clavier pour dicter vos réponses au lieu de les écrire.</p>
        <form method="POST" action="{{ route('projects.store') }}">
            @csrf
            <x-card>
                <h2 class="mb-3 font-semibold">1. Comment s'appelle votre projet ?</h2>
                <x-field name="title" label="Nom du projet" required maxlength="150" placeholder="Ex. : Marque de cosmétiques au karité" />
            </x-card>
            <x-card>
                <h2 class="mb-3 font-semibold">2. Quel problème résolvez-vous, et pour qui ?</h2>
                <x-field name="problem" type="textarea" rows="3" label="Le problème et vos clients" required
                         placeholder="Ex. : les femmes de Thiès trouvent difficilement des soins naturels à prix abordable." />
            </x-card>
            <x-card>
                <h2 class="mb-3 font-semibold">3. Que proposez-vous ?</h2>
                <x-field name="solution" type="textarea" rows="3" label="Votre solution" required
                         hint="Le problème et la solution forment le résumé public : n'y mettez rien de confidentiel."
                         placeholder="Ex. : une gamme de crèmes au karité fabriquées localement, vendues en ligne et en salon." />
            </x-card>
            <x-card>
                <h2 class="mb-3 font-semibold">4. Où en êtes-vous ?</h2>
                <x-select name="stage" label="Stade du projet" :options="$stages" value="idee" />
                <x-select name="sector_id" label="Secteur" :options="$sectors" placeholder="Choisir un secteur" />
                <x-field name="region" label="Région" placeholder="Ex. : Dakar, Thiès, Ziguinchor…" />
            </x-card>
            <x-card>
                <h2 class="mb-3 font-semibold">5. De quoi avez-vous besoin en premier ?</h2>
                <x-select name="first_need_type" label="Votre premier besoin (facultatif)" :options="$needTypes" placeholder="Je le préciserai plus tard" />
                <x-field name="first_need_description" type="textarea" rows="3" label="Précisez" placeholder="Ex. : un associé commercial avec un réseau de salons de beauté à Dakar." />
            </x-card>
            <x-button class="w-full">Publier le projet</x-button>
        </form>
    @else
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
    @endunless

    <p class="text-xs text-stone-500">Chaque version de votre projet est datée et scellée : elle prouve l'antériorité de votre texte.</p>
</x-layout>
