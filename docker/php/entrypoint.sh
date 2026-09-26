#!/bin/sh
# Prépare le projet au démarrage du conteneur, puis lance la commande (php-fpm par défaut).
# Idempotent : relancer le conteneur ne régénère ni la clé ni les dépendances.
set -e
cd /var/www/html

# Code monté depuis l'hôte sans vendor/ (premier lancement en développement).
if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --no-progress
fi

if [ ! -f .env ]; then
    cp .env.example .env
fi

# Une APP_KEY fournie par l'environnement (secret de l'hébergeur) prime ; sinon une clé est générée
# une seule fois dans .env, jamais remplacée ensuite (elle chiffre les cookies et signe les liens).
if [ -z "${APP_KEY:-}" ] && grep -Eq '^APP_KEY=$' .env; then
    php bin/niang key:generate
fi

mkdir -p storage/logs storage/framework storage/app
# Code monté depuis l'hôte : ses fichiers appartiennent à l'utilisateur de l'hôte, pas à www-data.
chmod -R a+rwX storage

if [ "${NIANGPRO_MIGRATE:-true}" = "true" ]; then
    php bin/niang migrate
fi

exec "$@"
