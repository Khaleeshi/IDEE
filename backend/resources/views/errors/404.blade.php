<x-layout title="Page introuvable">
    <x-card class="text-center">
        <p class="text-sm font-semibold text-stone-500">Erreur 404</p>
        <h1 class="mt-1 text-xl font-bold">Page introuvable</h1>
        <p class="mt-2 text-stone-600">Cette page n'existe pas ou n'est plus disponible.</p>
        <x-button :href="route('home')" class="mt-4">Retour à l'accueil</x-button>
    </x-card>
</x-layout>
