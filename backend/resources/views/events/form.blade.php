<x-layout title="Organiser une session de pitch">
    <a href="{{ route('events.index') }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← Sessions de pitch</a>
    <h1 class="text-2xl font-bold">Organiser une session de pitch</h1>

    <form method="POST" action="{{ route('events.store') }}" class="mt-4">
        @csrf
        @if ($organisations->isNotEmpty())
            <x-select name="organisation_id" label="Organisée par" :options="$organisations->pluck('name', 'id')->all()"
                      :value="$organisations->first()->id" :placeholder="auth()->user()->isAdmin() ? 'L\'équipe IDEE' : null" />
        @endif
        <fieldset class="mb-4">
            <legend class="block text-sm font-medium text-stone-800">Format</legend>
            <div class="mt-1 space-y-2">
                <label class="flex min-h-12 items-center gap-2 rounded-xl border border-stone-200 px-3"><input type="radio" name="format" value="pitch" @checked(old('format', 'pitch') === 'pitch') class="h-5 w-5 accent-brand-700"> <span>Session de pitch : des projets présentent devant un jury</span></label>
                <label class="flex min-h-12 items-center gap-2 rounded-xl border border-stone-200 px-3"><input type="radio" name="format" value="rencontres" @checked(old('format') === 'rencontres') class="h-5 w-5 accent-brand-700"> <span>Rencontres ciblées : porteurs et partenaires en tête-à-tête de {{ config('idee.speed_meeting.minutes') }} minutes</span></label>
            </div>
        </fieldset>
        <x-field name="title" label="Titre" placeholder="Ex. : Pitch agro-transformation de Thiès" maxlength="200" required />
        <x-field name="description" type="textarea" rows="4" label="Présentation" maxlength="3000" required
                 placeholder="Public visé, déroulé, composition du jury, temps de présentation…" />
        <x-field name="starts_at" type="datetime-local" label="Jour et heure (heure de Dakar)" required />
        <fieldset class="mb-4">
            <legend class="block text-sm font-medium text-stone-800">Forme</legend>
            <div class="mt-1 grid grid-cols-2 gap-2">
                @foreach (\App\Enums\MeetingMode::cases() as $mode)
                    <label class="flex min-h-12 items-center gap-2 rounded-xl border border-stone-200 px-3">
                        <input type="radio" name="mode" value="{{ $mode->value }}" @checked(old('mode', 'visio') === $mode->value) class="h-5 w-5 accent-brand-700">
                        <span>{{ $mode === \App\Enums\MeetingMode::Visio ? 'En ligne' : 'En présentiel' }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>
        <x-field name="place" label="Lieu, ou lien de la visio" placeholder="Ex. : CTIC Dakar, ou https://…" maxlength="300" hint="Le lien de visio n'est montré qu'aux inscrits." />
        <x-field name="pitch_slots" type="number" inputmode="numeric" min="0" max="50" label="Nombre de projets qui présenteront" value="5" />
        <x-field name="apply_until" type="date" label="Fin des candidatures (facultatif)" />
        <x-button class="w-full">Publier la session</x-button>
    </form>
</x-layout>
