<x-layout title="Vérifications">
    <h1 class="mb-3 text-2xl font-bold">Vérifications d'identité</h1>
    <x-admin-nav />
    <p class="mb-3 text-sm text-stone-600">Comparez le nom de la pièce avec celui du profil. La pièce est effacée dès votre décision.</p>

    @forelse ($pending as $verification)
        <x-card>
            <p class="font-semibold">{{ $verification->user->name }}</p>
            <p class="text-sm text-stone-500">{{ \App\Support\PhoneNumber::format($verification->user->phone) }} · envoyée {{ $verification->created_at->diffForHumans() }}</p>
            <a href="{{ route('admin.verifications.document', $verification) }}" target="_blank" rel="noopener" class="mt-2 inline-block font-medium text-brand-700 underline">Voir la pièce</a>
            <form method="POST" action="{{ route('admin.verifications.decide', $verification) }}" class="mt-3">
                @csrf
                <input type="hidden" name="approved" value="1">
                <x-button class="w-full">Approuver</x-button>
            </form>
            <form method="POST" action="{{ route('admin.verifications.decide', $verification) }}" class="mt-2">
                @csrf
                <input type="hidden" name="approved" value="0">
                <label for="motif-{{ $verification->id }}" class="block text-sm text-stone-600">Motif du refus</label>
                <input id="motif-{{ $verification->id }}" name="reason" maxlength="500" placeholder="Ex. : photo illisible, merci d'en renvoyer une plus nette"
                       class="mt-1 block w-full rounded-xl border border-stone-300 px-3 py-2 text-base">
                <x-button variant="danger" class="mt-2 w-full">Refuser</x-button>
            </form>
        </x-card>
    @empty
        <p class="text-sm text-stone-500">Aucune vérification en attente.</p>
    @endforelse
</x-layout>
