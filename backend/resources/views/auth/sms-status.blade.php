<x-layout title="Paiement du SMS">
    <h1 class="mb-1 text-2xl font-bold">Paiement du SMS</h1>
    <p class="mb-6 text-stone-600">{{ number_format($purchase->amount_fcfa, 0, ',', "\u{00A0}") }} FCFA · {{ $purchase->method->label() }} · {{ \App\Support\PhoneNumber::format($purchase->phone) }}</p>

    <x-card>
        <p class="font-semibold">{{ $purchase->status->label() }}</p>

        @if ($purchase->isUsable())
            <p class="mt-1 text-sm text-stone-600">Reçu {{ $purchase->receipt_number }}. Il reste {{ $purchase->sends_remaining }} envoi(s) sur ce paiement.</p>
            <form method="POST" action="{{ route('login.sms.resend', $purchase) }}" class="mt-3">
                @csrf
                <x-button class="w-full">Recevoir mon code par SMS</x-button>
            </form>
        @elseif ($purchase->status->value === 'en_attente')
            @include('payments.partials.pending-bictorys', ['instructions' => $purchase->instructions, 'link' => $purchase->launch_url, 'check' => route('login.sms.show', $purchase), 'checkMethod' => 'GET'])
        @else
            <p class="mt-1 text-sm text-stone-600">Ce paiement ne permet plus d'envoyer de SMS.</p>
            <x-button :href="route('login')" variant="secondary" class="mt-3 w-full">Revenir à la connexion</x-button>
        @endif
    </x-card>
</x-layout>
