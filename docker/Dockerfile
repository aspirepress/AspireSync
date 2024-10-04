FROM php:8.3-cli-alpine AS basebuild

COPY ./build /opt/assetgrabber
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN apk update && apk add subversion postgresql-dev $PHP_DEPS \
    && docker-php-ext-install pdo pdo_pgsql \
    && mkdir -p /opt/assetgrabber/data \
    && addgroup -g 1000 -S assetgrabber \
    && adduser -u 1000 -S assetgrabber -G assetgrabber && \
    chown -R assetgrabber:assetgrabber /opt/assetgrabber

WORKDIR /opt/assetgrabber

USER assetgrabber

FROM basebuild AS prodbuild

RUN composer install --no-dev --no-interaction --no-progress --working-dir=/opt/assetgrabber
