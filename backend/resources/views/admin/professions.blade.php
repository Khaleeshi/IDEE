<x-layout title="Métiers">
    <h1 class="mb-3 text-2xl font-bold">Métiers</h1>
    <x-admin-nav />
    <p class="mb-3 text-sm text-stone-600">Les métiers que les membres choisissent pour leurs profils (PM-01). Un métier ne se supprime pas : désactivez-le, les profils qui l'utilisent le gardent.</p>

    @if (session('status'))
        <p class="mb-4 rounded-xl bg-brand-50 p-3 text-sm text-brand-800" role="status">{{ session('status') }}</p>
    @endif

    <x-card>
        <h2 class="mb-3 font-semibold">Ajouter un métier</h2>
        <form method="POST" action="{{ route('admin.professions.store') }}">
            @csrf
            <x-select name="profession_category_id" label="Catégorie" :options="$categories->pluck('name', 'id')" placeholder="Choisir" />
            <x-field name="name" label="Nom du métier" maxlength="120" required />
            <label class="mb-4 flex items-center gap-3 text-sm">
                <input type="checkbox" name="regulated" value="1" class="h-5 w-5 accent-brand-700">
                Profession réglementée (identité vérifiée exigée pour l'afficher)
            </label>
            <x-button class="w-full">Ajouter</x-button>
        </form>
    </x-card>

    @foreach ($categories as $category)
        <h2 class="mb-2 mt-5 font-semibold">{{ $category->name }} <span class="text-sm font-normal text-stone-500">· {{ $category->professions->count() }}</span></h2>
        <ul class="divide-y divide-stone-100 rounded-2xl border border-stone-200 bg-white text-sm">
            @foreach ($category->professions as $profession)
                <li>
                    <a href="{{ route('admin.professions.edit', $profession) }}" class="flex items-center justify-between gap-2 p-3">
                        <span class="{{ $profession->active ? '' : 'text-stone-400 line-through' }}">{{ $profession->name }}</span>
                        <span class="flex shrink-0 items-center gap-2 text-xs text-stone-500">
                            @if ($profession->regulated)<span class="rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-900">Réglementée</span>@endif
                            {{ $profession->member_profiles_count }} profil{{ $profession->member_profiles_count > 1 ? 's' : '' }}
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endforeach

    <x-card class="mt-6">
        <h2 class="mb-3 font-semibold">Ajouter une catégorie</h2>
        <form method="POST" action="{{ route('admin.professions.categories.store') }}">
            @csrf
            <x-field name="name" label="Nom de la catégorie" maxlength="120" required />
            <x-button variant="secondary" class="w-full">Ajouter la catégorie</x-button>
        </form>
    </x-card>
</x-layout>
