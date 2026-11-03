<x-layout title="Accompagnements">
    <h1 class="text-2xl font-bold">Accompagnements</h1>
    <p class="mb-4 text-sm text-stone-600">Mentorat et aide bénévole : vos demandes, et les personnes que vous accompagnez. Une prestation payante passe par un <a href="{{ route('quotes.index') }}" class="underline">devis</a>.</p>

    @foreach (['asCoach' => 'Je les accompagne', 'asRequester' => 'On m\'accompagne'] as $list => $heading)
        <h2 class="mb-2 mt-5 font-semibold">{{ $heading }}</h2>
        @forelse ($$list as $accompaniment)
            @php($other = $list === 'asCoach' ? $accompaniment->requester : $accompaniment->coach)
            <a href="{{ route('accompaniments.show', $accompaniment) }}" class="mb-2 flex items-center justify-between gap-3 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
                <span class="min-w-0">
                    <span class="block font-medium">{{ $other->name }}</span>
                    <span class="block text-xs text-stone-500">{{ $accompaniment->kind->label() }}@if ($accompaniment->memberProfile) · {{ $accompaniment->memberProfile->label() }}@endif · {{ $accompaniment->created_at->translatedFormat('d F Y') }}</span>
                </span>
                <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium {{ \App\Http\Controllers\Web\AccompanimentController::statusClasses($accompaniment->status) }}">{{ $accompaniment->status->label() }}</span>
            </a>
        @empty
            <p class="rounded-xl border border-dashed border-stone-300 p-4 text-sm text-stone-600">
                {{ $list === 'asCoach' ? 'Personne pour l\'instant. Ajoutez le mentorat ou le bénévolat à l\'un de vos métiers pour recevoir des demandes.' : 'Aucune demande. Trouvez un coach ou un mentor dans l\'annuaire.' }}
            </p>
        @endforelse
    @endforeach

    <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
        <x-button :href="route('experts.index')" variant="secondary">Trouver un coach</x-button>
        <x-button :href="route('member-profiles.index')" variant="secondary">Mes métiers</x-button>
    </div>
</x-layout>
