#!/usr/bin/env bash
# Première installation d'IDEE sur le serveur, à lancer UNE SEULE FOIS
# depuis le dossier de l'application, après le premier envoi du code :
#
#   bash deploy/premiere-installation.sh /chemin/vers/php
#
# Le chemin de PHP 8.3 sous Webuzo ressemble à /usr/local/apps/php83/bin/php.
set -euo pipefail

PHP="${1:-php}"
cd "$(dirname "$0")/.."

"$PHP" -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);' || {
    echo "PHP 8.3 ou plus récent est nécessaire. Indiquez son chemin : bash deploy/premiere-installation.sh /usr/local/apps/php83/bin/php"
    exit 1
}

mkdir -p storage/app/public storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

if [ ! -f .env ]; then
    cp .env.production.example .env
    echo "Le fichier .env a été créé. Remplissez-le (adresse du site, base MySQL), puis relancez ce script."
    exit 0
fi

if grep -q "CHANGEZ_MOI" .env; then
    echo "Le fichier .env contient encore des CHANGEZ_MOI : complétez-le, puis relancez ce script."
    exit 1
fi

if ! grep -q "^APP_KEY=base64:" .env; then
    "$PHP" artisan key:generate --force
fi

"$PHP" artisan migrate --force
"$PHP" artisan db:seed --force
"$PHP" artisan optimize

echo
echo "Installation terminée."
echo "Ajoutez cette tâche planifiée (Webuzo → Cron Jobs), à exécuter chaque minute :"
echo "  * * * * * cd $(pwd) && $PHP artisan schedule:run >> /dev/null 2>&1"
