<x-layout title="Sessions de pitch" description="Sessions de pitch en ligne et en présentiel pour présenter son projet à des partenaires et des investisseurs.">
    <div class="flex items-start justify-between gap-2">
        <h1 class="text-2xl font-bold">Sessions de pitch</h1>
        @if ($canOrganise)
            <a href="{{ route('events.create') }}" class="py-2 text-sm font-medium text-brand-700 underline">+ Organiser</a>
        @endif
    </div>
    <p class="mt-1 text-stone-600">Présentez votre projet à un jury de partenaires et d'investisseurs, en ligne ou en présentiel, ou venez écouter les autres.</p>

    <div class="mt-4">
        @forelse ($events as $event)
            <a href="{{ route('events.show', $event) }}" class="mb-3 block rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                <span class="text-xs font-medium text-brand-700">{{ $event->starts_at->translatedFormat('l d F · H\hi') }} · {{ $event->mode === \App\Enums\MeetingMode::Visio ? 'En ligne' : 'En présentiel' }}</span>
                <span class="mt-1 block font-semibold leading-snug">{{ $event->title }}</span>
                <span class="mt-1 block text-xs text-stone-500">{{ $event->organisation?->name ?? 'Équipe IDEE' }}@if ($event->pitch_slots) · {{ $event->pitch_slots }} projets présenteront @endif</span>
            </a>
        @empty
            <x-card class="text-center text-sm text-stone-600">Aucune session prévue pour le moment.</x-card>
        @endforelse
        {{ $events->links() }}
    </div>
</x-layout>
