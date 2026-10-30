# IDEE

Réseau social de projets et d'actions pour les entrepreneurs du Sénégal.
Chaque projet publie ce qui lui manque (financement, associé, compétence,
clients, matériel) et chaque demande de contact reçoit une réponse.

## État d'avancement

**Étape 1 — cœur du réseau (API)** : terminée.

- Connexion par numéro de téléphone et code à usage unique, sans mot de passe
- Profils de membres avec rôles cumulables : porteur, contributeur, investisseur, expert
- Projets avec résumé public et détails privés, versions horodatées par empreinte SHA-256
- Score de préparation du projet et conseils pour l'améliorer
- Appels à action (besoins) et propositions « Je peux aider »
- Demandes de contact avec la règle « aucune demande sans réponse » : statuts,
  relance à 7 jours, expiration à 14 jours, indicateur de réactivité
- Recherche de projets par mot-clé, secteur, région, stade et type de besoin
- 40 tests automatisés

**Étape 2 — site web mobile** : terminée.

- Pages pensées pour le téléphone, avec une barre de navigation en bas de l'écran
- Fil des projets avec recherche et filtres, fiche projet, profil public des membres
- Connexion par code, publication et modification de projets, appels à action
- « Je peux aider », demandes de contact et réponses, compteur de demandes en attente
- Numéros partagés, avec un lien WhatsApp, seulement quand les deux parties sont intéressées
- Application installable sur l'écran d'accueil ; pages publiques consultables hors connexion
- Pages très légères : environ 10 Ko de HTML, 6 Ko de CSS et moins de 1 Ko de JavaScript compressés
- 63 tests automatisés au total

**Étape 3 — mise en ligne** : prête, à activer sur le serveur.

- Envoi des codes de connexion par WhatsApp, par SMS Orange, ou WhatsApp avec SMS en secours
- Mise en ligne automatique par GitHub à chaque fusion dans `main`, après les tests
- Scripts d'installation et de mise à jour du serveur, modèle de configuration de production
- Marche à suivre : [MISE-EN-LIGNE.md](MISE-EN-LIGNE.md)
- 69 tests automatisés au total

**Étape 4 — parcours guidés** : terminée.

- Cinq situations de départ, un parcours chacune : valider une idée, formaliser une
  activité informelle, créer une société, chercher un financement, exporter (38 étapes)
- Chaque étape : explication, pièces à réunir, organisme, coût et délai indicatifs,
  source et date de consultation ; mention « à valider avec un expert » quand les sources divergent
- Questionnaire « Quelle forme juridique choisir ? » : entreprenant, entreprise individuelle, SARL, SAS
- Suivi personnel étape par étape (à faire, en cours, en attente, terminée), note et rappel daté
- « Demander de l'aide pour cette étape » : un appel à action prérempli sur le projet du membre
- Parcours consultables sans compte et hors connexion
- 85 tests automatisés au total

**Étape 5 — le reste du cahier des charges (version 1)** : terminée.

- Fil d'activité (nouveaux projets, appels à action, mises à jour, réussites), journal
  de progression daté, partage sur WhatsApp avec aperçu, recherche par montant
- Suivre un projet, une personne ou un secteur ; « Je connais quelqu'un » ;
  recommandations du jour expliquées
- Messagerie après acceptation : texte, messages vocaux, documents, accusé de lecture
- Documents privés partagés sur autorisation, révocables, avec journal d'accès
- Signalements, modération avec recours devant un second modérateur, suspension et exclusion
- Vérification d'identité des investisseurs et experts, pièce effacée après examen
- Administration : indicateurs de la section 18, signalements, vérifications,
  fiches pratiques et opportunités
