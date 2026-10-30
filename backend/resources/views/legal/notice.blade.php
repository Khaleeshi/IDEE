@php($missing = 'à compléter avant l\'ouverture')
<x-legal-page title="Mentions légales">
    <h2>Éditeur</h2>
    <ul>
        <li>{{ config('idee.company.name') }}</li>
        <li>Adresse : {{ config('idee.company.address') ?: $missing }}</li>
        <li>RCCM : {{ config('idee.company.rccm') ?: $missing }}</li>
        <li>NINEA : {{ config('idee.company.ninea') ?: $missing }}</li>
        <li>Contact : {{ config('idee.company.email') ?: $missing }}</li>
        <li>Direction de la publication : {{ config('idee.company.publisher') ?: $missing }}</li>
    </ul>

    <h2>Hébergeur</h2>
    <p>{{ config('idee.company.host') ?: $missing }}</p>

    <h2>Contenus publiés par les membres</h2>
    <p>Les projets, besoins et messages sont publiés par les membres, qui en restent responsables. IDEE les héberge et retire
        rapidement un contenu illicite qui lui est signalé (loi n° 2008-08). Pour signaler un contenu, utilisez le bouton
        « Signaler » présent sur chaque profil, projet et message, ou écrivez à l'adresse de contact.</p>

    <h2>Textes à connaître</h2>
    <ul>
        <li><a href="{{ route('legal.terms') }}">Conditions d'utilisation</a></li>
        <li><a href="{{ route('legal.privacy') }}">Politique de confidentialité</a></li>
        <li><a href="{{ route('legal.charter') }}">Charte communautaire</a></li>
    </ul>
</x-legal-page>
