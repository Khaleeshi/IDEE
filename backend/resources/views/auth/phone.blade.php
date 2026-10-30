<x-layout title="Connexion">
    <h1 class="mb-1 text-2xl font-bold">Connexion</h1>
    <p class="mb-6 text-stone-600">Pas de mot de passe : nous vous envoyons un code sur votre téléphone, ou par e-mail.</p>

    <x-card>
        <form method="POST" action="{{ route('login.send') }}">
            @csrf
            <x-field name="phone" label="Numéro de téléphone ou adresse e-mail" type="text" autocomplete="username" required autofocus
                     placeholder="77 123 45 67" hint="Depuis l'étranger, ajoutez l'indicatif (+33 6 12 34 56 78) ou utilisez votre e-mail." />
            <x-button class="w-full">Recevoir mon code</x-button>
        </form>
    </x-card>

    <p class="text-sm text-stone-500">Nouveau sur IDEE ? Le compte est créé à votre première connexion.</p>
</x-layout>
