# Serveur IDEE en Rust

Ce dossier contient le futur serveur d'IDEE, écrit en Rust avec axum. Il remplacera `backend/` (Laravel) route par route, sur la même base MySQL et avec les mêmes jetons de connexion : l'application Flutter ne change pas. Le plan complet est dans le cahier des charges, sections 13, 16 et 17.

## Où on en est : étape 1, le socle

| Route | Rôle | État |
| --- | --- | --- |
| `GET /sante` | État du serveur Rust | Fait |
| `POST /api/auth/code` | Envoi d'un code par WhatsApp ou par e-mail | Fait, canal `log` seulement |
| `POST /api/auth/verify` | Vérification du code, création du compte, jeton | Fait |
| `POST /api/auth/logout` | Déconnexion de l'appareil | Fait |
| Toutes les autres | Restent chez Laravel | Relayées en local, Nginx en production |

Les deux serveurs se comprennent : un code émis par l'un se vérifie chez l'autre, et un jeton remis par l'un est accepté par l'autre.

Avant de basculer `POST /api/auth/code` en production, il reste à écrire l'envoi par WhatsApp, SMS Orange, Twilio et e-mail, ainsi que le SMS payé d'avance (CP-07). D'ici là, ces canaux répondent 503. La réponse de connexion n'a pas encore les champs `profiles` et `weekly_contact_quota` du profil : ils arriveront avec la route `/api/me`.

## Prérequis

- Rust stable, installé avec [rustup](https://rustup.rs).
- Sous Windows : les outils de compilation C++ de Microsoft (Visual Studio Build Tools, charge « Développement Desktop en C++ »).
- Un serveur MySQL avec la base de Laravel. En local, celui de WAMP convient, en InnoDB : WAMP crée les tables en MyISAM par défaut, qui n'a ni transactions ni clés étrangères.

## Mise en place locale

1. Copier `.env.example` en `.env`.
2. Créer la base `idee` dans MySQL, puis y lancer les migrations de Laravel (dans `backend/`) :

```bash
DB_CONNECTION=mysql DB_DATABASE=idee DB_USERNAME=root DB_PASSWORD= php artisan migrate --seed
```

3. Lancer Laravel sur le port 8000 avec la même base, pour le relais :

```bash
DB_CONNECTION=mysql DB_DATABASE=idee DB_USERNAME=root DB_PASSWORD= php artisan serve --port=8000
```

4. Lancer le serveur Rust (ici, dans `serveur/`) :

```bash
cargo run
```

Le serveur écoute sur http://127.0.0.1:8080. Les codes de connexion s'affichent dans son journal. Pour essayer l'application Flutter contre lui : `flutter run --dart-define=API_URL=http://10.0.2.2:8080` (émulateur Android).

## Commandes avant chaque envoi

```bash
cargo fmt
```

```bash
cargo clippy --all-targets -- -D warnings
```

```bash
cargo test
```

Les tests de bout en bout créent chacun une base neuve sur le serveur MySQL de `DATABASE_URL` (l'utilisateur doit pouvoir créer des bases), à partir de `tests/schema/0001_schema_laravel.sql`.

## Schéma des tests

`tests/schema/0001_schema_laravel.sql` est le schéma de la base après les migrations de Laravel. Après une nouvelle migration Laravel, le régénérer depuis la base locale à jour (dans `serveur/`, avec le `mysqldump` de WAMP) :

```bash
{ printf -- "-- Schéma exporté de MySQL après les migrations de Laravel.\nSET FOREIGN_KEY_CHECKS=0;\n\n"; mysqldump -u root --no-data --compact --skip-add-drop-table --ignore-table=idee.migrations idee | sed -E 's/ AUTO_INCREMENT=[0-9]+//'; printf "\nSET FOREIGN_KEY_CHECKS=1;\n"; } > tests/schema/0001_schema_laravel.sql
```

## Configuration

Le serveur lit les mêmes variables que Laravel, dans l'environnement ou dans `.env`.

| Variable | Rôle | Par défaut |
| --- | --- | --- |
| `IDEE_SERVEUR_ADRESSE` | Adresse d'écoute | `127.0.0.1:8080` |
| `APP_ENV` | Environnement : local, staging, production | `local` |
| `APP_URL` | Adresse publique, pour les liens des photos | `http://localhost` |
| `DATABASE_URL` | Base MySQL (`mysql://…`) ; sinon `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | `mysql://root@127.0.0.1:3306/idee` |
| `IDEE_OTP_TTL`, `IDEE_OTP_MAX_ATTEMPTS` | Validité d'un code (minutes), essais permis | `10`, `5` |
| `IDEE_OTP_DRIVER`, `MAIL_MAILER` | Canal d'envoi des codes (seul `log` est pris en charge) | `log` |
| `IDEE_SESSION_IDLE_DAYS`, `IDEE_SESSION_MAX_DAYS` | Déconnexion après inactivité, durée maximale d'une session | `90`, `365` |
| `BCRYPT_ROUNDS` | Coût du hachage des codes | `12` |
| `SANCTUM_TOKEN_PREFIX` | Préfixe des jetons | vide |
| `IDEE_LARAVEL_URL` | En local : adresse de Laravel, où relayer les routes pas encore réécrites | vide |
| `RUST_LOG` | Niveau des journaux | `info` |

## Organisation

| Chemin | Contenu |
| --- | --- |
| `src/main.rs` | Démarrage : configuration, base, journaux, écoute, arrêt propre |
| `src/lib.rs` | Assemblage des routes, relais des routes inconnues |
| `src/config.rs` | Lecture de la configuration |
| `src/etat.rs` | État partagé : configuration, base, envoi des codes, limites, relais |
| `src/erreurs.rs` | Erreurs renvoyées en JSON, au format de Laravel |
| `src/limites.rs` | Limites de tentatives (section 4) |
| `src/relais.rs` | Relais de développement vers Laravel |
| `src/api/` | Routes JSON : connexion, validation, membre connecté, profil |
| `src/domaine/` | Règles de gestion : codes, jetons, membres, téléphone, pays, envoi |
| `src/site/` | Pages HTML (étape 5) |
| `src/taches/` | Tâches planifiées et file des envois |
| `tests/` | Tests de bout en bout, sur une base MySQL neuve à chaque test |
