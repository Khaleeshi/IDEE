<x-layout title="Accompagnement">
    <a href="{{ route('accompaniments.index') }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← Accompagnements</a>
    @php($isCoach = $accompaniment->coach_id === auth()->id())
    @php($other = $isCoach ? $accompaniment->requester : $accompaniment->coach)
    <div class="flex items-start justify-between gap-2">
        <h1 class="text-2xl font-bold">{{ $accompaniment->kind->label() }} {{ $isCoach ? 'de' : 'avec' }} <a href="{{ route('members.show', $other) }}" class="underline">{{ $other->name }}</a></h1>
        <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium {{ \App\Http\Controllers\Web\AccompanimentController::statusClasses($accompaniment->status) }}">{{ $accompaniment->status->label() }}</span>
    </div>
    <p class="mt-1 text-sm text-stone-500">
        @if ($accompaniment->memberProfile){{ $accompaniment->memberProfile->label() }} · @endif
        @if ($accompaniment->project)projet <a href="{{ route('projects.show', $accompaniment->project) }}" class="underline">{{ $accompaniment->project->title }}</a> · @endif
        demandé le {{ $accompaniment->created_at->translatedFormat('d F Y') }}
    </p>

    @if (session('status'))
        <p class="mt-3 rounded-xl bg-brand-50 p-3 text-sm text-brand-800" role="status">{{ session('status') }}</p>
    @endif

    <x-card class="mt-4 text-sm">
        <h2 class="font-semibold">Objectifs</h2>
        <p class="mt-1 whitespace-pre-line">{{ $accompaniment->objectives }}</p>
        @if ($accompaniment->decline_reason)
            <p class="mt-2 text-stone-600"><span class="font-medium">Réponse :</span> {{ $accompaniment->decline_reason }}</p>
        @endif
    </x-card>

    @if ($accompaniment->status->value === 'demandee')
        @if ($isCoach)
            <x-card>
                <form method="POST" action="{{ route('accompaniments.respond', $accompaniment) }}">
                    @csrf
                    <input type="hidden" name="accepted" value="1">
                    <x-button class="w-full">J'accepte de l'accompagner</x-button>
                </form>
                <form method="POST" action="{{ route('accompaniments.respond', $accompaniment) }}" class="mt-3">
                    @csrf
                    <input type="hidden" name="accepted" value="0">
                    <x-field name="reason" label="Ou déclinez, avec un mot d'explication" maxlength="1000" placeholder="Ex. : je suis complet jusqu'en janvier" />
                    <x-button variant="secondary" class="w-full">Décliner</x-button>
                </form>
            </x-card>
        @else
            <form method="POST" action="{{ route('accompaniments.cancel', $accompaniment) }}" class="text-center">
                @csrf
                <button type="submit" class="py-2 text-sm font-medium text-red-700 underline">Retirer ma demande</button>
            </form>
        @endif
    @endif

    @if (in_array($accompaniment->status->value, ['en_cours', 'terminee'], true))
        <h2 class="mb-2 mt-5 font-semibold">Points de suivi</h2>
        @forelse ($accompaniment->sessions as $session)
            <x-card class="text-sm">
                <p class="text-xs text-stone-500">{{ $session->held_on->translatedFormat('d F Y') }} · noté par {{ $session->author?->name ?? 'membre supprimé' }}</p>
                <p class="mt-1 whitespace-pre-line">{{ $session->summary }}</p>
                @if ($session->next_steps)
                    <p class="mt-2"><span class="font-medium">Prochaines étapes :</span> {{ $session->next_steps }}</p>
                @endif
            </x-card>
        @empty
            <p class="mb-4 text-sm text-stone-600">Aucun point de suivi pour l'instant.</p>
        @endforelse
    @endif

    @if ($accompaniment->status->value === 'en_cours')
        <x-card>
            <h2 class="mb-2 font-semibold">Noter une séance</h2>
            <form method="POST" action="{{ route('accompaniments.sessions.store', $accompaniment) }}">
                @csrf
                <x-field name="held_on" type="date" label="Date de la séance" :value="today()->toDateString()" max="{{ today()->toDateString() }}" />
                <x-field name="summary" type="textarea" rows="3" label="Ce qui s'est dit" maxlength="3000" />
                <x-field name="next_steps" type="textarea" rows="2" label="Prochaines étapes (facultatif)" maxlength="2000" />
                <x-button class="w-full">Enregistrer</x-button>
            </form>
        </x-card>
        <form method="POST" action="{{ route('accompaniments.complete', $accompaniment) }}">
            @csrf
            <x-button variant="secondary" class="w-full">Mettre fin à l'accompagnement</x-button>
        </form>
    @endif

    @if ($accompaniment->status->value === 'terminee')
        <h2 class="mb-2 mt-5 font-semibold">Avis</h2>
        @if ($accompaniment->review)
            @include('accompaniments.partials.review', ['review' => $accompaniment->review])
        @elseif (! $isCoach)
            <x-card>
                <form method="POST" action="{{ route('accompaniments.review', $accompaniment) }}">
                    @csrf
                    <x-select name="rating" label="Votre note" :options="[5 => '5 · Excellent', 4 => '4 · Très bien', 3 => '3 · Bien', 2 => '2 · Moyen', 1 => '1 · Décevant']" :value="old('rating', 5)" />
                    <x-field name="comment" type="textarea" rows="3" label="Votre avis (facultatif)" maxlength="2000" placeholder="Ce qui vous a aidé, ce qui aurait pu être mieux." />
                    <x-button class="w-full">Publier mon avis</x-button>
                </form>
            </x-card>
        @else
            <p class="text-sm text-stone-600">{{ $accompaniment->requester->name }} n'a pas encore laissé d'avis.</p>
        @endif
    @endif
</x-layout>
