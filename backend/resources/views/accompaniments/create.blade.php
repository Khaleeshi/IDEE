<x-layout title="Demander un accompagnement">
    <a href="{{ route('members.show', $profile->user) }}" class="mb-2 inline-block text-sm text-stone-500">← {{ $profile->user->name }}</a>
    <h1 class="text-2xl font-bold">Demander un accompagnement</h1>
    <p class="mb-4 text-sm text-stone-600">À {{ $profile->user->name }}, {{ $profile->label() }}. Gratuit : une prestation payante se demande par un devis.</p>

    <x-card>
        <form method="POST" action="{{ route('accompaniments.store', $profile) }}">
            @csrf
            <fieldset class="mb-4">
                <legend class="text-sm font-medium text-stone-800">Forme d'accompagnement</legend>
                <div class="mt-2 space-y-2">
                    @if ($profile->offers_mentoring)
                        <label class="flex items-start gap-3 rounded-xl border border-stone-200 p-3">
                            <input type="radio" name="kind" value="mentorat" @checked(old('kind', 'mentorat') === 'mentorat') class="mt-1 h-5 w-5 accent-brand-700">
                            <span><span class="font-medium">Mentorat</span><span class="block text-xs text-stone-500">Un suivi sur plusieurs semaines, avec des points réguliers.</span></span>
                        </label>
                    @endif
                    @if ($profile->offers_volunteer)
                        <label class="flex items-start gap-3 rounded-xl border border-stone-200 p-3">
                            <input type="radio" name="kind" value="benevolat" @checked(old('kind', $profile->offers_mentoring ? null : 'benevolat') === 'benevolat') class="mt-1 h-5 w-5 accent-brand-700">
                            <span><span class="font-medium">Aide bénévole</span><span class="block text-xs text-stone-500">Un coup de main ponctuel sur une question précise.</span></span>
                        </label>
                    @endif
                </div>
                @error('kind')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </fieldset>

            @if ($projects->isNotEmpty())
                <x-select name="project_id" label="À propos de mon projet (facultatif)" :options="$projects" placeholder="Aucun projet en particulier" />
            @endif
            <x-field name="objectives" type="textarea" rows="5" label="Votre situation et ce que vous attendez" :min-length="config('idee.accompaniments.objectives_min_length')" maxlength="3000"
                     placeholder="Ex. : je lance une marque de jus locaux à Thiès, j'ai besoin d'aide pour fixer mes prix et trouver mes premiers revendeurs." />
            <x-button class="w-full">Envoyer ma demande</x-button>
        </form>
    </x-card>
</x-layout>