- Répertoire des programmes et financements, avec suivi de ses candidatures et rappels
- Centre de notifications et alertes WhatsApp (à activer)
- Publication d'un projet en cinq questions, photo de profil et disponibilité
- Assistant IA (Claude Opus 5.5) : questions pour clarifier l'idée, business plan
  modifiable et imprimable (à activer avec une clé d'API)
- Connexion par e-mail et confirmation des adresses ; équipe du projet sur sa fiche ;
  score de préparation aligné sur RG-06 (business plan et équipe compris)
- Notifications push du navigateur et SMS de secours pour les alertes critiques
- 136 tests automatisés au total

**Étape 6 — version 2** : en cours.

- Collaboration : prochaines étapes convenues à deux (objectif, échéance,
  responsable), tableau de tâches partagé du projet, rendez-vous en visio ou en
  présentiel avec rappel la veille, collaborations et réussites publiées seulement
  après confirmation des deux parties (CO-01 à CO-03, MR-07, FC-06)
- Alertes : recherches enregistrées sur les nouveaux projets (RR-04), programmes
  compatibles avec son projet (OP-02), rappels 7 jours puis 1 jour avant la date
  limite d'une candidature (OP-03), résumé de la semaine par e-mail (NO-04)
- Projets : parts envisagées dans l'équipe, privées et plafonnées à 100 % (PR-07) ;
  rapprochement de projets proches entre porteurs volontaires, conversation ouverte
  seulement avec l'accord des deux (PR-08, RG-12) ; accord de confidentialité accepté
  avant toute demande d'accès aux documents (CF-05)
- Organisations : pages gérées à plusieurs (CP-05), vérification par RCCM et NINEA
  avec justificatif effacé après examen (CF-03), programmes publiés par les
  organisations vérifiées dans le répertoire (OR-01)
- Annuaire des experts à l'identité vérifiée, avec services et tarif indicatif, et
  demande de contact à propos de son projet (SE-01)
- Groupes par secteur et par région : projets du groupe et discussion signalable,
  avec une limite de messages par jour (FC-05)
- Sessions de pitch en ligne ou en présentiel, organisées par l'équipe ou une
  organisation vérifiée : inscription, candidature avec un projet, sélection dans
  la limite des places, lien de visio réservé aux inscrits, rappel la veille (EV-01)
- Assistant IA : préparation d'un rendez-vous (questions, points à présenter,
  prochaines étapes), sans livrer les détails privés d'un projet à qui n'en est pas
  le porteur (IA-05) ; présentation du projet en neuf diapositives, modifiable et
  imprimable (IA-06)
- Fiches pratiques en wolof : texte et enregistrement audio saisis par l'équipe dans
  l'administration, bouton Français / Wolof sur les parcours (PG-07, contenu à
  produire avec des locuteurs natifs)
