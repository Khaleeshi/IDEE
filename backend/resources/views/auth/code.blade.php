<x-layout title="Code de connexion">
    <h1 class="mb-1 text-2xl font-bold">Saisissez votre code</h1>
    <p class="mb-6 text-stone-600">Envoyé {{ str_contains($phone, '@') ? 'à '.$phone : 'au '.\App\Support\PhoneNumber::format($phone) }}. Il est valable {{ config('idee.otp.ttl_minutes') }} minutes.</p>

    <x-card>
        <form method="POST" action="{{ route('login.verify') }}">
            @csrf
            <input type="hidden" name="phone" value="{{ $phone }}">
            <x-field name="code" label="Code à {{ config('idee.otp.length') }} chiffres" inputmode="numeric" autocomplete="one-time-code"
                     maxlength="{{ config('idee.otp.length') }}" required autofocus class="mb-4 [&_input]:text-center [&_input]:text-2xl [&_input]:tracking-[0.5em]" />
            <x-field name="name" label="Votre nom (première connexion)" autocomplete="name" placeholder="Prénom et nom"
                     hint="Laissez vide si vous avez déjà un compte." />
            <x-button class="w-full">Me connecter</x-button>
        </form>
    </x-card>

    <form method="POST" action="{{ route('login.send') }}" class="text-center">
        @csrf
        <input type="hidden" name="phone" value="{{ $phone }}">
        <button type="submit" class="py-3 text-sm font-medium text-brand-700 underline">Recevoir un nouveau code</button>
    </form>
</x-layout>
