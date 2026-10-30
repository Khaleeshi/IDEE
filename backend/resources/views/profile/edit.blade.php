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

            <label class="mb-3 flex min-h-12 items-center gap-3 rounded-xl border border-stone-200 px-3">
                <input type="checkbox" name="accepts_requests" value="1" @checked($user->contact_preferences['accepts_requests'] ?? true) class="h-5 w-5 accent-brand-700">
                <span>J'accepte de recevoir des demandes de contact</span>
            </label>

            <label class="mb-6 flex min-h-12 items-center gap-3 rounded-xl border border-stone-200 px-3">
                <input type="checkbox" name="whatsapp_alerts" value="1" @checked($user->contact_preferences['whatsapp_alerts'] ?? true) class="h-5 w-5 accent-brand-700">
                <span>Recevoir les alertes importantes sur WhatsApp</span>
            </label>

            <x-button class="w-full">Enregistrer</x-button>
        </form>
    </x-card>

    <x-card class="text-sm">
        <p><span class="text-stone-500">Téléphone :</span> {{ \App\Support\PhoneNumber::format($user->phone) }}</p>
        <p class="mt-1"><span class="text-stone-500">Vérification :</span> {{ $user->verification_level->label() }}</p>
        <p class="mt-1"><span class="text-stone-500">Demandes de contact par semaine :</span> {{ $user->weeklyContactQuota() }}</p>
        <a href="{{ route('members.show', $user) }}" class="mt-3 inline-block font-medium text-brand-700 underline">Voir mon profil public</a>
        @can('administrer')
            <a href="{{ route('admin.dashboard') }}" class="mt-2 block font-medium text-brand-700 underline">Administration de l'équipe IDEE</a>
        @endcan
    </x-card>

    <x-card id="verification">
        <h2 class="font-semibold">Vérification d'identité</h2>
        <p class="mt-1 text-sm text-stone-600">Obligatoire pour apparaître comme investisseur ou expert. La pièce est effacée dès qu'elle est vérifiée.</p>
        @if ($user->verification_level->isAtLeast(\App\Enums\VerificationLevel::Identity))
            <p class="mt-3 text-sm font-medium text-brand-800">✓ Identité vérifiée</p>
        @elseif ($verification?->status->value === 'en_attente')
            <p class="mt-3 text-sm font-medium text-amber-800">Votre pièce est en cours d'examen.</p>
        @else
            @if ($verification?->status->value === 'refusee')
                <p class="mt-3 text-sm text-red-700">Dernière demande refusée : {{ $verification->refusal_reason }}</p>
            @endif
            <form method="POST" action="{{ route('verification.store') }}" enctype="multipart/form-data" class="mt-3">
                @csrf
                <label for="piece-identite" class="block text-sm font-medium text-stone-800">Photo de votre carte d'identité ou de votre passeport</label>
                <input id="piece-identite" name="document" type="file" required accept="image/jpeg,image/png,.pdf" class="mt-1 block w-full text-sm">
                <x-button variant="secondary" class="mt-3 w-full">Envoyer pour vérification</x-button>
            </form>
        @endif
    </x-card>

    @if ($decisions->isNotEmpty())
        <x-card>
            <h2 class="font-semibold">Décisions de modération</h2>
            <ul class="mt-2 space-y-2 text-sm">
                @foreach ($decisions as $decision)
                    <li><a href="{{ route('moderation.decision', $decision) }}" class="font-medium underline">{{ $decision->decision->label() }}</a> · {{ $decision->decided_at->translatedFormat('d F Y') }}</li>
                @endforeach
            </ul>
        </x-card>
    @endif

    <form method="POST" action="{{ route('logout') }}" class="text-center">
        @csrf
        <button type="submit" class="py-3 text-sm font-medium text-red-700 underline">Se déconnecter</button>
    </form>
</x-layout>
