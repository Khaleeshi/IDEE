{{-- PA-04, CP-07 : paiement Bictorys en attente (lien Wave ou carte, code USSD d'Orange Money). --}}
@if ($instructions)
    <p class="mt-2 rounded-xl bg-stone-50 p-3 text-sm text-stone-800">{{ $instructions }}</p>
@endif
@if ($link)
    <x-button :href="$link" class="mt-3 w-full">Payer maintenant</x-button>
@endif
<p class="mt-3 text-sm text-stone-600">Une fois le paiement fait, la confirmation arrive en quelques instants.</p>
@php($checkMethod ??= 'POST')
<form method="{{ $checkMethod }}" action="{{ $check }}" class="mt-2">
    @if ($checkMethod === 'POST')
        @csrf
    @endif
    <x-button variant="secondary" class="w-full">J'ai payé : vérifier</x-button>
</form>
