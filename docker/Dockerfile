FROM php:8.3-cli-alpine AS basebuild

ENV PATH="/opt/aspiresync/bin:$PATH"

COPY ./build /opt/aspiresync
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN apk add subversion postgresql-dev $PHP_DEPS;
RUN docker-php-ext-install pdo pdo_pgsql \
    && mkdir -p /opt/aspiresync/data \
    && addgroup -g 1000 -S aspiresync \
    && adduser -u 1000 -S aspiresync -G aspiresync && \
    chown -R aspiresync:aspiresync /opt/aspiresync

WORKDIR /opt/aspiresync


FROM basebuild AS devbuild

RUN apk add linux-headers autoconf make $PHP_DEPS && pecl install xdebug && docker-php-ext-enable xdebug

COPY ./docker/php.ini /usr/local/etc/php/php.ini

USER aspiresync

FROM basebuild AS prodbuild

RUN composer install --no-dev --no-interaction --no-progress --working-dir=/opt/aspiresync

USER aspiresync
