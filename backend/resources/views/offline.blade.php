<x-layout title="Hors connexion">
    <x-card class="text-center">
        <h1 class="text-xl font-bold">Pas de connexion pour le moment</h1>
        <p class="mt-2 text-stone-600">Vérifiez votre réseau, puis réessayez. Les pages déjà consultées restent disponibles.</p>
        <x-button :href="route('home')" class="mt-4">Réessayer</x-button>
    </x-card>
</x-layout>
