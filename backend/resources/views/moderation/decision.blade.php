<x-layout title="Décision de modération">
    <x-card>
        <h1 class="text-xl font-bold">{{ $report->decision->label() }}</h1>
        <p class="mt-1 text-sm text-stone-500">Décidée le {{ $report->decided_at->translatedFormat('d F Y') }}</p>
        <p class="mt-3"><span class="text-stone-500">Motif du signalement :</span> {{ $report->reason->label() }}</p>
        @if ($report->decision_note)
            <p class="mt-1"><span class="text-stone-500">Explication de l'équipe :</span> {{ $report->decision_note }}</p>
        @endif
    </x-card>

    @if ($report->appeal_decided_at)
        <x-card class="{{ $report->appeal_upheld ? '' : 'border-brand-100 bg-brand-50' }}">
            <p class="font-semibold">{{ $report->appeal_upheld ? 'Recours examiné : la décision est maintenue.' : 'Recours accepté : la décision est annulée.' }}</p>
        </x-card>
    @elseif ($report->appealed_at)
        <x-card><p>Votre recours est en cours d'examen par un second membre de l'équipe.</p></x-card>
    @elseif ($report->canBeAppealed())
        <x-card>
            <h2 class="font-semibold">Contester cette décision</h2>
            <p class="mt-1 text-sm text-stone-600">Vous pouvez la contester une seule fois, dans les {{ config('idee.moderation.appeal_days') }} jours. Un autre membre de l'équipe examinera votre recours.</p>
            <form method="POST" action="{{ route('moderation.appeal', $report) }}" class="mt-3">
                @csrf
                <x-field name="appeal" type="textarea" rows="4" label="Votre explication" required :min-length="20" />
                <x-button class="w-full">Envoyer mon recours</x-button>
            </form>
        </x-card>
    @endif
</x-layout>
