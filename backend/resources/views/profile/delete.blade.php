<x-layout title="Supprimer mon compte">
    <a href="{{ route('profile.edit') }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← Mon profil</a>
    <h1 class="mb-4 text-2xl font-bold">Supprimer mon compte</h1>

    <x-card class="text-sm">
        <p class="font-semibold">Ce qui sera effacé</p>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-stone-700">
            <li>Votre profil : nom, téléphone, e-mail, photo, présentation, compétences.</li>
            <li>{{ $projects > 0 ? 'Vos '.$projects.' projet(s)' : 'Vos projets' }}, avec leurs besoins, documents, business plans et présentations.</li>
            <li>Vos abonnements, alertes, parcours, inscriptions aux événements et publications dans les groupes.</li>
            <li>Vos pièces de vérification et vos notifications.</li>
        </ul>
        <p class="mt-4 font-semibold">Ce qui reste, sous le nom « Membre supprimé »</p>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-stone-700">
            <li>Les messages déjà envoyés dans les conversations des autres membres : ils leur appartiennent aussi.</li>
            <li>Les accords et collaborations confirmés avec eux.</li>
            <li>Vos paiements et reçus, que la loi oblige à conserver pour la comptabilité.</li>
            <li>Les signalements et décisions de modération, gardés comme preuve.</li>
        </ul>
        <p class="mt-4 text-stone-700">La suppression est définitive. Avant, vous pouvez
            <a href="{{ route('account.export') }}" class="font-medium text-brand-700 underline">télécharger toutes vos données</a>.</p>
    </x-card>

    <x-card>
        <form method="POST" action="{{ route('account.destroy') }}">
            @csrf
            @method('DELETE')
            <x-field name="confirmation" :label="'Pour confirmer, écrivez '.$word" autocomplete="off" required />
            <x-button variant="danger" class="w-full">Supprimer définitivement mon compte</x-button>
        </form>
    </x-card>
</x-layout>
