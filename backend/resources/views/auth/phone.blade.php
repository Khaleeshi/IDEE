<x-layout title="Connexion">
    <h1 class="mb-1 text-2xl font-bold">Connexion</h1>
    <p class="mb-6 text-stone-600">Pas de mot de passe : nous vous envoyons un code par WhatsApp, par e-mail ou par SMS.</p>

    <x-card>
        <form method="POST" action="{{ route('login.send') }}">
            @csrf
            {{-- CP-07 : le pays ajoute l'indicatif ; un numéro commençant par « + » le garde. --}}
            <x-select name="country" label="Pays" :options="$countries" :value="old('country', 'SN')" />
            <x-field name="phone" label="Numéro de téléphone ou adresse e-mail" type="text" autocomplete="username" required autofocus
                     placeholder="77 123 45 67" hint="Avec une adresse e-mail, le code arrive par e-mail, gratuitement." />

            <fieldset class="mb-4">
                <legend class="text-sm font-medium text-stone-800">Avec un numéro, recevoir le code par</legend>
                <div class="mt-2 space-y-2">
                    <label class="flex items-center gap-3 rounded-xl border border-stone-200 p-3">
                        <input type="radio" name="channel" value="whatsapp" @checked(old('channel', 'whatsapp') === 'whatsapp') class="h-5 w-5 accent-brand-700">
                        <span><span class="font-medium">WhatsApp</span> <span class="text-sm text-stone-500">· gratuit</span></span>
                    </label>
                    @if ($paidSms)
                        <label class="flex items-center gap-3 rounded-xl border border-stone-200 p-3">
                            <input type="radio" name="channel" value="sms" @checked(old('channel') === 'sms') class="h-5 w-5 accent-brand-700">
                            <span><span class="font-medium">SMS</span> <span class="text-sm text-stone-500">· {{ number_format($smsPrice, 0, ',', "\u{00A0}") }} FCFA, payés avant l'envoi</span></span>
                        </label>
                    @endif
                </div>
                @error('channel')
                    <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </fieldset>

            <x-button class="w-full">Recevoir mon code</x-button>
        </form>
    </x-card>

    <p class="text-sm text-stone-500">Nouveau sur IDEE ? Le compte est créé à votre première connexion.</p>
</x-layout>
