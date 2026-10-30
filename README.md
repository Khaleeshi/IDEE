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

**Prochaines étapes** : assistant IA pour le business plan, parcours guidés de
création d'entreprise et d'export, messagerie intégrée, application Flutter.

## Structure du dépôt

| Dossier | Contenu |
| --- | --- |
| `backend/` | Serveur Laravel 13 (PHP 8.3+) : API, site web, base de données, règles de gestion, tests |
| `backend/resources/views/` | Pages du site web (Blade et Tailwind CSS) |
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
| `/profil` | Mon profil, mes rôles, mes préférences |
| `/membres/{id}` | Profil public d'un membre et sa réactivité |

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
| RG-11 | Rôle d'investisseur ou d'expert affiché seulement après vérification d'identité | — |

Les relances et expirations tournent toutes les heures, la mise en sommeil
chaque nuit, via le planificateur Laravel (`php artisan schedule:work` en local,
une tâche cron en production).
