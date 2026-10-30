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
            @if ($other->phone)
                <p class="mt-2 text-lg font-semibold">{{ \App\Support\PhoneNumber::format($other->phone) }}</p>
                <x-button :href="'https://wa.me/'.ltrim($other->phone, '+')" class="mt-3 w-full" target="_blank" rel="noopener">Écrire sur WhatsApp</x-button>
            @elseif ($other->email)
                <p class="mt-2 text-lg font-semibold">{{ $other->email }}</p>
            @endif
        </x-card>

        @include('contact-requests.partials.collaboration')

        <section id="messages" aria-labelledby="titre-messages">
            <h2 id="titre-messages" class="mb-3 mt-6 text-lg font-bold">Messages</h2>

            @forelse ($contactRequest->messages as $message)
                @php($mine = $message->sender_id === auth()->id())
                <div class="mb-2 flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[85%] rounded-2xl px-3 py-2 text-sm {{ $mine ? 'bg-brand-700 text-white' : 'border border-stone-200 bg-white text-stone-800' }}">
                        @if ($message->isRemoved())
                            <p class="italic">Message retiré par la modération.</p>
                        @elseif ($message->body)
                            <p class="whitespace-pre-line">{{ $message->body }}</p>
                        @endif
                        @if ($message->isRemoved())
                        @elseif ($message->kind->value === 'vocal')
                            <audio controls preload="none" src="{{ route('messages.attachment', $message) }}" class="mt-1 max-w-full"></audio>
                        @elseif ($message->hasAttachment())
                            <a href="{{ route('messages.attachment', $message) }}" class="mt-1 block font-medium underline">📎 {{ $message->attachment_name }}</a>
                        @endif
                        <p class="mt-1 text-[11px] {{ $mine ? 'text-brand-50' : 'text-stone-400' }}">
                            {{ $message->created_at->translatedFormat('d F, H:i') }}@if ($mine && $message->read_at) · lu @endif
                        </p>
                    </div>
                </div>
            @empty
                <p class="mb-3 text-sm text-stone-500">Écrivez le premier message pour convenir d'un échange.</p>
            @endforelse

            <form method="POST" action="{{ route('messages.store', $contactRequest) }}" enctype="multipart/form-data" class="mt-3 rounded-2xl border border-stone-200 bg-white p-3">
                @csrf
                <label for="message-texte" class="sr-only">Votre message</label>
                <textarea id="message-texte" name="body" rows="2" maxlength="4000" placeholder="Votre message…"
                          class="block w-full rounded-xl border border-stone-300 px-3 py-3 text-base focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-100">{{ old('body') }}</textarea>
                <label for="message-fichier" class="mt-2 block text-sm text-stone-600">Joindre un document ou une photo (facultatif)</label>
                <input id="message-fichier" name="attachment" type="file" accept=".pdf,image/*,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt" class="mt-1 block w-full text-sm">
                <x-button class="mt-3 w-full">Envoyer</x-button>
            </form>

            <form method="POST" action="{{ route('messages.store', $contactRequest) }}" enctype="multipart/form-data" class="mt-2 rounded-2xl border border-stone-200 bg-white p-3">
                @csrf
                <label for="message-vocal" class="block text-sm font-medium text-stone-800">Message vocal</label>
                <input id="message-vocal" name="attachment" type="file" accept="audio/*" capture required class="mt-1 block w-full text-sm">
                <x-button variant="secondary" class="mt-2 w-full">Envoyer le vocal</x-button>
            </form>

            @php($lastReceived = $contactRequest->messages->where('sender_id', '!=', auth()->id())->whereNull('removed_at')->last())
            @if ($lastReceived)
                <x-report-form type="message" :id="$lastReceived->id" label="Signaler le dernier message reçu" class="mt-4 text-center" />
            @endif
        </section>
    @endif
</x-layout>
