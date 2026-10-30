#!/usr/bin/env bash
# Lancé automatiquement par GitHub après chaque envoi du code sur le serveur.
set -euo pipefail

PHP="${1:-php}"
cd "$(dirname "$0")/.."

if [ ! -f .env ]; then
    echo "Première mise en ligne : le code est envoyé. Terminez l'installation sur le serveur :"
    echo "  bash deploy/premiere-installation.sh $PHP"
    exit 0
fi

"$PHP" artisan down --retry=15 || true
"$PHP" artisan migrate --force
"$PHP" artisan db:seed --class=SectorSeeder --force
"$PHP" artisan db:seed --class=JourneySeeder --force
"$PHP" artisan optimize
"$PHP" artisan up

echo "IDEE est à jour."