- Paiements : offres Pro et investisseur, mise en avant « Sponsorisé » signalée et
  sans effet sur le score ni les recommandations (RG-08), packs prépayés payés par
  Wave (API Checkout, statut toujours relu auprès de Wave) ou par Orange Money
  (transfert vérifié par l'équipe), reçus numérotés, factures des organisations,
  statistiques de visibilité pour l'offre Pro (PA-01 à PA-03)
- Saisie vocale en français : bouton « Dicter » sur les champs de texte, avec la
  reconnaissance vocale du navigateur, sans frais (IA-03)
- Candidatures structurées aux programmes des organisations vérifiées, envoyées
  depuis le dossier IDEE avec, si le porteur le veut, son business plan (OR-02) ;
  suivi des cohortes : score, besoins, activité et points de suivi (OR-03)
- 184 tests automatisés au total

**Reste à faire** : saisie vocale en wolof (IA-04, après des tests de qualité) ; traduction et enregistrement des fiches en wolof ; paiement par carte
(avec un agrégateur comme PayDunya ou PayTech) ; fonctions prévues en
V3 (rencontres ciblées, devis d'experts, candidatures structurées, suivi de
cohortes) ; application Flutter.

## Structure du dépôt

| Dossier | Contenu |
| --- | --- |
| `backend/` | Serveur Laravel 13 (PHP 8.3+) : API, site web, base de données, règles de gestion, tests |
| `backend/resources/views/` | Pages du site web (Blade et Tailwind CSS) |
| `backend/database/data/` | Contenu des parcours guidés : étapes, pièces, coûts, sources |
| `backend/deploy/` | Scripts d'installation et de mise à jour du serveur |
| `.github/workflows/` | Tests à chaque envoi de code, mise en ligne à chaque fusion dans `main` |

## Installer et lancer le serveur en local

Prérequis : PHP 8.3 ou plus récent, Composer, l'extension SQLite et Node.js 22.

```bash
cd backend
composer install
npm install
npm run build
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Le site s'ouvre alors sur `http://127.0.0.1:8000` et l'API répond sur
`http://127.0.0.1:8000/api`. Les données de démonstration créent quelques
membres et projets fictifs. En développement, les codes
de connexion ne sont pas envoyés par SMS : ils sont écrits dans
`backend/storage/logs/laravel.log`.

## Pages du site

| Adresse | Page |
| --- | --- |
| `/` | Fil des projets, recherche et filtres |
| `/connexion` | Connexion par numéro de téléphone et code |
| `/projets/{id}` | Fiche projet : besoins, « Je peux aider », contacter le porteur |
| `/projets/nouveau` | Publier un projet |
| `/mes-projets` | Mes projets et leur score de préparation |
| `/demandes` | Demandes de contact reçues et envoyées |
| `/demandes/{id}` | Conversation : messages, prochaines étapes, rendez-vous, collaboration |
| `/projets/{id}/taches` | Tableau de tâches partagé avec les partenaires du projet |
| `/alertes` | Mes alertes sur les nouveaux projets |
| `/projets/{id}/rapprochements` | Projets proches du mien et propositions de rapprochement |
| `/organisations` | Annuaire des organisations ; `/organisations/{id}` leur page, `/gestion` pour la gérer |
| `/experts` | Experts vérifiés et leurs tarifs ; `/membres/{id}/contacter` pour écrire à propos de son projet |
| `/groupes` | Groupes par secteur et par région ; `/groupes/{secteur ou region}/{id}` |
| `/evenements` | Sessions de pitch ; `/evenements/{id}` pour s'inscrire ou candidater |
| `/projets/{id}/presentation` | Présentation du projet rédigée par l'assistant, à corriger et projeter |
| `/candidatures/{id}` | Candidature à un programme : réponses, business plan joint, décision |
| `/organisations/{id}/cohortes` | Cohortes suivies par une organisation ; `/cohortes/{id}` leur tableau de suivi |
| `/offres` | Offres payantes, mes offres en cours et mes paiements ; `/paiements/{id}/recu` pour le reçu |
| `/opportunites?projet={id}` | Programmes compatibles avec l'un de mes projets |
| `/profil` | Mon profil, mes rôles, mes préférences |
| `/membres/{id}` | Profil public d'un membre et sa réactivité |
| `/parcours` | Parcours guidés : choix de la situation, mes démarches, rappels |
| `/parcours/{id}` | Les étapes d'un parcours et mon avancement |
| `/parcours/forme-juridique` | Questionnaire « Quelle forme juridique choisir ? » |
| `/?vue=suivis` | Activité de ce que je suis |
| `/opportunites` | Programmes et financements, et mes candidatures |
| `/projets/{id}/business-plan` | Business plan rédigé par l'assistant, à corriger et imprimer |
| `/notifications` | Mes notifications |
| `/administration` | Équipe IDEE : indicateurs, signalements, vérifications, fiches, opportunités |

## Routes de l'API

Toutes les réponses sont en JSON. Les routes marquées « connecté » attendent
l'en-tête `Authorization: Bearer <jeton>` reçu à la connexion.

| Méthode | Route | Accès | Rôle |
| --- | --- | --- | --- |
| POST | `/api/auth/code` | public | Envoyer un code de connexion au numéro donné |
| POST | `/api/auth/verify` | public | Vérifier le code, créer le compte si besoin, recevoir un jeton |
| POST | `/api/auth/logout` | connecté | Se déconnecter |
| GET | `/api/me` | connecté | Mon profil complet |
| PATCH | `/api/me` | connecté | Modifier mon profil et mes rôles |
| GET | `/api/me/projects` | connecté | Mes projets, y compris en sommeil |
| GET | `/api/members/{id}` | public | Profil public, projets et indicateur de réactivité |
| GET | `/api/sectors` | public | Secteurs d'activité |
| GET | `/api/projects` | public | Rechercher des projets actifs (`q`, `sector`, `region`, `stage`, `need_type`) |
| POST | `/api/projects` | connecté | Publier un projet |
| GET | `/api/projects/{id}` | public | Voir un projet (détails privés réservés au porteur) |
| PATCH | `/api/projects/{id}` | porteur | Modifier un projet |
| DELETE | `/api/projects/{id}` | porteur | Supprimer un projet |
| GET | `/api/projects/{id}/needs` | public | Besoins d'un projet |
| POST | `/api/projects/{id}/needs` | porteur | Publier un appel à action |
| POST | `/api/needs/{id}/close` | porteur | Clôturer un besoin (comblé ou abandonné) |
| GET | `/api/needs/{id}/help-offers` | porteur | Voir les propositions d'aide |
| POST | `/api/needs/{id}/help-offers` | connecté | Proposer son aide |
| POST | `/api/help-offers/{id}/respond` | porteur | Accepter ou décliner une proposition |
| GET | `/api/contact-requests` | connecté | Demandes reçues, ou envoyées avec `box=sent` |
| POST | `/api/contact-requests` | connecté | Envoyer une demande de contact |
| GET | `/api/contact-requests/{id}` | concerné | Lire une demande (la marque comme lue) |
| POST | `/api/contact-requests/{id}/respond` | destinataire | Répondre : intéressé, pas maintenant ou refusée avec motif |
| GET | `/api/journeys` | public | Parcours guidés |
| GET | `/api/journeys/{id}` | public | Étapes d'un parcours, avec mon avancement si je suis connecté |
| GET | `/api/legal-form` | public | Questionnaire de forme juridique ; avec `associes`, `protection` et `taille`, l'orientation |
| GET | `/api/me/journeys` | connecté | Mes parcours et le nombre d'étapes terminées |
| POST | `/api/journeys/{id}/start` | connecté | Commencer un parcours |
| DELETE | `/api/journeys/{id}/start` | connecté | Retirer un parcours de mes démarches |
| PATCH | `/api/journey-steps/{id}/progress` | connecté | Statut, note et date de rappel d'une étape |
| GET | `/api/feed` | public | Fil d'activité |
| GET | `/api/me/feed` | connecté | Fil de ce que je suis |
| GET | `/api/me/recommendations` | connecté | Recommandations du jour, avec leurs raisons |
| POST | `/api/follows/projects/{id}` · `members/{id}` · `sectors/{slug}` | connecté | Suivre ou ne plus suivre |
| GET, POST | `/api/projects/{id}/updates` | public, porteur | Journal de progression |
| GET, POST | `/api/contact-requests/{id}/messages` | concerné | Messagerie (texte, vocal, document) |
| GET | `/api/messages/{id}/attachment` | concerné | Pièce jointe d'un message |
| GET | `/api/contact-requests/{id}/collaboration` | concerné | Étapes convenues, rendez-vous et collaborations de la conversation |
| POST | `/api/contact-requests/{id}/agreements` · `meetings` · `collaborations` | concerné | Proposer une étape, un rendez-vous ; déclarer une collaboration |
| POST | `/api/agreements/{id}/respond` · `complete` | l'autre personne, concerné | Accepter ou refuser une étape ; la marquer réalisée |
| POST | `/api/meetings/{id}/respond` · `cancel` | l'autre personne, concerné | Confirmer, décliner ou annuler un rendez-vous |
| POST | `/api/collaborations/{id}/respond` | partenaire | Confirmer ou non une collaboration |
| GET | `/api/members/{id}/collaborations` | public | Collaborations confirmées d'un membre |
| GET, POST | `/api/projects/{id}/tasks` | porteur, partenaires | Tableau de tâches du projet |
| GET, POST | `/api/me/alerts` | connecté | Alertes enregistrées (mêmes filtres que la recherche de projets) |
| DELETE | `/api/alerts/{id}` | auteur | Supprimer une alerte |
| GET | `/api/projects/{id}/opportunities` | porteur | Opportunités compatibles avec le projet, et pourquoi |
| PATCH | `/api/team-members/{id}` | porteur | Rôle et part envisagée d'une personne de l'équipe |
| GET, PUT | `/api/projects/{id}/matches` · `matching` | porteur | Projets proches, propositions ; activer les rapprochements |
| POST | `/api/projects/{id}/matches` · `/api/project-matches/{id}/respond` | porteurs | Proposer un rapprochement, y répondre |
| GET | `/api/organisations` · `/api/organisations/{id}` | public | Annuaire et page d'une organisation, avec ses programmes |
| POST, PATCH | `/api/organisations` · `/api/organisations/{id}` | connecté, gestionnaire | Créer ou modifier une page d'organisation |
| POST, DELETE | `/api/organisations/{id}/members` · `/api/organisation-members/{id}` | gestionnaire | Ajouter ou retirer un membre |
| POST | `/api/organisations/{id}/verification` | gestionnaire | Demander la vérification (RCCM, NINEA, justificatif) |
| POST, PUT, DELETE | `/api/organisations/{id}/programmes` · `/api/programmes/{id}` | gestionnaire d'une organisation vérifiée | Publier, modifier ou retirer un programme |
| GET | `/api/experts` | public | Experts vérifiés (`q`, `sector`, `region`) |
| GET | `/api/groups` · `/api/groups/{secteur ou region}/{id}` | public | Groupes, leurs projets et leurs messages |
| POST, DELETE | `/api/groups/{type}/{id}/posts` · `/api/group-posts/{id}` | connecté, auteur | Écrire au groupe, supprimer son message |
| GET, POST | `/api/projects/{id}/pitch-deck` | porteur | Présentation du projet (IA-06) ; `PUT /api/pitch-decks/{id}` pour la corriger |
| POST | `/api/meetings/{id}/preparation` | concerné | Préparer un rendez-vous (IA-05) ; `GET /api/assistant-documents/{id}` pour la lire |
| GET, POST | `/api/opportunities/{id}/questions` · `/api/opportunities/{id}/applications` | public, porteur | Questions d'un programme ; candidater depuis IDEE |
| GET | `/api/me/programme-applications` | connecté | Mes candidatures et leurs décisions |
| GET, POST | `/api/programmes/{id}/applications` · `/api/programme-applications/{id}/decide` | gestionnaire | Candidatures reçues, décision |
| GET | `/api/organisations/{id}/cohorts` · `/api/cohorts/{id}` | gestionnaire | Cohortes et tableau de suivi |
| GET | `/api/offers` | public | Offres, prix et moyens de paiement disponibles |
| POST, GET | `/api/payments` · `/api/me/payments` · `/api/payments/{id}` | connecté | Acheter une offre (lien Wave en retour), suivre ses paiements |
| POST | `/api/payments/{id}/reference` | acheteur | Identifiant du transfert Orange Money |
| POST | `/api/webhooks/wave` | Wave | Avis de paiement, qui déclenche une relecture auprès de Wave |
| GET | `/api/projects/{id}/stats` | porteur avec l'offre Pro | Statistiques de visibilité du projet |
| GET | `/api/events` · `/api/events/{id}` | public | Sessions de pitch à venir (le lien de visio seulement pour les inscrits) |
| POST | `/api/events` | équipe, organisation vérifiée | Organiser une session |
| POST, DELETE | `/api/events/{id}/registration` | connecté | S'inscrire, ou candidater avec `project_id` ; se désinscrire |
| GET, POST | `/api/events/{id}/registrations` · `/api/event-registrations/{id}/decide` | organisateur | Candidatures et sélection |
| PATCH, DELETE | `/api/tasks/{id}` | porteur, partenaires | Changer la colonne d'une tâche, la retirer |
| GET, POST | `/api/projects/{id}/documents` | autorisé, porteur | Documents privés |
| GET | `/api/documents/{id}` | autorisé | Télécharger un document (consultation journalisée) |
| GET, POST | `/api/projects/{id}/document-accesses` | porteur, connecté | Demandes d'accès aux documents |
| POST | `/api/document-accesses/{id}/decide` · `revoke` | porteur | Accorder, refuser ou révoquer un accès |
| POST | `/api/reports` | connecté | Signaler un profil, un projet ou un message |
| GET | `/api/me/moderation` | connecté | Décisions de modération me concernant |
| POST | `/api/reports/{id}/appeal` | concerné | Contester une décision, une seule fois |
| GET, POST | `/api/me/verification` | connecté | Vérification d'identité |
| GET | `/api/opportunities` | public | Programmes et financements (`type`, `q`) |
| GET | `/api/me/applications` | connecté | Mes candidatures |
| PUT, DELETE | `/api/opportunities/{id}/application` | connecté | Suivre ou retirer une candidature |
| GET | `/api/me/notifications` | connecté | Mes notifications |
| POST | `/api/me/notifications/read` | connecté | Tout marquer comme lu |
| GET, POST | `/api/projects/{id}/clarification` | porteur | Questions de l'assistant IA |
| PUT | `/api/clarifications/{id}` | porteur | Répondre aux questions |
| GET, POST | `/api/projects/{id}/business-plan` | porteur | Business plan : lire, en rédiger une version |
| PUT | `/api/business-plans/{id}` | porteur | Corriger les sections |

## Règles de gestion appliquées

Les valeurs se règlent dans `backend/config/idee.php` ou dans le fichier `.env`.

| Règle | Comportement | Valeur par défaut |
| --- | --- | --- |
| RG-01 | Nombre maximum de besoins ouverts par projet | 10 |
| RG-02 | Une demande vise un projet ou un besoin, avec un message minimum | 100 caractères |
| RG-03 | Relance du destinataire, puis expiration de la demande | 7 jours, puis 14 jours |
| RG-04 | Demandes de contact par semaine | 5, ou 20 avec identité vérifiée |
| RG-06 | Score de préparation sur des critères publics, sans juger la rentabilité | sur 100 |
| RG-07 | Mise en sommeil d'un projet sans mise à jour | 90 jours |
| RG-09 | Montants indicatifs : aucun argent ne transite par la plateforme | — |
| RG-10 | Chaque fiche affiche sa source et sa date ; une fiche non revue est signalée à l'équipe | 6 mois |
| RG-05 | Documents privés visibles après accord du porteur ; accès journalisés et révocables | — |
| RG-11 | Rôle d'investisseur ou d'expert affiché seulement après vérification d'identité | — |
| Modération | Durée d'une suspension, délai pour contester une décision | 30 jours, 14 jours |
| Assistant IA | Business plans et présentations par projet et par mois ; questions par jour ; préparations de rendez-vous par membre et par jour | 3 ; 3 ; 3 |
| Rendez-vous | Rappel envoyé aux deux personnes avant un rendez-vous confirmé | 24 heures |
| RG-12 | Rapprochement proposé seulement entre porteurs volontaires, sans rien révéler de privé | désactivé par défaut |
| CF-03 | Badge « Vérifiée » après contrôle du RCCM et du NINEA ; justificatif effacé après la décision | — |
| CF-05 | Accord de confidentialité accepté avant toute demande d'accès aux documents | version 2026-10 |
| Groupes | Messages par membre et par jour dans les groupes | 10 |
| RG-08 | Mise en avant payante signalée « Sponsorisé », sans effet sur le score ni sur l'ordre des recommandations | 2 projets au plus en tête |
| Offres | Pro : 5 000 FCFA / 30 jours ; investisseur : 20 000 FCFA / 30 jours ; « Sponsorisé » : 5 000 FCFA / semaine | prix à tester pendant le pilote |
| Alertes | Alertes enregistrées par membre ; rappels avant une date limite | 10 ; 7 jours puis 1 jour |
| Résumé hebdomadaire | Envoyé le lundi aux adresses e-mail confirmées, s'il y a du nouveau | activé |
| Collaboration | Étapes, rendez-vous et collaborations ne comptent qu'avec l'accord des deux | — |

Les relances, expirations et rappels de rendez-vous tournent toutes les heures,
la mise en sommeil chaque nuit, les rappels d'étapes et de dates limites puis
les alertes chaque matin, le résumé par e-mail et le signalement des fiches à
revoir chaque lundi, via le planificateur Laravel (`php artisan schedule:work`
en local, une tâche cron en production).

Le contenu des parcours est dans `backend/database/data/parcours.php`. Après une
correction, mettez à jour la date `checked_on` de l'étape : le contenu est
rechargé à chaque mise en ligne (`php artisan db:seed --class=JourneySeeder`),
sans perdre l'avancement des membres.
