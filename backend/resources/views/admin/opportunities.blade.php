<x-layout title="Opportunités">
    <h1 class="mb-3 text-2xl font-bold">Opportunités</h1>
    <x-admin-nav />
    <x-button :href="route('admin.opportunities.create')" class="mb-3 w-full">+ Ajouter une opportunité</x-button>

    <ul class="divide-y divide-stone-100 rounded-2xl border border-stone-200 bg-white text-sm">
        @foreach ($opportunities as $opportunity)
            <li>
                <a href="{{ route('admin.opportunities.edit', $opportunity) }}" class="flex items-center justify-between gap-2 p-3">
                    <span><span class="block font-medium">{{ $opportunity->title }}</span><span class="text-xs text-stone-500">{{ $opportunity->organisation }} · vérifiée le {{ $opportunity->checked_on?->format('d/m/Y') ?? '—' }}</span></span>
                    @if (! $opportunity->is_published)<span class="shrink-0 rounded-full bg-stone-100 px-2 py-0.5 text-xs">Masquée</span>
                    @elseif ($opportunity->isClosed())<span class="shrink-0 rounded-full bg-stone-100 px-2 py-0.5 text-xs">Clôturée</span>@endif
                </a>
            </li>
        @endforeach
    </ul>
</x-layout>
