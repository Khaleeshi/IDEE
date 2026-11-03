<x-layout :title="$profile->label()">
    <a href="{{ route('member-profiles.index') }}" class="mb-2 inline-block text-sm text-stone-500">← Mes métiers</a>
    <h1 class="text-2xl font-bold">{{ $profile->profession->name }}</h1>
    <p class="mb-4 text-sm text-stone-600">Le métier ne change pas : pour un autre métier, ajoutez un nouveau profil.</p>

    <x-card>
        <form method="POST" action="{{ route('member-profiles.update', $profile) }}">
            @csrf
            @method('PUT')
            @include('profile.partials.profession-fields', ['profile' => $profile])

            <label class="mb-3 flex items-center gap-3">
                <input type="hidden" name="is_primary" value="0">
                <input type="checkbox" name="is_primary" value="1" @checked(old('is_primary', $profile->is_primary)) class="h-5 w-5 accent-brand-700">
                <span class="text-sm">Profil principal, affiché en premier</span>
            </label>
            <label class="mb-4 flex items-center gap-3">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" @checked(old('active', $profile->active)) class="h-5 w-5 accent-brand-700">
                <span class="text-sm">Profil actif (décochez pour le mettre en pause sans le supprimer)</span>
            </label>
            <x-button class="w-full">Enregistrer</x-button>
        </form>
    </x-card>

    <form method="POST" action="{{ route('member-profiles.destroy', $profile) }}">
        @csrf
        @method('DELETE')
        <x-button variant="danger" class="w-full">Supprimer ce profil</x-button>
    </form>
</x-layout>
