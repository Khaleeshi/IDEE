{{-- Assistant IA du porteur (section 7) : il structure et recommande, sans décider à sa place. --}}
<section id="assistant" class="mt-6" aria-labelledby="titre-assistant">
    <h2 id="titre-assistant" class="mb-1 text-lg font-bold">Assistant IA</h2>

    @unless ($assistantEnabled)
        <x-card class="text-sm text-stone-600">L'assistant IA (questions pour clarifier votre idée, business plan rédigé pour vous) arrive bientôt sur IDEE.</x-card>
    @else
        <x-card>
            @if ($clarification?->status->value === 'en_cours')
                <p class="text-sm text-stone-600">L'assistant prépare ses questions… <a href="{{ route('projects.show', $project) }}#assistant" class="font-medium text-brand-700 underline">Actualiser</a></p>
            @elseif ($clarification?->status->value === 'pret')
                <h3 class="font-semibold">Questions pour clarifier votre idée</h3>
                <form method="POST" action="{{ route('assistant.answer', $clarification) }}" class="mt-2">
                    @csrf
                    @method('PUT')
                    @foreach ($clarification->questions as $index => $question)
                        <label for="reponse-{{ $index }}" class="mt-3 block text-sm font-medium text-stone-800">{{ $question }}</label>
                        <textarea id="reponse-{{ $index }}" name="answers[{{ $index }}]" rows="2" maxlength="2000"
                                  class="mt-1 block w-full rounded-xl border border-stone-300 px-3 py-2 text-base">{{ $clarification->answers[$index] ?? '' }}</textarea>
                    @endforeach
                    <x-button variant="secondary" class="mt-3 w-full">Enregistrer mes réponses</x-button>
                </form>
                @if ($clarification->suggestions)
                    <h3 class="mt-4 font-semibold">Conseils pour avancer</h3>
                    <ul class="mt-1 list-disc space-y-1 pl-5 text-sm text-stone-700">
                        @foreach ($clarification->suggestions as $suggestion)
                            <li>{{ $suggestion }}</li>
                        @endforeach
                    </ul>
                @endif
            @elseif ($clarification?->status->value === 'echec')
                <p class="text-sm text-red-700">{{ $clarification->error }}</p>
            @endif

            <form method="POST" action="{{ route('assistant.clarify', $project) }}" class="mt-3">
                @csrf
                <x-button variant="secondary" class="w-full">{{ $clarification ? 'Me poser d\'autres questions' : 'Clarifier mon idée avec l\'assistant' }}</x-button>
            </form>

            <div class="mt-4 border-t border-stone-100 pt-4">
                <h3 class="font-semibold">Business plan</h3>
                <p class="mt-1 text-sm text-stone-600">L'assistant rédige un premier business plan à partir de votre fiche et de vos réponses. Vous le corrigez ensuite : c'est votre document.</p>
                <x-button :href="route('business-plans.show', $project)" class="mt-3 w-full">{{ $latestPlan ? 'Voir mon business plan' : 'Créer mon business plan' }}</x-button>
            </div>
        </x-card>
    @endunless
</section>
