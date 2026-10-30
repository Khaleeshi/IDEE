<x-layout title="Préparer le rendez-vous" :refresh="$document->status->value === 'en_cours' ? 10 : null">
    <a href="{{ route('contact-requests.show', $document->meeting->contactRequest) }}#collaboration" class="mb-3 inline-block py-1 text-sm text-stone-500">← Conversation</a>
    <h1 class="text-2xl font-bold">Préparer le rendez-vous</h1>
    <p class="mt-1 text-sm text-stone-500">{{ $document->meeting->starts_at->translatedFormat('l d F à H\hi') }} · {{ $document->meeting->mode->label() }}</p>

    @if ($document->status->value === 'en_cours')
        <x-card class="mt-4 text-center">
            <p class="font-medium">L'assistant prépare votre rendez-vous…</p>
            <p class="mt-1 text-sm text-stone-500">Cette page se met à jour toute seule.</p>
        </x-card>
    @elseif ($document->status->value === 'echec')
        <x-card class="mt-4 text-sm"><p class="text-red-700">{{ $document->error }}</p></x-card>
    @else
        @php($content = $document->content)
        <x-card class="mt-4">
            <h2 class="text-sm font-semibold text-stone-500">Objectif</h2>
            <p class="mt-1 font-medium">{{ $content['objective'] }}</p>
        </x-card>
        @foreach (['questions' => 'Questions à poser', 'talking_points' => 'Points à présenter', 'to_bring' => 'À apporter ou à vérifier', 'next_steps' => 'Prochaines étapes à proposer'] as $key => $title)
            @if (! empty($content[$key]))
                <h2 class="mb-2 mt-5 font-bold">{{ $title }}</h2>
                <ul class="list-disc space-y-1 pl-5 text-sm text-stone-800">
                    @foreach ($content[$key] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            @endif
        @endforeach
        <p class="mt-5 rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-900">Ces suggestions vous aident à vous préparer ; adaptez-les à ce que vous savez de votre interlocuteur. À la fin du rendez-vous, notez vos prochaines étapes dans la conversation.</p>
    @endif
</x-layout>
