<x-layout title="Gérer · {{ $organisation->name }}">
    <a href="{{ route('organisations.show', $organisation) }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← {{ $organisation->name }}</a>
    <h1 class="text-2xl font-bold">Gérer la page</h1>

    {{-- CF-03 : vérification par le RCCM et le NINEA. --}}
    <x-card class="mt-4" id="verification">
        <div class="flex items-center justify-between gap-2">
            <h2 class="font-semibold">Vérification</h2>
            <span class="text-xs font-medium {{ $organisation->isVerified() ? 'text-brand-800' : 'text-stone-500' }}">{{ $organisation->verification_status->label() }}</span>
        </div>
        @if ($organisation->isVerified())
            <p class="mt-1 text-sm text-stone-600">RCCM {{ $organisation->rccm }} · NINEA {{ $organisation->ninea }}. Le badge « Vérifiée » s'affiche sur la page{{ $organisation->kind->publishesProgrammes() ? ' et vous pouvez publier vos programmes' : '' }}.</p>
        @elseif ($organisation->verification_status->value === 'en_attente')
            <p class="mt-1 text-sm text-stone-600">L'équipe IDEE examine votre justificatif. Il sera effacé après examen.</p>
        @else
            @if ($organisation->verification_note)
                <p class="mt-1 rounded-xl bg-stone-50 p-2 text-sm"><span class="font-medium">Motif :</span> {{ $organisation->verification_note }}</p>
            @endif
            <p class="mt-1 text-sm text-stone-600">Indiquez le RCCM et le NINEA, et joignez l'extrait du registre du commerce ou l'attestation NINEA. Le justificatif est effacé dès la décision de l'équipe.</p>
            <form method="POST" action="{{ route('organisations.verification', $organisation) }}" enctype="multipart/form-data" class="mt-3">
                @csrf
                <x-field name="rccm" label="Numéro RCCM" :value="$organisation->rccm" placeholder="SN-DKR-2024-B-12345" maxlength="50" />
                <x-field name="ninea" label="NINEA" :value="$organisation->ninea" placeholder="009876543 2V2" maxlength="30" />
                <label for="justificatif" class="block text-sm font-medium text-stone-800">Justificatif (PDF ou photo)</label>
                <input id="justificatif" name="document" type="file" required accept=".pdf,image/jpeg,image/png" class="mt-1 block w-full text-sm">
                @error('document')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                <x-button class="mt-3 w-full">Demander la vérification</x-button>
            </form>
        @endif
    </x-card>

    {{-- OR-01 : programmes publiés par l'organisation. --}}
    @if ($organisation->kind->publishesProgrammes())
        <x-card class="mt-3">
            <h2 class="font-semibold">Programmes</h2>
            @forelse ($programmes as $programme)
                <div class="mt-2 flex items-center justify-between gap-2 border-t border-stone-100 pt-2 text-sm">
                    <a href="{{ route('opportunities.show', $programme) }}" class="font-medium underline">{{ $programme->title }}</a>
                    <a href="{{ route('programmes.edit', $programme) }}" class="py-2 text-xs text-brand-700 underline">Modifier</a>
                </div>
            @empty
                <p class="mt-1 text-sm text-stone-600">{{ $organisation->canPublishProgrammes() ? 'Publiez vos appels, concours et financements : ils rejoignent le répertoire d\'IDEE.' : 'Vous pourrez publier vos programmes une fois la page vérifiée.' }}</p>
            @endforelse
            @if ($organisation->canPublishProgrammes())
                <x-button :href="route('programmes.create', $organisation)" variant="secondary" class="mt-3 w-full">Publier un programme</x-button>
            @endif
        </x-card>
    @endif

    <x-card class="mt-3">
        <h2 class="font-semibold">Membres</h2>
        @foreach ($organisation->members as $member)
            <div class="mt-2 flex items-center justify-between gap-2 border-t border-stone-100 pt-2 text-sm">
                <span><span class="font-medium">{{ $member->user->name }}</span> <span class="text-stone-500">· {{ $member->role->label() }}</span></span>
                <form method="POST" action="{{ route('organisations.members.destroy', $member) }}" data-confirm="Retirer {{ $member->user->name }} de la page ?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="py-2 text-xs text-stone-500 underline">Retirer</button>
                </form>
            </div>
        @endforeach
        <details class="mt-3" @if ($errors->hasAny(['identifier', 'role'])) open @endif>
            <summary class="cursor-pointer py-2 font-medium text-brand-700">Ajouter un membre</summary>
            <form method="POST" action="{{ route('organisations.members.store', $organisation) }}" class="mt-2">
                @csrf
                <x-field name="identifier" label="Téléphone ou e-mail de son compte IDEE" placeholder="77 123 45 67" />
                <x-select name="role" label="Rôle" :options="collect($roles)->mapWithKeys(fn ($role) => [$role->value => $role->label()])->all()" value="membre" />
                <x-button variant="secondary" class="w-full">Ajouter</x-button>
            </form>
        </details>
    </x-card>

    <x-card class="mt-3">
        <details @if ($errors->hasAny(['name', 'kind', 'description', 'region', 'website'])) open @endif>
            <summary class="cursor-pointer py-1 font-semibold">Modifier la présentation</summary>
            <form method="POST" action="{{ route('organisations.update', $organisation) }}" class="mt-3">
                @csrf
                @method('PATCH')
                @include('organisations.partials.fields')
                <x-button class="w-full">Enregistrer</x-button>
            </form>
        </details>
    </x-card>
</x-layout>
