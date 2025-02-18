FROM dunglas/frankenphp:1.4.1-php8.4.3-bookworm AS base

COPY --from=composer:2.8.5 /usr/bin/composer /usr/bin/composer
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/download/2.7.14/install-php-extensions /usr/local/bin/

RUN apt update && apt install -y bash jq subversion zip

RUN install-php-extensions intl pdo pdo_pgsql pdo_sqlite zip

RUN useradd --create-home --shell /bin/bash app \
    && chown -R app:app /config /data \
    && apt update \
    && apt install -y postgresql-client

COPY ./docker/webapp/Caddyfile /etc/caddy/Caddyfile
COPY ./docker/webapp/php.ini /usr/local/etc/php/php.ini

WORKDIR /app

################
FROM base AS dev

RUN apt update && apt install -y git postgresql-client sqlite3

RUN install-php-extensions xdebug

USER app

################
FROM base AS prod

# do not use copy --chown, it never works right
COPY . /app
RUN chown -R app:app /app

USER app

# Fails: Class "Symfony\Bundle\MakerBundle\MakerBundle" not found
# RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader --working-dir=/app

# Just install dev deps too I guess ... sigh.
RUN composer install --no-interaction --no-progress --optimize-autoloader --working-dir=/app


