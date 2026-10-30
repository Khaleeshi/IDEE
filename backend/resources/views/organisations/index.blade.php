<x-layout title="Organisations" description="Incubateurs, institutions, financeurs, entreprises et associations présents sur IDEE.">
    <div class="flex items-start justify-between gap-2">
        <h1 class="text-2xl font-bold">Organisations</h1>
        @auth
            <a href="{{ route('organisations.create') }}" class="py-2 text-sm font-medium text-brand-700 underline">+ Créer une page</a>
        @endauth
    </div>
    <p class="mt-1 text-stone-600">Incubateurs, institutions, financeurs, entreprises et associations. Le badge « Vérifiée » signale un RCCM et un NINEA contrôlés par l'équipe IDEE.</p>

    <nav aria-label="Types d'organisations" class="mt-4 flex gap-2 overflow-x-auto pb-1 text-sm">
        <a href="{{ route('organisations.index') }}" class="shrink-0 rounded-full border px-3 py-1.5 {{ $kind ? 'border-stone-300 bg-white' : 'border-brand-700 bg-brand-700 text-white' }}">Toutes</a>
        @foreach ($kinds as $item)
            <a href="{{ route('organisations.index', ['type' => $item->value]) }}" class="shrink-0 rounded-full border px-3 py-1.5 {{ $kind === $item ? 'border-brand-700 bg-brand-700 text-white' : 'border-stone-300 bg-white' }}">{{ $item->label() }}</a>
        @endforeach
    </nav>

    <div class="mt-4">
        @forelse ($organisations as $organisation)
            <a href="{{ route('organisations.show', $organisation) }}" class="mb-3 block rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                <span class="flex items-center justify-between gap-2">
                    <span class="font-semibold">{{ $organisation->name }}</span>
                    <x-organisation-badge :organisation="$organisation" />
                </span>
                <span class="mt-1 block text-xs text-stone-500">{{ $organisation->kind->label() }}@if ($organisation->region) · {{ $organisation->region }}@endif</span>
                <span class="mt-1 line-clamp-2 block text-sm text-stone-600">{{ $organisation->description }}</span>
            </a>
        @empty
            <x-card class="text-center text-sm text-stone-600">Aucune organisation pour le moment.</x-card>
        @endforelse
        {{ $organisations->links() }}
    </div>
</x-layout>
