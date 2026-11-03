<x-layout title="Payer le SMS">
    <a href="{{ route('login') }}" class="mb-2 inline-block text-sm text-stone-500">← Connexion</a>
    <h1 class="mb-1 text-2xl font-bold">Recevoir le code par SMS</h1>
    <p class="mb-6 text-stone-600">
        Le SMS coûte {{ number_format($price, 0, ',', "\u{00A0}") }} FCFA, payés avant l'envoi au {{ \App\Support\PhoneNumber::format($phone) }}.
        Un renvoi est compris si le premier code n'arrive pas.
    </p>

    <x-card>
        <form method="POST" action="{{ route('login.sms.start') }}">
            @csrf
            <input type="hidden" name="phone" value="{{ $phone }}">
            <fieldset class="mb-4">
                <legend class="text-sm font-medium text-stone-800">Payer avec</legend>
                <div class="mt-2 space-y-2">
                    @foreach ($methods as $method)
                        <label class="flex items-center gap-3 rounded-xl border border-stone-200 p-3">
                            <input type="radio" name="method" value="{{ $method->value }}" @checked($loop->first) class="h-5 w-5 accent-brand-700">
                            <span class="font-medium">{{ $method->label() }}</span>
                        </label>
                    @endforeach
                </div>
                @error('method')
                    <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </fieldset>
            <x-button class="w-full">Payer {{ number_format($price, 0, ',', "\u{00A0}") }} FCFA</x-button>
        </form>
    </x-card>

    <form method="POST" action="{{ route('login.send') }}" class="text-center">
        @csrf
        <input type="hidden" name="phone" value="{{ $phone }}">
        <input type="hidden" name="channel" value="whatsapp">
        <button type="submit" class="py-3 text-sm font-medium text-brand-700 underline">Recevoir plutôt le code par WhatsApp, gratuitement</button>
    </form>
</x-layout>
