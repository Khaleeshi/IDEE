<x-layout title="Mes métiers">
    <a href="{{ route('profile.edit') }}" class="mb-2 inline-block text-sm text-stone-500">← Mon profil</a>
    <h1 class="text-2xl font-bold">Mes métiers</h1>
    <p class="mb-4 text-sm text-stone-600">Un seul compte, plusieurs casquettes : coach, chef de projet, formatrice… Ajoutez un profil par métier, jusqu'à {{ $max }}.</p>

    @if (session('status'))
        <p class="mb-4 rounded-xl bg-brand-50 p-3 text-sm text-brand-800" role="status">{{ session('status') }}</p>
    @endif

    @forelse ($profiles as $profile)
        <x-card>
            <div class="flex items-start justify-between gap-2">
                <div>
                    <h2 class="font-semibold">{{ $profile->label() }}</h2>
                    <p class="text-xs text-stone-500">{{ $profile->profession->name }}@if ($profile->experience_years !== null) · {{ $profile->experience_years }} an{{ $profile->experience_years > 1 ? 's' : '' }} d'expérience @endif</p>
                </div>
                <div class="flex shrink-0 flex-col items-end gap-1">
                    @if ($profile->is_primary)<span class="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-800">Principal</span>@endif
                    @if (! $profile->active)
                        <span class="rounded-full bg-stone-100 px-2 py-0.5 text-xs font-medium text-stone-600">En pause</span>
                    @elseif (! $profile->isPublic())
                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-900">Visible après vérification</span>
                    @endif
                </div>
            </div>
            <p class="mt-2 text-sm text-stone-700">{{ implode(' · ', $profile->offerLabels()) }}@if ($profile->rate) · {{ $profile->rate }} @endif</p>
            @if (! $profile->isPublic() && $profile->active)
                <p class="mt-2 text-xs text-stone-500">Les autres membres le verront après la <a href="{{ route('profile.edit') }}#verification" class="underline">vérification de votre identité</a>.</p>
            @endif
            <x-button :href="route('member-profiles.edit', $profile)" variant="secondary" class="mt-3 min-h-10 w-full py-2 text-sm">Modifier</x-button>
        </x-card>
    @empty
        <p class="mb-4 rounded-xl border border-dashed border-stone-300 p-4 text-sm text-stone-600">Vous n'avez encore aucun métier. Le premier ajouté deviendra votre profil principal.</p>
    @endforelse

    @if ($profiles->count() < $max)
        <x-card>
            <h2 class="mb-3 font-semibold">Ajouter un métier</h2>
            <form method="POST" action="{{ route('member-profiles.store') }}">
                @csrf
                <div class="mb-4">
                    <label for="champ-profession_id" class="block text-sm font-medium text-stone-800">Métier</label>
                    <select id="champ-profession_id" name="profession_id" required
                            class="mt-1 block w-full rounded-xl border border-stone-300 bg-white px-3 py-3 text-base focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-100">
                        <option value="">Choisir dans la liste</option>
                        @foreach ($professions as $category => $options)
                            <optgroup label="{{ $category }}">
                                @foreach ($options as $id => $name)
                                    <option value="{{ $id }}" @selected((string) old('profession_id') === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-stone-500">Votre métier manque ? Écrivez à l'équipe IDEE : elle l'ajoutera à la liste.</p>
                    @error('profession_id')
                        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                @include('profile.partials.profession-fields', ['profile' => null])
                <x-button class="w-full">Ajouter ce métier</x-button>
            </form>
        </x-card>
    @endif
</x-layout>
