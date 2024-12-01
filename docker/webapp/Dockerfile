FROM dunglas/frankenphp:1.2.5-php8.3 AS base

COPY --from=composer:2.8.3 /usr/bin/composer /usr/bin/composer
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/download/2.5.2/install-php-extensions /usr/local/bin/

RUN apt update && apt install -y bash subversion zip

RUN install-php-extensions intl pdo pdo_pgsql pdo_sqlite zip

COPY ./docker/webapp/Caddyfile /etc/caddy/Caddyfile
COPY ./docker/webapp/php.ini /usr/local/etc/php/php.ini

WORKDIR /app

################
FROM base AS dev

RUN apt update && apt install -y git postgresql-client sqlite3

RUN install-php-extensions xdebug

################
FROM base AS prod

COPY . /app

RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader --working-dir=/app

