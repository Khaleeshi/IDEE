<x-layout :title="$opportunity->title" :description="\Illuminate\Support\Str::limit($opportunity->summary, 180)">
    <a href="{{ route('opportunities.index') }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← Programmes et financements</a>

    <p class="text-sm font-medium text-brand-700">{{ $opportunity->kind->label() }} ·
        @if ($opportunity->organisationPage)
            <a href="{{ route('organisations.show', $opportunity->organisationPage) }}" class="underline">{{ $opportunity->organisation }}</a>
        @else
            {{ $opportunity->organisation }}
        @endif
    </p>
    <h1 class="mt-1 text-2xl font-bold leading-tight">{{ $opportunity->title }}</h1>
    <p class="mt-3 text-stone-700">{{ $opportunity->summary }}</p>

    <x-card class="mt-4 text-sm">
        <dl class="space-y-2">
            @if ($opportunity->criteria)
                <div><dt class="font-semibold">Critères</dt><dd class="mt-1 whitespace-pre-line text-stone-700">{{ $opportunity->criteria }}</dd></div>
            @endif
            @if ($opportunity->amount)
                <div><dt class="font-semibold">Montant</dt><dd class="mt-1 text-stone-700">{{ $opportunity->amount }}</dd></div>
            @endif
            <div>
                <dt class="font-semibold">Calendrier</dt>
                <dd class="mt-1 text-stone-700">
                    @if ($opportunity->deadline)
                        Date limite : {{ $opportunity->deadline->translatedFormat('d F Y') }} @if ($opportunity->isClosed())<span class="font-medium text-red-700">(clôturé)</span>@endif
                    @else
                        {{ $opportunity->deadline_note ?? 'À confirmer auprès de l\'organisme' }}
                    @endif
                </dd>
            </div>
        </dl>
        @if ($opportunity->url)
            <x-button :href="$opportunity->url" variant="secondary" class="mt-4 w-full" target="_blank" rel="noopener">Voir la source</x-button>
        @endif
        <p class="mt-3 text-xs text-stone-500">Source : {{ $opportunity->source_label }}@if ($opportunity->checked_on), consultée le {{ $opportunity->checked_on->translatedFormat('d F Y') }}@endif. Vérifiez les conditions auprès de l'organisme avant de candidater.</p>
    </x-card>

    @if ($eligibilities->isNotEmpty())
        {{-- OP-02 : compatibilité avec les projets du membre. --}}
        <x-card class="text-sm">
            <h2 class="font-semibold">Pour mes projets</h2>
            @foreach ($eligibilities as $item)
                <div class="mt-2 border-t border-stone-100 pt-2">
                    <p class="font-medium">{{ $item['project']->title }} :
                        <span class="{{ $item['eligible'] ? 'text-brand-800' : 'text-stone-500' }}">{{ $item['eligible'] ? 'compatible' : 'non compatible' }}</span>
                    </p>
                    <p class="text-xs text-stone-500">{{ implode(' · ', $item['eligible'] ? $item['reasons'] : $item['blockers']) }}</p>
                </div>
            @endforeach
        </x-card>
    @endif

    @if ($programmeApplication)
        <x-card class="text-sm">
            <p>Votre projet « {{ $programmeApplication->project->title }} » a candidaté sur IDEE : <span class="font-semibold">{{ mb_strtolower($programmeApplication->status->label()) }}</span>.</p>
            <a href="{{ route('applications.show', $programmeApplication) }}" class="mt-1 inline-block font-medium text-brand-700 underline">Voir ma candidature</a>
        </x-card>
    @elseif ($opportunity->takesApplications() && $myProjects->isNotEmpty())
        {{-- OR-02 : candidature structurée depuis le dossier IDEE. --}}
        <x-card id="dossier">
            <h2 class="font-semibold">Candidater avec mon dossier IDEE</h2>
            <form method="POST" action="{{ route('programmes.apply', $opportunity) }}" class="mt-3">
                @csrf
                <x-select name="project_id" label="Projet" :options="$myProjects->pluck('title', 'id')->all()" :value="$myProjects->first()->id" />
                @foreach ($questions as $key => $question)
                    <x-field :name="'answers['.$key.']'" type="textarea" rows="3" :label="$question" maxlength="3000" />
                    @error('answers.'.$key)<p class="-mt-3 mb-3 text-sm text-red-700">{{ $message }}</p>@enderror
                @endforeach
                <label class="mb-4 flex items-start gap-3 text-sm"><input type="checkbox" name="share_business_plan" value="1" checked class="mt-0.5 h-5 w-5 accent-brand-700"> <span>Joindre mon dernier business plan, s'il existe. Il n'est partagé qu'avec {{ $opportunity->organisation }}.</span></label>
                <x-button class="w-full">Envoyer ma candidature</x-button>
            </form>
        </x-card>
    @endif

    @auth
        <x-card id="candidature">
            <h2 class="font-semibold">Suivre ma candidature</h2>
            <p class="mt-1 text-sm text-stone-600">Notez où en est votre dossier et réglez un rappel pour relancer l'organisme.</p>
            <form method="POST" action="{{ route('opportunities.track', $opportunity) }}" class="mt-3">
                @csrf
                @method('PUT')
                <x-select name="status" label="Statut" :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])" :value="$application?->status->value" />
                <x-field name="remind_on" type="date" label="Me le rappeler le (facultatif)" :value="$application?->remind_on?->toDateString()" />
                <x-field name="note" type="textarea" rows="2" label="Note (facultatif)" :value="$application?->note" placeholder="Ex. : dossier déposé à l'antenne de Thiès le 4 novembre" />
                <x-button class="w-full">Enregistrer</x-button>
            </form>
            @if ($application)
                <form method="POST" action="{{ route('opportunities.forget', $opportunity) }}" class="mt-2 text-center">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="py-2 text-sm text-stone-600 underline">Retirer de mon suivi</button>
                </form>
            @endif
        </x-card>
    @endauth
</x-layout>
