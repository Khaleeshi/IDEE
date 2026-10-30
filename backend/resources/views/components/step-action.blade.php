@props(['step'])

{{-- Lien d'une étape vers la page d'IDEE qui permet de la réaliser. --}}
@php
    $link = match ($step->action) {
        'publier-projet' => [route('projects.create'), 'Publier mon projet'],
        'mes-projets' => [route('projects.mine'), 'Voir mes projets'],
        'demandes' => [route('contact-requests.index'), 'Voir mes demandes'],
        'forme-juridique' => [route('journeys.legal-form'), 'Faire le questionnaire'],
        'opportunites' => [route('opportunities.index'), 'Voir les programmes et financements'],
        default => $step->relatedJourney
            ? [route('journeys.show', $step->relatedJourney), 'Voir le parcours « '.$step->relatedJourney->title.' »']
            : null,
    };
@endphp

@if ($link)
    <x-button variant="secondary" :href="$link[0]" class="mt-3 w-full">{{ $link[1] }}</x-button>
@endif
