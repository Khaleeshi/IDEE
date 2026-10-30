<x-layout title="Organisations à vérifier">
    <h1 class="mb-3 text-2xl font-bold">Organisations à vérifier</h1>
    <x-admin-nav />
    <p class="mb-3 text-sm text-stone-600">Comparez le nom, le RCCM et le NINEA avec le justificatif (registre du commerce ou attestation NINEA). Le justificatif est effacé dès votre décision.</p>

    @forelse ($pending as $organisation)
        <x-card>
            <a href="{{ route('organisations.show', $organisation) }}" class="font-semibold underline">{{ $organisation->name }}</a>
            <p class="text-sm text-stone-500">{{ $organisation->kind->label() }} · demandée {{ $organisation->verification_requested_at?->diffForHumans() }}</p>
            <p class="mt-2 text-sm">RCCM : <span class="font-mono">{{ $organisation->rccm }}</span> · NINEA : <span class="font-mono">{{ $organisation->ninea }}</span></p>
            <a href="{{ route('admin.organisations.document', $organisation) }}" target="_blank" rel="noopener" class="mt-2 inline-block font-medium text-brand-700 underline">Voir le justificatif</a>
            <form method="POST" action="{{ route('admin.organisations.decide', $organisation) }}" class="mt-3">
                @csrf
                <input type="hidden" name="approved" value="1">
                <x-button class="w-full">Vérifier l'organisation</x-button>
            </form>
            <form method="POST" action="{{ route('admin.organisations.decide', $organisation) }}" class="mt-2">
                @csrf
                <input type="hidden" name="approved" value="0">
                <label for="note-{{ $organisation->id }}" class="block text-sm text-stone-600">Motif du refus</label>
                <input id="note-{{ $organisation->id }}" name="note" maxlength="500" placeholder="Ex. : le nom du registre ne correspond pas à la page"
                       class="mt-1 block w-full rounded-xl border border-stone-300 px-3 py-2 text-base">
                <x-button variant="danger" class="mt-2 w-full">Refuser</x-button>
            </form>
        </x-card>
    @empty
        <p class="text-sm text-stone-500">Aucune organisation en attente.</p>
    @endforelse
</x-layout>
