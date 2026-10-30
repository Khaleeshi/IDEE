<x-legal-page title="Charte communautaire">
    <p>IDEE est un réseau de projets et d'actions : chacun vient pour faire avancer un projet, le sien ou celui d'un autre.
        Cette charte dit ce qui est attendu de chaque membre. Elle est acceptée à l'inscription.</p>

    <p class="rounded-xl border border-brand-100 bg-brand-50 px-4 py-3 font-semibold text-brand-900">
        Un investisseur ou une institution ne demande jamais d'argent à un porteur de projet pour étudier son dossier.
    </p>

    <h2>Ce que nous attendons</h2>
    <ul>
        <li>Des informations exactes sur vous et sur vos projets.</li>
        <li>Une réponse à chaque demande de contact, même un refus : sans réponse, elle expire au bout de {{ config('idee.contact_expire_after_days') }} jours.</li>
        <li>Le respect des idées des autres : on ne copie pas un projet découvert sur IDEE et on ne partage pas ses documents privés.</li>
        <li>Le signalement de tout comportement suspect.</li>
    </ul>

    <h2>Ce qui est interdit</h2>
    <ul>
        <li>Demander des « frais de dossier », un transfert d'argent, un code reçu par SMS ou des coordonnées bancaires.</li>
        <li>Collecter de l'argent pour un projet, proposer des parts ou des actions au public, promettre un « rendement garanti ».</li>
        <li>Les faux profils, les comptes multiples et l'usurpation d'une personne ou d'une institution.</li>
        <li>Les envois en masse, la publicité non sollicitée et les liens trompeurs.</li>
        <li>Le harcèlement, les menaces, les insultes, les propos haineux ou discriminatoires.</li>
        <li>Les contenus illégaux, les contrefaçons et la diffusion des données d'une autre personne sans son accord.</li>
    </ul>

    <h2>Signalements et sanctions</h2>
    <ul>
        <li>Chaque profil, projet et message peut être signalé en deux clics. Les soupçons d'arnaque passent en tête de file.</li>
        <li>Un modérateur décide : aucune action, avertissement, retrait du contenu, suspension de {{ config('idee.moderation.suspension_days') }} jours ou exclusion. La décision finale est toujours humaine.</li>
        <li>Après {{ config('idee.moderation.reports_before_suspension') }} signalements de personnes différentes, un compte est suspendu par précaution en attendant l'examen d'un modérateur.</li>
        <li>La personne concernée connaît le motif et peut contester une fois, dans les {{ config('idee.moderation.appeal_days') }} jours : un second modérateur examine le recours.</li>
        <li>Nous visons une décision en moins de 24 heures pour un soupçon d'arnaque, en moins de 72 heures pour les autres signalements.</li>
    </ul>
</x-legal-page>
