<?php

/*
|--------------------------------------------------------------------------
| Contenu des parcours guidés (cahier des charges, section 5)
|--------------------------------------------------------------------------
|
| Cinq situations de départ, un parcours chacune. Chaque étape porte sa
| fiche pratique : organisme, pièces, coût et délai indicatifs, source et
| date de consultation (PG-06, RG-10). Les montants viennent de pages lues
| pendant la recherche ; plusieurs sources se contredisent, d'où la mention
| « indicatif » et l'avis « à valider avec un expert » quand c'est utile.
|
| Clés d'action (lien vers une page d'IDEE) : publier-projet, mes-projets,
| demandes, forme-juridique. « related » renvoie vers un autre parcours.
|
| Pour corriger une fiche : modifier ce fichier, mettre à jour « checked_on »,
| puis lancer « php artisan db:seed --class=JourneySeeder » (fait à chaque
| mise en ligne).
|
*/

$consulted = '2026-10-29';

$servicePublicSarl = [
    'source_label' => 'servicepublic.gouv.sn',
    'source_url' => 'http://www.servicepublic.gouv.sn/index.php/demarche_administrative/demarche/1/584',
    'checked_on' => $consulted,
];

$rccmNinea = [
    'slug' => 'recevoir-rccm-ninea',
    'title' => 'Recevoir le registre du commerce (RCCM) et le NINEA',
    'body' => "Le guichet remet le numéro d'inscription au registre du commerce et du crédit mobilier (RCCM), "
        ."le numéro d'identification national des entreprises et associations (NINEA) et la déclaration "
        ."d'établissement. Gardez-en plusieurs copies : ils sont demandés à chaque démarche.",
    'organisation' => 'APIX',
    'cost' => 'Inclus dans le dépôt',
    'source_label' => 'Senformation (guide privé)',
    'source_url' => 'https://www.senformation.com/dossiers/se-mettre-a-son-compte-apres-une-formation/obtenir-son-ninea-et-son-rccm',
    'checked_on' => $consulted,
];

$merchantCard = [
    'slug' => 'carte-de-commercant',
    'title' => "Obtenir la carte de commerçant ou la carte d'artisan",
    'body' => "La carte de commerçant est délivrée après l'inscription au registre du commerce. Si votre métier "
        ."relève de l'artisanat, demandez plutôt la carte d'artisan à la chambre des métiers de votre région.",
    'organisation' => 'Ministère du Commerce, ou chambre des métiers',
    'cost' => '15 500 FCFA pour la carte de commerçant',
    'source_label' => 'Ministère du Commerce',
    'source_url' => 'https://industriecommerce.gouv.sn/services-usagers/carte-commercant/',
    'checked_on' => $consulted,
];

$firstHire = [
    'slug' => 'premiere-embauche',
    'title' => 'À la première embauche : CSS et IPRES',
    'body' => "Dès votre premier salarié, affiliez l'entreprise à la Caisse de sécurité sociale (CSS) et à "
        ."l'IPRES, puis déclarez chaque nouvel embauché dans les 8 jours.",
    'documents' => [
        "Avis d'immatriculation au NINEA (copie)",
        "Certificat d'inscription au RCCM",
        "Demande d'adhésion signée (original)",
    ],
    'organisation' => 'CSS, IPRES',
    'cost' => 'Cotisations sociales sur les salaires',
    'delay' => 'Déclaration sous 8 jours',
    'source_label' => "IPRES, guide de l'employeur",
    'source_url' => 'https://secusociale.ipres.sn/guide-de-lemployeur/',
    'checked_on' => $consulted,
];

