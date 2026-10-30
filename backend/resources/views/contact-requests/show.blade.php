<x-layout title="Demande de contact">
    <a href="{{ route('contact-requests.index') }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← Demandes</a>

    <x-card>
        <div class="flex items-center justify-between gap-2">
            <p class="text-sm text-stone-500">
                De <a href="{{ route('members.show', $contactRequest->sender) }}" class="font-semibold text-stone-800 underline">{{ $contactRequest->sender->name }}</a>
                à <a href="{{ route('members.show', $contactRequest->recipient) }}" class="font-semibold text-stone-800 underline">{{ $contactRequest->recipient->name }}</a>
            </p>
            <x-status :status="$contactRequest->status" />
        </div>
        @if ($contactRequest->project)
            <p class="mt-2 text-sm">Projet : <a href="{{ route('projects.show', $contactRequest->project) }}" class="font-medium underline">{{ $contactRequest->project->title }}</a></p>
        @endif
        @if ($contactRequest->need)
            <p class="text-sm">Besoin : {{ $contactRequest->need->title }}</p>
        @endif
        <p class="mt-3 whitespace-pre-line text-stone-800">{{ $contactRequest->message }}</p>
        <p class="mt-3 text-xs text-stone-400">Envoyée {{ $contactRequest->created_at->diffForHumans() }}
            @if ($contactRequest->status->isPending()) · expire le {{ $contactRequest->expires_at->translatedFormat('d F') }}@endif
        </p>
        @if ($contactRequest->refusal_reason)
            <p class="mt-3 rounded-xl bg-stone-50 p-3 text-sm"><span class="font-medium">Motif du refus :</span> {{ $contactRequest->refusal_reason }}</p>
        @endif
    </x-card>

    @if ($canRespond)
        <x-card>
            <h2 class="mb-3 font-semibold">Votre réponse</h2>
            <form method="POST" action="{{ route('contact-requests.respond', $contactRequest) }}">
                @csrf
                <fieldset class="mb-4 space-y-2">
                    <legend class="sr-only">Réponse</legend>
                    @foreach ($responses as $response)
                        <label class="flex min-h-12 items-center gap-3 rounded-xl border border-stone-200 px-3">
                            <input type="radio" name="status" value="{{ $response->value }}" @checked(old('status') === $response->value) class="h-5 w-5 accent-brand-700">
                            <span>{{ $response->label() }}</span>
                        </label>
                    @endforeach
                </fieldset>
                <x-field name="refusal_reason" type="textarea" rows="2" label="Motif, en cas de refus"
                         hint="Un motif clair aide l'expéditeur à améliorer sa demande." />
                <x-button class="w-full">Envoyer ma réponse</x-button>
            </form>
        </x-card>
    @elseif ($contactRequest->status->value === 'interesse')
        {{-- Les coordonnées ne sont partagées qu'après l'accord des deux parties (double consentement). --}}
        @php($other = $contactRequest->sender->is(auth()->user()) ? $contactRequest->recipient : $contactRequest->sender)
        <x-card class="border-brand-100 bg-brand-50">
            <h2 class="font-semibold text-brand-800">Vous êtes tous les deux intéressés</h2>
            <p class="mt-1 text-sm text-brand-800">Prenez contact avec {{ $other->name }} pour convenir d'un premier échange.</p>
            <p class="mt-2 text-lg font-semibold">{{ \App\Support\PhoneNumber::format($other->phone) }}</p>
            <x-button :href="'https://wa.me/'.ltrim($other->phone, '+')" class="mt-3 w-full" target="_blank" rel="noopener">Écrire sur WhatsApp</x-button>
        </x-card>
    @endif
</x-layout>
