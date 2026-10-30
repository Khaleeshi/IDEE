<?php

/*
|--------------------------------------------------------------------------
| Répertoire des programmes et financements (OP-01)
|--------------------------------------------------------------------------
|
| Contenu de départ, relevé pendant la recherche du cahier des charges.
| Chaque fiche cite sa source et sa date de consultation ; les critères
| issus de guides non officiels sont signalés « à confirmer ». L'équipe
| complète ensuite le répertoire depuis l'administration (AD-02) : une
| fiche modifiée là n'est plus écrasée à la mise en ligne.
|
*/

$consulted = '2026-10-29';

return [
    [
        'slug' => 'der-fj-financement',
        'title' => 'Financement des femmes et des jeunes',
        'organisation' => 'DER/FJ',
        'kind' => 'pret',
        'summary' => 'Prêts de la Délégation générale à l\'entrepreneuriat rapide des femmes et des jeunes, demandés en ligne sur financement.der.sn ou dans une antenne régionale. Le dépôt est gratuit : toute demande de commission est une arnaque.',
        'criteria' => 'Taux de 5 % (TEG), plafonné à 8 % pour les projets cofinancés avec une banque. Public : femmes et jeunes porteurs d\'une activité génératrice de revenus, résidant au Sénégal (critères d\'âge et pièces à confirmer auprès de la DER/FJ).',
        'deadline_note' => 'Campagnes d\'enrôlement annoncées par la DER/FJ',
        'url' => 'https://www.der.sn/devenez-entrepreneur/types-de-financements/',
        'source_label' => 'der.sn',
        'checked_on' => $consulted,
    ],
    [
        'slug' => 'fongip-garantie-individuelle',
        'title' => 'Garantie individuelle de crédit',
        'organisation' => 'FONGIP',
        'kind' => 'garantie',
        'summary' => 'Le Fonds de garantie des investissements prioritaires garantit une partie du crédit accordé par votre banque, ce qui facilite son accord.',
        'criteria' => 'Au moins 1 an d\'activité. Fonds propres d\'au moins 15 % du coût du projet (20 % pour une grande entreprise). Quotité garantie jusqu\'à 80 % du crédit, sur 60 mois au plus ; commission de garantie de 1 à 2 % par an.',
        'amount' => 'Jusqu\'à 500 millions FCFA par entreprise',
        'deadline_note' => 'Toute l\'année, en passant par votre banque',
        'url' => 'https://www.fongip.sn/pages/nos-solutions/produits/garantie-individuelle/',
        'source_label' => 'fongip.sn',
        'checked_on' => $consulted,
    ],
    [
        'slug' => 'fongip-garantie-portefeuille',
        'title' => 'Garantie de portefeuille pour les très petites entreprises',
        'organisation' => 'FONGIP',
        'kind' => 'garantie',
        'summary' => 'Garantie accordée aux banques et institutions de microfinance partenaires pour les petits crédits des très petites entreprises.',
        'criteria' => 'Besoin de financement de 20 millions FCFA au plus ; quotité garantie de 50 % au maximum.',
        'amount' => 'Jusqu\'à 25 millions FCFA par bénéficiaire',
        'deadline_note' => 'Toute l\'année, auprès des établissements partenaires',
        'url' => 'https://www.fongip.sn/pages/nos-solutions/produits/garantie-portefeuille/',
        'source_label' => 'fongip.sn',
        'checked_on' => $consulted,
    ],
    [
        'slug' => 'adepme-guf-pme',
        'title' => 'Guichet unique d\'accès au financement des PME (GUF-PME)',
        'organisation' => 'ADEPME',
        'kind' => 'accompagnement',
        'summary' => 'Plateforme de l\'ADEPME, soutenue par la BCEAO, qui relie les PME aux banques et aux structures d\'appui : création de compte, diagnostic, modèles de documents, sur le portail Sénégal PME.',
        'criteria' => 'PME et entrepreneurs installés au Sénégal. Une tournée d\'enrôlement passe dans les 14 régions de septembre à novembre 2026.',
        'deadline_note' => 'Inscription ouverte toute l\'année',
        'url' => 'https://www.senegalpme.sn/2026/08/31/guf-pme-le-dispositif-national-dacces-au-financement-se-deploie-dans-les-territoires/',
        'source_label' => 'senegalpme.sn',
        'checked_on' => $consulted,
    ],
    [
        'slug' => 'bnde-credit-jeune-entrepreneur',
        'title' => 'Crédit Jeune Entrepreneur',
        'organisation' => 'BNDE',
        'kind' => 'pret',
        'summary' => 'Prêt à taux bonifié de la Banque nationale pour le développement économique pour les projets innovants des jeunes, présenté sur le portail Sénégal PME.',
        'criteria' => 'Porteurs de 18 à 35 ans, projets innovants (conditions détaillées à confirmer auprès de la BNDE).',
        'amount' => 'De 5 à 100 millions FCFA',
        'deadline_note' => 'À confirmer auprès de la BNDE',
        'url' => 'https://www.senegalpme.sn/financement/',
        'source_label' => 'senegalpme.sn',
        'checked_on' => $consulted,
    ],
    [
        'slug' => 'bidc-bnde-ligne-pme',
        'title' => 'Ligne de crédit BIDC-BNDE pour les PME',
        'organisation' => 'BNDE, BIDC',
        'kind' => 'pret',
        'summary' => 'Nouvelle ligne de crédit de 20 milliards FCFA signée en mai 2026 entre la Banque d\'investissement et de développement de la CEDEAO et la BNDE, réservée au financement des PME.',
        'criteria' => 'PME sénégalaises ; conditions d\'accès à demander à la BNDE.',
        'deadline_note' => 'À confirmer auprès de la BNDE',
        'url' => 'https://www.financialafrik.com/2026/05/05/la-bidc-et-la-bnde-signent-une-nouvelle-ligne-de-credit-de-20-milliards-de-fcfa-pour-renforcer-le-financement-des-pme-au-senegal/',
        'source_label' => 'Financial Afrik (presse)',
        'checked_on' => $consulted,
    ],
    [
        'slug' => 'fonsis-kfw-fonds-locaux',
        'title' => 'Programme FONSIS-KfW pour les PME et l\'entrepreneuriat féminin',
        'organisation' => 'FONSIS, KfW',
        'kind' => 'investissement',
        'summary' => 'Programme d\'environ 8,5 milliards FCFA annoncé en octobre 2026, qui renforce les fonds d\'investissement locaux pour qu\'ils investissent dans des PME, des startups et des entreprises de femmes.',
        'criteria' => 'Accès par les fonds d\'investissement locaux partenaires (liste à confirmer).',
        'deadline_note' => 'Premiers décaissements annoncés fin 2026',
        'url' => 'https://lesoleil.sn/actualites/economie/85-milliards-fcfa-du-fonsis-et-de-la-kfw-pour-propulser-les-pme-et-lentrepreneuriat-feminin/',
        'source_label' => 'Le Soleil (presse)',
        'checked_on' => $consulted,
    ],
    [
        'slug' => 'teranga-capital',
        'title' => 'Fonds d\'investissement Teranga Capital',
        'organisation' => 'Teranga Capital',
        'kind' => 'investissement',
        'summary' => 'Fonds d\'investissement en capital pour les PME, soutenu par le FONSIS depuis 2025, qui dit regarder au-delà de Dakar.',
        'criteria' => 'Entreprises déjà en activité, avec un potentiel de croissance (critères à confirmer auprès du fonds).',
        'deadline_note' => 'Candidature spontanée',
        'url' => 'https://launchbaseafrica.com/2025/05/06/backed-by-senegals-sovereign-wealth-fund-teranga-capitals-new-fund-looks-beyond-dakar/',
        'source_label' => 'Launch Base Africa (presse)',
        'checked_on' => $consulted,
    ],
];
