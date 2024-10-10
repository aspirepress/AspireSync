FROM php:8.3-cli-alpine AS basebuild

ENV PATH="/opt/assetgrabber/bin:$PATH"

COPY ./build /opt/assetgrabber
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN apk add subversion postgresql-dev $PHP_DEPS;
RUN docker-php-ext-install pdo pdo_pgsql \
    && mkdir -p /opt/assetgrabber/data \
    && addgroup -g 1000 -S assetgrabber \
    && adduser -u 1000 -S assetgrabber -G assetgrabber && \
    chown -R assetgrabber:assetgrabber /opt/assetgrabber

WORKDIR /opt/assetgrabber


FROM basebuild AS devbuild

RUN apk add linux-headers autoconf make $PHP_DEPS && pecl install xdebug && docker-php-ext-enable xdebug

COPY ./docker/php.ini /usr/local/etc/php/php.ini

USER assetgrabber

FROM basebuild AS prodbuild

RUN composer install --no-dev --no-interaction --no-progress --working-dir=/opt/assetgrabber

USER assetgrabber
