FROM php:8.3-cli AS basebuild

ENV PATH="/opt/aspiresync/bin:$PATH"

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/download/2.5.2/install-php-extensions /usr/local/bin/

COPY --from=juicedata/mount:ce-v1.2.1 /usr/local/bin/juicefs /usr/local/bin/juicefs

RUN apt update && apt install -y bash git subversion postgresql-client

RUN install-php-extensions pdo pdo_pgsql

RUN mkdir -p /opt/aspiresync/data \
    && addgroup --gid 1000 aspiresync \
    && adduser --uid 1000 --gid 1000 aspiresync \
    && chown -R aspiresync:aspiresync /opt/aspiresync

WORKDIR /opt/aspiresync

################
FROM basebuild AS devbuild

RUN apt update \
    && apt install -y sudo \
    && usermod -a -G sudo aspiresync \
    && echo '%sudo ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers

RUN install-php-extensions xdebug

COPY ./docker/php.ini /usr/local/etc/php/php.ini

USER aspiresync

RUN git config --global --add safe.directory /opt/aspiresync

################
FROM basebuild AS prodbuild

RUN composer install --no-dev --no-interaction --no-progress --working-dir=/opt/aspiresync

USER aspiresync

