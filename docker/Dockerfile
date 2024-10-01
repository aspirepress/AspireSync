FROM php:8.3-cli-alpine AS basebuild

COPY ./code /opt/assetgrabber
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN apk update && apk upgrade \
    && apk add subversion \
    && mkdir -p /opt/assetgrabber/data \
    && composer install --no-dev --no-interaction --no-progress --working-dir=/opt/assetgrabber

WORKDIR /opt/assetgrabber

FROM basebuild AS prodbuild

ENTRYPOINT ["php", "/opt/assetgrabber/assetgrabber"]
