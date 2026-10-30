<x-layout title="Mon profil">
    <h1 class="mb-4 text-2xl font-bold">Mon profil</h1>

    <x-card>
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PUT')

            <x-field name="name" label="Nom" :value="$user->name" required autocomplete="name" />
            <x-field name="region" label="Région" :value="$user->region" placeholder="Ex. : Dakar, ou Paris pour la diaspora" />
            <x-field name="bio" type="textarea" rows="3" label="Présentation" :value="$user->bio" placeholder="Votre parcours, ce que vous cherchez, ce que vous pouvez apporter." />
            <x-field name="skills" label="Compétences" :value="implode(', ', $user->skills ?? [])" hint="Séparées par des virgules. Ex. : vente, comptabilité, Flutter" />
            <x-field name="languages" label="Langues" :value="implode(', ', $user->languages ?? [])" hint="Ex. : français, wolof, pulaar" />

            <fieldset class="mb-4">
                <legend class="text-sm font-medium text-stone-800">Mes rôles</legend>
                <div class="mt-2 space-y-2">
                    @foreach ($roles as $role)
                        <label class="flex min-h-12 items-center gap-3 rounded-xl border border-stone-200 px-3">
                            <input type="checkbox" name="roles[]" value="{{ $role->value }}" @checked($user->hasRole($role)) class="h-5 w-5 accent-brand-700">
                            <span class="py-2">
                                {{ $role->label() }}
                                @if ($role->requiresIdentityVerification() && ! $user->hasVerifiedIdentity())
                                    <span class="block text-xs text-stone-500">Affiché publiquement après vérification d'identité</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <fieldset class="mb-4">
                <legend class="text-sm font-medium text-stone-800">Secteurs qui m'intéressent</legend>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($sectors as $slug => $sectorName)
                        <label class="inline-flex min-h-10 items-center gap-2 rounded-full border border-stone-200 px-3 text-sm">
                            <input type="checkbox" name="sectors[]" value="{{ $slug }}" @checked(in_array($slug, $user->sectors ?? [], true)) class="accent-brand-700">
                            {{ $sectorName }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <label class="mb-6 flex min-h-12 items-center gap-3 rounded-xl border border-stone-200 px-3">
                <input type="checkbox" name="accepts_requests" value="1" @checked($user->contact_preferences['accepts_requests'] ?? true) class="h-5 w-5 accent-brand-700">
                <span>J'accepte de recevoir des demandes de contact</span>
            </label>

            <x-button class="w-full">Enregistrer</x-button>
        </form>
    </x-card>

    <x-card class="text-sm">
        <p><span class="text-stone-500">Téléphone :</span> {{ \App\Support\PhoneNumber::format($user->phone) }}</p>
        <p class="mt-1"><span class="text-stone-500">Vérification :</span> {{ $user->verification_level->label() }}</p>
        <p class="mt-1"><span class="text-stone-500">Demandes de contact par semaine :</span> {{ $user->weeklyContactQuota() }}</p>
        <a href="{{ route('members.show', $user) }}" class="mt-3 inline-block font-medium text-brand-700 underline">Voir mon profil public</a>
    </x-card>

    <form method="POST" action="{{ route('logout') }}" class="text-center">
        @csrf
        <button type="submit" class="py-3 text-sm font-medium text-red-700 underline">Se déconnecter</button>
    </form>
</x-layout>
