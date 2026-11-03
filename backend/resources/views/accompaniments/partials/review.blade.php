{{-- AV-01 à AV-03 : un avis, sa note et la réponse de la personne évaluée. --}}
<x-card class="text-sm">
    <p class="font-semibold text-brand-800" aria-label="{{ $review->rating }} sur 5">{{ str_repeat('★', $review->rating) }}<span class="text-stone-300">{{ str_repeat('★', 5 - $review->rating) }}</span></p>
    @if ($review->comment)
        <p class="mt-1 whitespace-pre-line text-stone-800">{{ $review->comment }}</p>
    @endif
    <p class="mt-1 text-xs text-stone-500">{{ $review->reviewer?->name ?? 'Membre supprimé' }} · {{ $review->created_at->translatedFormat('d F Y') }}</p>
    @if ($review->reply)
        <p class="mt-2 rounded-lg bg-stone-50 p-2"><span class="font-medium">Réponse :</span> {{ $review->reply }}</p>
    @elseif ($review->reviewee_id === auth()->id())
        <form method="POST" action="{{ route('reviews.reply', $review) }}" class="mt-2">
            @csrf
            <x-field name="reply" label="Répondre publiquement (une seule fois)" maxlength="1000" />
            <x-button variant="secondary" class="w-full min-h-10 py-2 text-sm">Publier ma réponse</x-button>
        </form>
    @endif
</x-card>
