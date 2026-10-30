{{-- CF-05 : accord de confidentialité numérique, accepté avant toute demande d'accès aux documents privés. --}}
<details class="rounded-xl bg-stone-50 p-3 text-xs text-stone-700">
    <summary class="cursor-pointer text-sm font-medium text-stone-800">Lire l'accord de confidentialité</summary>
    <p class="mt-2">En demandant l'accès aux documents privés de « {{ $project->title }} », je m'engage envers son porteur à :</p>
    <ol class="mt-1 list-decimal space-y-1 pl-5">
        <li>n'utiliser ces documents et les informations qu'ils contiennent que pour étudier une collaboration avec ce projet ;</li>
        <li>ne pas les copier, les transmettre ni les divulguer, en tout ou en partie, sans l'accord écrit du porteur ;</li>
        <li>ne pas reprendre l'idée, les chiffres ou les méthodes du projet pour mon propre compte ou celui d'un tiers ;</li>
        <li>cesser de les utiliser et supprimer toute copie dès que le porteur me retire l'accès ou me le demande.</li>
    </ol>
    <p class="mt-2">Cet engagement vaut pendant trois ans. Chaque consultation est enregistrée. Un manquement peut entraîner l'exclusion d'IDEE, sans préjudice des recours du porteur.</p>
    <p class="mt-2 text-stone-500">Version {{ config('idee.documents.nda_version') }}.</p>
</details>
