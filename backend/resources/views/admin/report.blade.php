<x-layout title="Signalement">
    <x-admin-nav />
    <a href="{{ route('admin.reports') }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← Signalements</a>

    <x-card>
        <p class="font-semibold {{ $report->priority >= 3 ? 'text-red-700' : '' }}">{{ $report->reason->label() }}</p>
        <p class="text-sm text-stone-500">Signalé le {{ $report->created_at->translatedFormat('d F Y à H:i') }} par {{ $report->reporter->name }}</p>
        @if ($report->details)<p class="mt-2 whitespace-pre-line text-sm">« {{ $report->details }} »</p>@endif
    </x-card>

    <x-card>
        <h2 class="text-sm font-semibold text-stone-500">Contenu signalé</h2>
        @php($target = $report->reportable)
        @if ($target instanceof \App\Models\Project)
            <p class="mt-1 font-semibold">{{ $target->title }} @if ($target->trashed())<span class="text-xs text-red-700">(retiré)</span>@endif</p>
            <p class="mt-1 whitespace-pre-line text-sm">{{ $target->summary }}</p>
        @elseif ($target instanceof \App\Models\Message)
            <p class="mt-1 whitespace-pre-line text-sm">{{ $target->body ?? ($target->attachment_name ? 'Pièce jointe : '.$target->attachment_name : '') }}</p>
        @elseif ($target instanceof \App\Models\Organisation)
            <p class="mt-1 font-semibold">{{ $target->name }}</p>
            <p class="mt-1 whitespace-pre-line text-sm">{{ $report->removed_content ?? $target->description }}</p>
        @elseif ($target instanceof \App\Models\User)
            <p class="mt-1 font-semibold">{{ $target->name }}</p>
            <p class="mt-1 whitespace-pre-line text-sm">{{ $target->bio ?? $report->removed_content }}</p>
        @else
            <p class="mt-1 text-sm text-stone-500">Ce contenu n'existe plus.</p>
        @endif
        <p class="mt-2 text-sm text-stone-500">Auteur : {{ $report->targetUser?->name ?? '—' }} @if ($report->targetUser?->isRestricted()) · <span class="text-red-700">compte restreint</span>@endif</p>
        @if ($history->isNotEmpty())
            <p class="mt-1 text-xs text-stone-500">Décisions précédentes : {{ $history->map(fn ($item) => $item->decision->label())->join(', ') }}</p>
        @endif
    </x-card>

    @if ($report->status->value === 'ouvert')
        <x-card>
            <h2 class="font-semibold">Décision</h2>
            <form method="POST" action="{{ route('admin.reports.decide', $report) }}" class="mt-2">
                @csrf
                <fieldset class="space-y-1 text-sm">
                    @foreach ($decisions as $decision)
                        <label class="flex min-h-10 items-center gap-2"><input type="radio" name="decision" value="{{ $decision->value }}" required class="h-5 w-5 accent-brand-700"> {{ $decision->label() }}</label>
                    @endforeach
                </fieldset>
                <x-field name="note" type="textarea" rows="2" label="Explication transmise à l'auteur (facultatif)" class="mt-2" />
                <x-button class="w-full">Enregistrer la décision</x-button>
            </form>
        </x-card>
    @elseif ($report->status->value === 'en_recours')
        <x-card>
            <p class="text-sm"><span class="text-stone-500">Décision contestée :</span> {{ $report->decision->label() }} (par {{ $report->moderator?->name }})</p>
            <p class="mt-2 whitespace-pre-line text-sm">« {{ $report->appeal_text }} »</p>
            @if ($report->moderator_id === auth()->id())
                <p class="mt-3 text-sm font-medium text-amber-800">Un autre membre de l'équipe doit examiner ce recours.</p>
            @else
                <div class="mt-3 flex gap-2">
                    <form method="POST" action="{{ route('admin.reports.appeal', $report) }}">@csrf<input type="hidden" name="upheld" value="1"><x-button variant="secondary" class="text-sm">Maintenir</x-button></form>
                    <form method="POST" action="{{ route('admin.reports.appeal', $report) }}">@csrf<input type="hidden" name="upheld" value="0"><x-button class="text-sm">Annuler la décision</x-button></form>
                </div>
            @endif
        </x-card>
    @else
        <x-card class="text-sm">
            <p>{{ $report->decision?->label() ?? 'Sans décision' }} · {{ $report->status->label() }}</p>
            @if ($report->decision_note)<p class="mt-1 text-stone-600">{{ $report->decision_note }}</p>@endif
        </x-card>
    @endif
</x-layout>
