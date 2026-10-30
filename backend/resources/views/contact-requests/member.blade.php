<x-layout title="Contacter {{ $member->name }}">
    <a href="{{ route('members.show', $member) }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← {{ $member->name }}</a>
    <h1 class="mb-1 text-2xl font-bold">Contacter {{ $member->name }}</h1>
    <p class="mb-4 text-stone-600">Choisissez le projet concerné et dites précisément ce que vous attendez{{ $member->rate ? ' ; tarif indiqué : '.$member->rate : '' }}.</p>

    <x-card>
        <form method="POST" action="{{ route('contact-requests.store') }}">
            @csrf
            <input type="hidden" name="recipient_id" value="{{ $member->id }}">
            <x-select name="project_id" label="Votre projet" :options="$projects->pluck('title', 'id')->all()" :value="$projects->first()->id" />
            <x-field name="message" type="textarea" rows="6" label="Votre message" required
                     :min-length="config('idee.contact_message_min_length')"
                     placeholder="Où en est votre projet ? De quelle aide avez-vous besoin, et pour quand ?" />

            <p class="mb-4 text-sm text-stone-600">Demandes envoyées cette semaine : <strong>{{ $used }} / {{ $quota }}</strong>.</p>
            <x-button class="w-full" :disabled="$used >= $quota">Envoyer la demande</x-button>
        </form>
    </x-card>

    <p class="text-xs text-stone-500">Le règlement d'une prestation se fait directement entre vous : aucun paiement ne transite par IDEE.</p>
</x-layout>
