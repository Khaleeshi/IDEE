<x-layout title="Session expirée">
    <x-card class="text-center">
        <p class="text-sm font-semibold text-stone-500">Erreur 419</p>
        <h1 class="mt-1 text-xl font-bold">Session expirée</h1>
        <p class="mt-2 text-stone-600">La page est restée ouverte trop longtemps. Revenez en arrière et réessayez.</p>
        <x-button :href="route('home')" class="mt-4">Retour à l'accueil</x-button>
    </x-card>
</x-layout>
