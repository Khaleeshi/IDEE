<x-layout title="Quelle forme juridique choisir ?">
    <a href="{{ route('journeys.index') }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← Parcours</a>

    <h1 class="text-2xl font-bold leading-tight">Quelle forme juridique choisir ?</h1>
    <p class="mt-1 text-stone-600">Trois questions pour vous orienter. IDEE ne donne pas de conseil juridique : confirmez votre choix avec l'APIX ou un notaire.</p>

    @if ($advice)
        <x-card class="mt-4 border-brand-100 bg-brand-50" role="status">
            <p class="text-sm text-brand-800">Piste conseillée</p>
            <h2 class="text-xl font-bold text-brand-900">{{ $advice['main']['name'] }}</h2>
            <p class="mt-1 text-sm text-brand-900">{{ $advice['main']['summary'] }}</p>
            @if ($advice['main']['journey'])
                <x-button :href="route('journeys.show', $advice['main']['journey'])" class="mt-3 w-full">Suivre le parcours « {{ $advice['main']['journey']->title }} »</x-button>
            @endif
            <p class="mt-4 text-sm text-brand-900">
                À comparer avec : <strong>{{ $advice['alternative']['name'] }}</strong>. {{ $advice['alternative']['summary'] }}
            </p>
        </x-card>
    @endif

    <form method="GET" action="{{ route('journeys.legal-form') }}" class="mt-4">
        @foreach ($questions as $name => $question)
            <fieldset class="mb-5">
                <legend class="font-semibold">{{ $loop->iteration }}. {{ $question['label'] }}</legend>
                @isset($question['hint'])
                    <p class="mt-1 text-sm text-stone-500">{{ $question['hint'] }}</p>
                @endisset
                <div class="mt-2 space-y-2">
                    @foreach ($question['options'] as $value => $label)
                        <label class="flex min-h-12 items-center gap-3 rounded-xl border border-stone-200 bg-white px-3">
                            <input type="radio" name="{{ $name }}" value="{{ $value }}" required @checked(($answers[$name] ?? old($name)) === $value) class="h-5 w-5 accent-brand-700">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endforeach

        <x-button class="w-full">{{ $advice ? 'Mettre à jour' : 'Voir la forme conseillée' }}</x-button>
    </form>
</x-layout>
