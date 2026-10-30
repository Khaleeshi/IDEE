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

**Prochaines étapes** : interface web mobile (site installable), envoi des codes
par WhatsApp et SMS Orange, assistant IA pour le business plan, parcours guidés,
application Flutter.

## Structure du dépôt

| Dossier | Contenu |
| --- | --- |
| `backend/` | Serveur Laravel 13 (PHP 8.3+) : API, base de données, règles de gestion, tests |
| `.github/workflows/` | Tests lancés automatiquement par GitHub à chaque envoi de code |

## Installer et lancer le serveur en local

Prérequis : PHP 8.3 ou plus récent, Composer et l'extension SQLite.

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

L'API répond alors sur `http://127.0.0.1:8000/api`. En développement, les codes
de connexion ne sont pas envoyés par SMS : ils sont écrits dans
`backend/storage/logs/laravel.log`.

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
