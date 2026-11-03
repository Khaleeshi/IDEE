<x-layout :title="$profession->name">
    <a href="{{ route('admin.professions') }}" class="mb-2 inline-block text-sm text-stone-500">← Métiers</a>
    <h1 class="mb-1 text-2xl font-bold">{{ $profession->name }}</h1>
    <p class="mb-4 text-sm text-stone-500">{{ $profession->member_profiles_count }} profil{{ $profession->member_profiles_count > 1 ? 's' : '' }} de membres utilisent ce métier.</p>

    <x-card>
        <form method="POST" action="{{ route('admin.professions.update', $profession) }}">
            @csrf
            @method('PUT')
            <x-select name="profession_category_id" label="Catégorie" :options="$categories" :value="$profession->profession_category_id" />
            <x-field name="name" label="Nom affiché" :value="$profession->name" maxlength="120" required />
            <x-field name="description" label="Description (facultatif)" :value="$profession->description" maxlength="300" />
            <label class="mb-3 flex items-center gap-3 text-sm">
                <input type="checkbox" name="regulated" value="1" @checked($profession->regulated) class="h-5 w-5 accent-brand-700">
                Profession réglementée (identité vérifiée exigée pour l'afficher)
            </label>
            <label class="mb-4 flex items-center gap-3 text-sm">
                <input type="checkbox" name="active" value="1" @checked($profession->active) class="h-5 w-5 accent-brand-700">
                Proposé aux membres (décocher pour le retirer de la liste)
            </label>
            <x-button class="w-full">Enregistrer</x-button>
        </form>
    </x-card>
</x-layout>
