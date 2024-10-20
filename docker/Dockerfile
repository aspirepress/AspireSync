FROM php:8.3-cli-alpine AS basebuild

ENV PATH="/opt/aspiresync/bin:$PATH"

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/download/2.5.2/install-php-extensions /usr/local/bin/

RUN apk add bash git subversion postgresql-client postgresql-dev

RUN install-php-extensions pdo pdo_pgsql

RUN mkdir -p /opt/aspiresync/data \
    && addgroup -g 1000 -S aspiresync \
    && adduser -u 1000 -S aspiresync -G aspiresync && \
    chown -R aspiresync:aspiresync /opt/aspiresync

WORKDIR /opt/aspiresync

################
FROM basebuild AS devbuild

RUN apk add sudo

RUN install-php-extensions xdebug

COPY ./docker/php.ini /usr/local/etc/php/php.ini

USER aspiresync

################
FROM basebuild AS prodbuild

RUN composer install --no-dev --no-interaction --no-progress --working-dir=/opt/aspiresync

USER aspiresync

