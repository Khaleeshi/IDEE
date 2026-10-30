<x-layout title="Accès refusé">
    <x-card class="text-center">
        <p class="text-sm font-semibold text-stone-500">Erreur 403</p>
        <h1 class="mt-1 text-xl font-bold">Accès refusé</h1>
        <p class="mt-2 text-stone-600">Vous n'avez pas le droit d'effectuer cette action.</p>
        <x-button :href="route('home')" class="mt-4">Retour à l'accueil</x-button>
    </x-card>
</x-layout>
