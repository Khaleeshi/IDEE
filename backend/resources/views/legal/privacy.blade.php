<x-legal-page title="Politique de confidentialité">
    <p>Cette page explique quelles données IDEE garde sur vous, pourquoi, et comment exercer vos droits (loi n° 2008-12 sur
        la protection des données personnelles). Le responsable du traitement est {{ config('idee.company.name') }}.</p>

    <h2>Les données que nous gardons</h2>
    <ul>
        <li>Votre compte : téléphone ou e-mail, nom, région, langues, présentation, compétences, rôles et photo si vous en ajoutez une.</li>
        <li>Vos projets et besoins, avec leurs détails privés, leurs documents et leur journal de progression.</li>
        <li>Vos demandes de contact et vos messages (texte, vocal, documents).</li>
        <li>La pièce d'identité envoyée pour une vérification : elle est effacée dès que l'équipe a décidé.</li>
        <li>Vos paiements : offre, montant, moyen de paiement, reçu.</li>
        <li>Des informations techniques : connexions, appareil, notifications envoyées.</li>
    </ul>

    <h2>Pourquoi</h2>
    <ul>
        <li>Faire fonctionner le réseau : profils, projets, demandes de contact, messagerie, parcours guidés.</li>
        <li>Protéger les membres : vérifications, lutte contre les arnaques et modération.</li>
        <li>Vous recommander des projets, des personnes ou des opportunités, toujours avec la raison affichée.</li>
        <li>Encaisser les offres payantes et tenir la comptabilité.</li>
    </ul>

    <h2>Qui voit quoi</h2>
    <ul>
        <li>Tout le monde voit votre nom, votre région, vos rôles, votre présentation et le résumé public de vos projets.</li>
        <li>Votre téléphone et votre e-mail ne sont jamais affichés aux autres membres.</li>
        <li>Les détails privés et les documents d'un projet ne s'ouvrent qu'avec l'accord du porteur ; chaque accès est noté.</li>
        <li>Une conversation n'est lue que par les deux personnes, et par l'équipe de modération si un message est signalé.</li>
    </ul>

    <h2>Nos prestataires</h2>
    <p>Pour fonctionner, IDEE confie certaines tâches à des prestataires, qui n'utilisent vos données que pour ce service :
        l'hébergeur du site ; Orange et, si elle est activée, la messagerie WhatsApp de Meta pour les codes et les alertes ;
        un service d'envoi d'e-mails ; les services de notification des navigateurs et des téléphones ; Wave et Orange Money
        pour les paiements ; Anthropic (États-Unis) pour l'assistant IA. Les numéros d'identité, coordonnées bancaires et
        documents d'identité ne sont jamais envoyés à l'assistant. IDEE déclare les transferts de données hors du Sénégal à la
        Commission de protection des données personnelles (CDP).</p>

    <h2>Combien de temps</h2>
    <ul>
        <li>Votre compte et vos contenus : tant que votre compte existe. Ils sont effacés quand vous le supprimez.</li>
        <li>Les codes de connexion : valables {{ config('idee.otp.ttl_minutes') }} minutes.</li>
        <li>Les paiements et reçus : dix ans, durée de conservation des pièces comptables.</li>
        <li>Les signalements et décisions de modération : gardés comme preuve, même après la suppression d'un compte.</li>
    </ul>

    <h2>Vos droits</h2>
    <ul>
        <li><strong>Accès</strong> : téléchargez toutes vos données depuis votre profil, rubrique « Mes données ».</li>
        <li><strong>Rectification</strong> : modifiez votre profil et vos projets à tout moment.</li>
        <li><strong>Opposition</strong> : désactivez les recommandations personnalisées depuis votre profil.</li>
        <li><strong>Effacement</strong> : supprimez votre compte depuis votre profil ou l'application.</li>
    </ul>
    <p>Pour toute question : {{ config('idee.company.email') ?: 'adresse de contact à compléter avant l\'ouverture' }}.
        Vous pouvez aussi saisir la CDP (www.cdp.sn).</p>
</x-legal-page>
