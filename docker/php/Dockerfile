# Image PHP-FPM de l'application. Utilisée par compose.yaml (le code y est alors monté depuis l'hôte,
# pour le développement), ou seule pour la production : le code et les dépendances sont copiés dans
# l'image, sans les outils de développement.
#
#   docker build -f docker/php/Dockerfile -t mon-app .
FROM php:8.3-fpm-alpine

RUN apk add --no-cache postgresql-libs \
    && apk add --no-cache --virtual .build-deps postgresql-dev $PHPIZE_DEPS \
    && docker-php-ext-install pdo_mysql pdo_pgsql opcache \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/niangpro.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-niangpro.conf
COPY docker/php/entrypoint.sh /usr/local/bin/niangpro-entrypoint

WORKDIR /var/www/html

COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress \
    && mkdir -p storage/logs storage/framework storage/app \
    && chown -R www-data:www-data storage

ENTRYPOINT ["niangpro-entrypoint"]
CMD ["php-fpm"]
