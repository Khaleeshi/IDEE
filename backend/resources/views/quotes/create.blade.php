<x-layout title="Demander un devis à {{ $expert->name }}">
    <a href="{{ route('members.show', $expert) }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← {{ $expert->name }}</a>
    <h1 class="text-2xl font-bold">Demander un devis</h1>
    <p class="mt-1 text-sm text-stone-600">{{ $expert->name }}@if ($expert->expertise) · {{ $expert->expertise }}@endif @if ($expert->rate) · tarif indicatif : {{ $expert->rate }}@endif</p>

    <form method="POST" action="{{ route('quotes.store', $expert) }}" class="mt-4">
        @csrf
        @if ($projects->isNotEmpty())
            <x-select name="project_id" label="Projet concerné" :options="$projects->pluck('title', 'id')->all()" placeholder="Aucun en particulier" />
        @endif
        <x-field name="description" type="textarea" rows="5" label="Prestation attendue" maxlength="3000" required
                 placeholder="Ex. : rédiger les statuts d'une SARL à deux associés et préparer le dossier pour l'APIX" />
        <x-field name="budget" label="Budget envisagé (facultatif)" placeholder="Ex. : 100 000 FCFA" maxlength="120" />
        <x-field name="wanted_by" type="date" label="Pour quand (facultatif)" />
        <x-button class="w-full">Envoyer la demande</x-button>
    </form>
    <p class="mt-3 text-xs text-stone-500">Le devis et le paiement se règlent directement entre vous et l'expert : IDEE ne prend aucune commission et aucun argent ne transite par la plateforme.</p>
</x-layout>
