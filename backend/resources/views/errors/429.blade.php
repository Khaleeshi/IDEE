<x-layout title="Trop de tentatives">
    <x-card class="text-center">
        <p class="text-sm font-semibold text-stone-500">Erreur 429</p>
        <h1 class="mt-1 text-xl font-bold">Trop de tentatives</h1>
        <p class="mt-2 text-stone-600">Patientez une minute avant de réessayer.</p>
        <x-button :href="route('home')" class="mt-4">Retour à l'accueil</x-button>
    </x-card>
</x-layout>
