FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
        nginx \
        supervisor \
        curl \
        git \
        unzip \
        oniguruma-dev \
        postgresql-dev \
        icu-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install pdo_pgsql pcntl opcache \
    && docker-php-ext-enable opcache \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apk del $PHPIZE_DEPS

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-progress

COPY . .

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
    && php artisan package:discover --ansi \
    && php artisan storage:link \
    && chown -R www-data:www-data storage bootstrap/cache

COPY deploy/dev/nginx.conf /etc/nginx/nginx.conf
COPY deploy/dev/supervisord.conf /etc/supervisord.conf
COPY deploy/dev/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
