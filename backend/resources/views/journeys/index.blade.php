<x-layout title="Parcours guidés">
    <h1 class="text-2xl font-bold">Parcours guidés</h1>
    <p class="mt-1 text-stone-600">Où en êtes-vous&nbsp;? Choisissez votre situation. IDEE vous guide étape par étape, avec les pièces, le coût et l'organisme à chaque fois.</p>

    @if ($reminders->isNotEmpty())
        <x-card class="mt-4 border-amber-200 bg-amber-50">
            <h2 class="font-semibold text-amber-900">Rappels</h2>
            <ul class="mt-2 space-y-2 text-sm">
                @foreach ($reminders as $reminder)
                    <li>
                        <a href="{{ route('journeys.show', $reminder->step->journey_id) }}#etape-{{ $reminder->journey_step_id }}" class="font-medium text-amber-900 underline">{{ $reminder->step->title }}</a>
                        <span class="text-amber-800">· {{ $reminder->step->journey->title }} · prévu le {{ $reminder->remind_on->translatedFormat('d F') }}</span>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif

    @if ($followed->isNotEmpty())
        <h2 class="mb-2 mt-6 text-sm font-semibold uppercase tracking-wide text-stone-500">Mes démarches</h2>
        @foreach ($followed as $journey)
            <a href="{{ route('journeys.show', $journey) }}" class="mb-3 block rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                <span class="flex items-center justify-between gap-2">
                    <span class="font-semibold">{{ $journey->title }}</span>
                    <span class="shrink-0 text-sm text-stone-500">{{ $journey->done_steps_count }}/{{ $journey->steps_count }}</span>
                </span>
                <x-progress :done="$journey->done_steps_count" :total="$journey->steps_count" class="mt-2" />
            </a>
        @endforeach
    @endif

    <h2 class="mb-2 mt-6 text-sm font-semibold uppercase tracking-wide text-stone-500">Ma situation</h2>
    @foreach ($journeys as $journey)
        <a href="{{ route('journeys.show', $journey) }}" class="mb-3 flex items-center justify-between gap-3 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
            <span>
                <span class="block font-semibold">{{ $journey->situation }}</span>
                <span class="block text-sm text-stone-500">{{ $journey->situation_hint }}</span>
                <span class="mt-1 block text-sm font-medium text-brand-700">{{ $journey->title }} · {{ $journey->steps_count }} étapes</span>
            </span>
            <span aria-hidden="true" class="text-2xl text-stone-400">›</span>
        </a>
    @endforeach

    <x-card class="mt-2">
        <h2 class="font-semibold">Quelle forme juridique choisir ?</h2>
        <p class="mt-1 text-sm text-stone-600">Trois questions pour vous orienter entre l'entreprenant, l'entreprise individuelle, la SARL et la SAS.</p>
        <x-button variant="secondary" :href="route('journeys.legal-form')" class="mt-3 w-full">Faire le questionnaire</x-button>
    </x-card>

    <x-journey-notice />
</x-layout>
