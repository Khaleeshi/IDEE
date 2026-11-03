{{-- Champs communs à l'ajout et à la modification d'un profil professionnel (PM-02, PM-03). --}}
@php($profile ??= null)
<x-field name="title" label="Intitulé affiché (facultatif)" :value="$profile?->title" maxlength="120"
         hint="Laissez vide pour afficher le nom du métier. Ex. : Coach en levée de fonds pour l'agroalimentaire" />
<x-field name="description" type="textarea" rows="3" label="Ce que vous apportez" :value="$profile?->description"
         placeholder="Votre expérience, les projets que vous accompagnez, votre façon de travailler." />
<x-field name="experience_years" type="number" min="0" max="60" inputmode="numeric" label="Années d'expérience (facultatif)" :value="$profile?->experience_years" />

<fieldset class="mb-4">
    <legend class="text-sm font-medium text-stone-800">Comment intervenez-vous ?</legend>
    <p class="text-xs text-stone-500">Cochez au moins une case ; vous pouvez en cocher plusieurs.</p>
    <div class="mt-2 space-y-2">
        @foreach (['offers_paid' => 'Prestations payantes', 'offers_volunteer' => 'Bénévolat', 'offers_mentoring' => 'Mentorat'] as $field => $label)
            <label class="flex items-center gap-3 rounded-xl border border-stone-200 p-3">
                <input type="hidden" name="{{ $field }}" value="0">
                <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $profile?->{$field})) class="h-5 w-5 accent-brand-700">
                <span>{{ $label }}</span>
            </label>
        @endforeach
    </div>
    @error('offers')
        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
    @enderror
    <p class="mt-2 text-xs text-stone-500">Un profil payant ou de mentorat n'est visible des autres membres qu'après la vérification de votre identité.</p>
</fieldset>

<x-field name="rate" label="Tarif indicatif (si payant)" :value="$profile?->rate" maxlength="120"
         placeholder="Ex. : 25 000 FCFA la séance, premier échange offert"
         hint="Réglé directement entre vous et la personne accompagnée : IDEE ne prend aucune commission." />
