<x-layout title="Bienvenue sur IDEE">
    <h1 class="mb-1 text-2xl font-bold">{{ $renewal ? 'Nos textes ont changé' : 'Bienvenue sur IDEE' }}</h1>
    <p class="mb-4 text-stone-600">{{ $renewal ? 'Relisez et acceptez la nouvelle version pour continuer.' : 'Avant de commencer, voici les règles qui protègent chaque membre.' }}</p>

    <x-card class="text-sm">
        <ul class="list-disc space-y-2 pl-5 text-stone-700">
            <li><strong>Un investisseur ou une institution ne demande jamais d'argent</strong> à un porteur de projet pour étudier son dossier.</li>
            <li>Aucun argent entre membres ne passe par IDEE, et IDEE ne propose jamais de parts ou d'actions au public.</li>
            <li>Chaque demande de contact reçoit une réponse, même un refus.</li>
            <li>Votre téléphone et votre e-mail ne sont jamais affichés aux autres membres.</li>
            <li>Les arnaques, faux profils et envois en masse sont sanctionnés, après décision d'un modérateur.</li>
        </ul>
        <p class="mt-4">Textes complets :
            <a href="{{ route('legal.terms') }}" class="font-medium text-brand-700 underline">conditions d'utilisation</a>,
            <a href="{{ route('legal.charter') }}" class="font-medium text-brand-700 underline">charte communautaire</a>,
            <a href="{{ route('legal.privacy') }}" class="font-medium text-brand-700 underline">politique de confidentialité</a>.</p>
    </x-card>

    <x-card>
        <form method="POST" action="{{ route('terms.accept') }}">
            @csrf
            <label class="mb-4 flex min-h-12 items-start gap-3">
                <input type="checkbox" name="accept" value="1" required class="mt-1 h-5 w-5 accent-brand-700">
                <span>J'ai lu et j'accepte les conditions d'utilisation et la charte communautaire.</span>
            </label>
            <x-button class="w-full">Continuer</x-button>
        </form>
    </x-card>

    <form method="POST" action="{{ route('logout') }}" class="text-center">
        @csrf
        <button type="submit" class="py-3 text-sm font-medium text-stone-500 underline">Se déconnecter</button>
    </form>
</x-layout>
