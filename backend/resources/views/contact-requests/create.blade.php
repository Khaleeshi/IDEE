<x-layout title="Contacter">
    <a href="{{ route('projects.show', $project) }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← {{ $project->title }}</a>
    <h1 class="mb-1 text-2xl font-bold">Contacter {{ $project->owner->name }}</h1>
    <p class="mb-4 text-stone-600">
        @if ($need) À propos du besoin « {{ $need->title }} ». @endif
        Présentez-vous et dites précisément ce que vous proposez.
    </p>

    <x-card>
        <form method="POST" action="{{ route('contact-requests.store') }}">
            @csrf
            <input type="hidden" name="recipient_id" value="{{ $project->owner_id }}">
            <input type="hidden" name="project_id" value="{{ $project->id }}">
            @if ($need)<input type="hidden" name="need_id" value="{{ $need->id }}">@endif

            <x-field name="message" type="textarea" rows="6" label="Votre message" required
                     :min-length="config('idee.contact_message_min_length')"
                     placeholder="Qui êtes-vous ? Qu'est-ce qui vous intéresse dans ce projet ? Que proposez-vous ?" />

            <p class="mb-4 text-sm text-stone-600">Demandes envoyées cette semaine : <strong>{{ $used }} / {{ $quota }}</strong>.</p>
            <x-button class="w-full" :disabled="$used >= $quota">Envoyer la demande</x-button>
        </form>
    </x-card>

    <p class="text-xs text-stone-500">Rappel : un investisseur ou une institution ne vous demandera jamais d'argent pour étudier votre dossier. Signalez toute demande de ce type.</p>
</x-layout>
