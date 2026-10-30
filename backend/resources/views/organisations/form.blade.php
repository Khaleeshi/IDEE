<x-layout title="Créer une page d'organisation">
    <a href="{{ route('organisations.index') }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← Organisations</a>
    <h1 class="text-2xl font-bold">Créer une page d'organisation</h1>
    <p class="mt-1 text-sm text-stone-600">Pour une entreprise, un incubateur, une institution, un financeur ou une association. Vous pourrez ensuite ajouter vos collègues et demander la vérification.</p>

    <form method="POST" action="{{ route('organisations.store') }}" class="mt-4">
        @csrf
        @include('organisations.partials.fields')
        <x-button class="w-full">Créer la page</x-button>
    </form>
</x-layout>