return [

    [
        'slug' => 'valider-mon-idee',
        'situation' => "J'ai une idée",
        'situation_hint' => "Rien n'est encore lancé",
        'title' => 'Valider et structurer',
        'summary' => 'Avant de dépenser, vérifiez que votre idée répond à un vrai besoin, puis mettez-la en forme pour la présenter.',
        'steps' => [
            [
                'slug' => 'decrire-le-probleme',
                'title' => 'Décrire le problème et le client visé',
                'body' => 'Écrivez en deux phrases le problème que vous résolvez, et pour qui. Plus le client est précis '
                    ."(« les vendeuses de poisson du marché de Thiaroye » plutôt que « tout le monde »), plus l'idée est facile à vérifier.",
                'cost' => 'Gratuit',
                'delay' => 'Une soirée',
            ],
            [
                'slug' => 'interroger-des-clients',
                'title' => 'Interroger une dizaine de clients potentiels',
                'body' => "Allez à leur rencontre et demandez-leur comment ils font aujourd'hui, ce que cela leur coûte et ce "
                    ."qu'ils paieraient pour mieux. Notez leurs mots exacts : ils serviront dans votre fiche projet.",
                'cost' => 'Gratuit',
                'delay' => '1 à 2 semaines',
            ],
            [
                'slug' => 'etudier-la-concurrence',
                'title' => 'Regarder la concurrence et fixer un prix',
                'body' => 'Listez ceux qui répondent déjà à ce besoin, même de façon informelle, avec leurs prix et ce qui vous '
                    .'distingue. Votre prix doit couvrir vos coûts et rester acceptable pour les clients interrogés.',
            ],
            [
                'slug' => 'estimer-le-budget',
                'title' => 'Estimer le budget de démarrage',
                'body' => "Listez ce qu'il faut pour vendre les premiers produits : matériel, stock, local, démarches, "
                    .'communication. Séparez ce que vous avez déjà de ce qui manque : ce qui manque deviendra vos appels à action.',
                'documents' => ['Un devis ou un prix relevé pour chaque dépense'],
            ],
            [
                'slug' => 'rediger-le-business-plan',
                'title' => 'Rédiger le business plan',
                'body' => 'Résumez le problème, la solution, les clients, la concurrence, le budget et les besoins. '
                    ."L'assistant IA d'IDEE vous aidera bientôt à le rédiger ; en attendant, la fiche projet en reprend l'essentiel.",
            ],
            [
                'slug' => 'publier-le-projet',
                'title' => 'Publier le projet et ses appels à action',
                'body' => 'Publiez votre fiche projet sur IDEE, puis ajoutez précisément ce qui vous manque : un associé, une '
                    .'compétence, du matériel, un financement. Chaque demande reçue appelle une réponse.',
                'action' => 'publier-projet',
            ],
        ],
    ],

    [
        'slug' => 'formaliser-simplement',
        'situation' => 'Je vends déjà',
        'situation_hint' => 'Activité informelle',
        'title' => 'Formaliser simplement',
        'summary' => "Sortir de l'informel sans complications : le statut de l'entreprenant ou l'entreprise individuelle, puis la carte de commerçant.",
        'steps' => [
            [
                'slug' => 'choisir-le-statut',
                'title' => "Choisir entre l'entreprenant et l'entreprise individuelle",
                'body' => "Le statut de l'entreprenant, prévu par le droit OHADA, permet de se formaliser par une simple "
                    ."déclaration, sans immatriculation au registre du commerce. L'entreprise individuelle demande une "
                    .'immatriculation, mais ni capital ni statuts. Dans les deux cas, vos biens personnels ne sont pas '
                    ."séparés de ceux de l'activité.",
                'cost' => 'Gratuit',
                'delay' => 'Quelques minutes',
                'action' => 'forme-juridique',
                'source_label' => 'Emploidakar (guide privé)',
                'source_url' => 'https://www.emploidakar.com/creer-une-entreprise-individuelle-au-senegal/',
                'checked_on' => $consulted,
            ],
            [
                'slug' => 'reunir-les-pieces',
                'title' => 'Réunir les pièces',
                'body' => 'Préparez ces documents avant de vous déplacer : un dossier complet évite un second voyage.',
                'documents' => [
                    "Pièce d'identité (carte nationale d'identité ou passeport)",
                    "Extrait de casier judiciaire de moins de 3 mois, ou déclaration sur l'honneur",
                ],
                ...$servicePublicSarl,
            ],
            [
                'slug' => 'deposer-au-guichet-unique',
                'title' => "Déposer le dossier au guichet unique de l'APIX",
                'body' => "Pour une entreprise individuelle, le dépôt se fait au bureau d'appui à la création d'entreprise "
                    ."(BCE) de l'APIX, sur place ou sur creationdentreprise.sn. Un seul dépôt suffit. L'entreprenant "
                    ."fait une simple déclaration d'activité : demandez au guichet la marche à suivre.",
                'organisation' => 'APIX',
                'cost' => 'Environ 10 000 FCFA de greffe, plus 10 000 FCFA pour un nom commercial',
                'delay' => '24 à 48 heures',
                'source_label' => 'Senformation (guide privé)',
                'source_url' => 'https://www.senformation.com/dossiers/se-mettre-a-son-compte-apres-une-formation/obtenir-son-ninea-et-son-rccm',
                'checked_on' => $consulted,
            ],
            $rccmNinea,
            $merchantCard,
            [
                'slug' => 'regime-fiscal',
                'title' => 'Connaître son régime fiscal',
                'body' => "Une entreprise individuelle dont le chiffre d'affaires ne dépasse pas 50 millions FCFA relève en "
                    .'principe de la contribution globale unique (CGU), qui regroupe plusieurs impôts en un seul. Les lois '
                    .'de finances récentes ont pu changer ces règles : vérifiez auprès du centre des impôts.',
                'organisation' => 'Impôts (DGID)',
                'needs_expert' => true,
                'source_label' => 'FAQ de l\'APIX',
                'source_url' => 'https://investinsenegal.sn/apix-s-a/faq/',
                'checked_on' => $consulted,
            ],
            $firstHire,
            [
                'slug' => 'faire-connaitre-l-activite',
                'title' => "Faire connaître l'activité sur IDEE",
                'body' => 'Une activité formalisée inspire confiance : publiez-la sur IDEE avec ce qui lui manque, '
                    .'par exemple des clients, un fournisseur ou un financement.',
                'action' => 'publier-projet',
            ],
        ],
    ],

    [
        'slug' => 'creer-ma-societe',
        'situation' => 'Je crée une société',
        'situation_hint' => 'Seul ou avec des associés',
        'title' => "Créer et s'organiser",
        'summary' => "SARL, SUARL, SAS ou SASU : choisir la forme, passer chez le notaire, déposer à l'APIX, puis s'organiser pour embaucher.",
        'steps' => [
            [
                'slug' => 'choisir-la-forme',
                'title' => 'Choisir la forme juridique',
                'body' => 'Avec des associés, on choisit le plus souvent une SARL ou une SAS ; seul, leur version à associé '
                    ."unique, la SUARL ou la SASU. Ces sociétés séparent vos biens personnels de ceux de l'entreprise. "
                    ."Le capital minimum d'une SARL a été abaissé à 100 000 FCFA par une loi de 2014, mais certaines pages "
                    ."officielles indiquent encore d'autres montants : confirmez-le avant de déposer.",
                'cost' => 'Gratuit',
                'delay' => 'Quelques minutes',
                'action' => 'forme-juridique',
                'needs_expert' => true,
                'source_label' => 'senegalservices.sn',
                'source_url' => 'https://senegalservices.sn/actualite/baisse-du-capital-dans-la-creation-dune-sarl-de-1000000-a-100000-fcfa-lassemblee-nationale-v',
                'checked_on' => $consulted,
            ],
            [
                'slug' => 'reunir-les-pieces',
                'title' => 'Réunir les pièces',
                'body' => 'Les statuts fixent le nom, l\'objet, le capital et la répartition des parts entre associés. '
                    .'Préparez-les avec le notaire.',
                'documents' => [
                    "Copie certifiée de la pièce d'identité de chaque associé",
                    "Extrait de casier judiciaire de moins de 3 mois, ou déclaration sur l'honneur",
                    'Statuts de la société et procès-verbal de constitution',
                ],
                ...$servicePublicSarl,
            ],
            [
                'slug' => 'passer-chez-le-notaire',
                'title' => 'Passer chez le notaire',
                'body' => 'Le notaire est obligatoire pour une SARL, une SAS et leurs versions à associé unique. Si un associé '
                    .'apporte un bien d\'une valeur supérieure à 5 millions FCFA, un commissaire aux apports doit l\'évaluer.',
                'organisation' => 'Notaire',
                'cost' => 'Honoraires non publiés : demandez un devis',
                ...$servicePublicSarl,
            ],
            [
                'slug' => 'deposer-au-guichet-unique',
                'title' => "Déposer le dossier au guichet unique de l'APIX",
                'body' => "Le dossier est déposé au bureau d'appui à la création d'entreprise (BCE) de l'APIX, sur place ou "
                    .'sur creationdentreprise.sn. Prévoyez aussi la publication d\'un avis de création dans un journal '
                    .'d\'annonces légales.',
                'documents' => ['Statuts : une copie simple et une copie authentique'],
                'organisation' => 'APIX',
                'cost' => "SARL : 20 000 FCFA jusqu'à 500 000 FCFA de capital, 70 000 FCFA de 500 000 à 5 millions ; annonce légale : 40 000 FCFA",
                'delay' => '24 à 48 heures après les actes du notaire',
                ...$servicePublicSarl,
            ],
            $rccmNinea,
            $merchantCard,
            [
                'slug' => 'regime-fiscal',
                'title' => 'Faire le point sur la fiscalité',
                'body' => 'Rencontrez le centre des impôts dont dépend la société pour connaître son régime fiscal, '
                    .'les déclarations à faire et leurs dates.',
                'organisation' => 'Impôts (DGID)',
                'needs_expert' => true,
            ],
            $firstHire,
            [
                'slug' => 'publier-le-projet',
                'title' => 'Présenter la société sur IDEE',
                'body' => 'Publiez votre projet avec ses appels à action : associés, compétences, premiers clients ou financement.',
                'action' => 'publier-projet',
            ],
        ],
    ],

    [
        'slug' => 'trouver-un-financement',
        'situation' => 'Je cherche des fonds',
        'situation_hint' => 'Ou un associé, un partenaire',
        'title' => 'Se préparer et se faire connaître',
        'summary' => "Un financeur regarde d'abord si le dossier est prêt. Préparez-le, repérez les guichets adaptés, puis publiez précisément ce que vous cherchez.",
        'steps' => [
            [
                'slug' => 'mesurer-sa-preparation',
                'title' => 'Mesurer sa préparation',
                'body' => 'Le score de préparation de votre projet indique les informations et les pièces qui manquent. '
                    .'Complétez-les avant de solliciter un financeur.',
                'action' => 'mes-projets',
            ],
            [
                'slug' => 'completer-le-business-plan',
                'title' => 'Compléter le business plan',
                'body' => "Les financeurs demandent un business plan à leur format. L'assistant IA d'IDEE vous aidera bientôt "
                    .'à le rédiger ; préparez déjà vos chiffres : ventes prévues, coûts, besoin de financement.',
            ],
            [
                'slug' => 'reperer-les-guichets',
                'title' => 'Repérer les guichets compatibles',
                'body' => 'Chaque guichet a ses critères. Par exemple, la DER/FJ prête à 5 %, ou à 8 % en cofinancement avec '
                    .'une banque. Les démarches se font directement auprès des organismes officiels : méfiez-vous de '
                    .'quiconque demande de l\'argent pour « faciliter » un dossier.',
                'organisation' => 'DER/FJ, FONGIP, banques',
                'action' => 'opportunites',
                'source_label' => 'DER/FJ',
                'source_url' => 'https://www.der.sn/devenez-entrepreneur/types-de-financements/',
                'checked_on' => $consulted,
            ],
            [
                'slug' => 'garantie-fongip',
                'title' => "Vérifier l'accès à une garantie",
                'body' => "Le FONGIP peut garantir jusqu'à 80 % d'un crédit bancaire. Il demande au moins 15 % de fonds "
                    ."propres et un an d'activité.",
                'organisation' => 'FONGIP',
                'source_label' => 'FONGIP',
                'source_url' => 'https://www.fongip.sn/pages/nos-solutions/produits/garantie-individuelle/',
                'checked_on' => $consulted,
            ],
            [
                'slug' => 'publier-ses-appels-a-action',
                'title' => 'Publier ses appels à action',
                'body' => "Indiquez le montant recherché, le profil d'associé ou la compétence, l'échéance et la contrepartie. "
                    .'Les montants restent indicatifs : aucun argent ne transite par IDEE.',
                'action' => 'mes-projets',
            ],
            [
                'slug' => 'repondre-aux-demandes',
                'title' => 'Répondre à chaque demande de contact',
                'body' => 'Chaque demande reçue attend une réponse : intéressé, pas maintenant ou refus motivé. '
                    .'Votre réactivité est affichée sur votre profil.',
                'action' => 'demandes',
            ],
            [
                'slug' => 'suivre-ses-candidatures',
                'title' => 'Suivre ses candidatures',
                'body' => "Pour chaque dossier déposé auprès d'un guichet, notez la date dans cette étape et réglez un rappel "
                    .'pour relancer au bon moment.',
            ],
        ],
    ],

    [
        'slug' => 'exporter',
        'situation' => 'Je veux exporter',
        'situation_hint' => 'Produit et pays visés',
        'title' => 'Exporter pas à pas',
        'summary' => "Les étapes pour vendre hors du Sénégal, avec l'exemple d'un produit alimentaire vers l'Union européenne. Les exigences dépendent du produit et du pays.",
        'steps' => [
            [
                'slug' => 'entreprise-formalisee',
                'title' => 'Avoir une entreprise formalisée',
                'body' => 'Il faut une entreprise inscrite au registre du commerce, avec un NINEA, et la carte de commerçant.',
                'organisation' => 'APIX, ministère du Commerce',
                'related' => 'formaliser-simplement',
            ],
            [
                'slug' => 'carte-import-export',
                'title' => 'Obtenir la carte import-export',
                'body' => 'Elle est valable 4 ans. Déposer le dossier directement à la Direction du commerce intérieur, à '
                    .'Dakar, réduit le délai à environ 2 jours, contre environ 1 mois par une chambre régionale.',
                'documents' => [
                    'Photocopie du NINEA',
                    'Copie certifiée de la carte de commerçant',
                    "Pièce d'identité ou passeport certifié",
                    "Une photo d'identité",
                    'Quittance COSEC',
                    'Timbre fiscal',
                ],
                'organisation' => 'Direction du commerce intérieur, chambre de commerce',
                'cost' => '41 500 FCFA',
                'delay' => 'Environ 2 jours à Dakar, environ 1 mois en région',
                'source_label' => 'Ministère du Commerce',
                'source_url' => 'https://industriecommerce.gouv.sn/carte-import-export/',
                'checked_on' => $consulted,
            ],
            [
                'slug' => 'code-ppm',
                'title' => 'Obtenir le code douane (code PPM)',
                'body' => "Le code « personne physique et morale » (PPM) identifie l'exportateur auprès de la douane.",
                'organisation' => 'Douanes',
                'cost' => 'Non trouvé : demandez aux douanes',
                'source_label' => 'eRegulations Sénégal',
                'source_url' => 'https://senegal.eregulations.org/procedure/print/120/72/step/0?showRecourses=true&showCertification=false&l=fr',
                'checked_on' => $consulted,
            ],
            [
                'slug' => 'enregistrement-rex',
                'title' => "S'enregistrer comme exportateur pour l'UE (REX)",
                'body' => "Vers l'Union européenne, le Sénégal exporte sans droits de douane grâce à l'initiative « Tout sauf "
                    .'les armes ». L\'origine se prouve par une attestation sur la facture ; le numéro REX est obligatoire '
                    .'quand la valeur des produits d\'un envoi dépasse 6 000 €.',
                'organisation' => 'ASEPEX, ou en ligne',
                'source_label' => 'ASEPEX',
                'source_url' => 'https://www.asepex.sn/senegal-export/',
                'checked_on' => $consulted,
            ],
            [
                'slug' => 'normes-du-produit',
                'title' => 'Mettre le produit aux normes du pays visé',
                'body' => "Exemple de la mangue séchée vers l'UE : limites de résidus de pesticides et de mycotoxines, "
                    .'additifs autorisés seulement ; en bio, pas de sulfites. Faites analyser votre produit par un laboratoire.',
                'organisation' => 'Laboratoire, organisme certificateur',
                'cost' => 'Selon les analyses',
                'needs_expert' => true,
                'source_label' => 'CBI (2021)',
                'source_url' => 'https://www.cbi.eu/sites/default/files/market_information/researches/2021%20Dried-Mango%20FR_0.pdf',
                'checked_on' => $consulted,
            ],
            [
                'slug' => 'controle-phytosanitaire',
                'title' => 'Faire le contrôle phytosanitaire, si exigé',
                'body' => 'Les produits végétaux peuvent nécessiter un certificat phytosanitaire, délivré après inspection. '
                    .'Pour un produit transformé comme un fruit séché, vérifiez avec la DPV et votre acheteur si c\'est exigé.',
                'organisation' => 'Direction de la protection des végétaux (DPV)',
                'cost' => 'Non confirmé',
                'needs_expert' => true,
                'source_label' => 'eRegulations',
                'source_url' => 'https://corridor.eregulations.org/procedure/118/71/step/249?l=fr',
                'checked_on' => $consulted,
            ],
            [
                'slug' => 'declaration-en-douane',
                'title' => "Déclarer l'export en douane",
                'body' => 'La déclaration se fait dans le système GAINDE, le plus souvent par un commissionnaire agréé, '
                    .'jusqu\'au bon à embarquer.',
                'documents' => [
                    'Facture commerciale',
                    'Liste de colisage',
                    'Connaissement ou lettre de transport aérien',
                    "Attestation d'assurance",
                    'Certificats propres au produit, selon le cas',
                ],
                'organisation' => 'Douanes, commissionnaire agréé',
                'cost' => 'Honoraires du commissionnaire',
                'source_label' => 'Ministère du Commerce, guide export (2026)',
                'source_url' => 'https://industriecommerce.gouv.sn/wp-content/uploads/2026/06/PROCEDURES-GENERALES-COMMERCE-EXTERIEUR-EXPORTATION.pdf',
                'checked_on' => $consulted,
            ],
            [
                'slug' => 'trouver-des-acheteurs',
                'title' => 'Trouver des acheteurs',
                'body' => 'Publiez votre projet d\'export sur IDEE et ajoutez un appel à action « Clients » qui précise le '
                    .'produit, les volumes et le pays visé.',
                'action' => 'publier-projet',
            ],
        ],
    ],

];
