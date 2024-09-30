FROM php:8.3-cli-alpine AS basebuild

RUN apk update && apk upgrade \
    && apk add subversion

COPY ./code /opt/plugin-slurp
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer install --no-dev --no-interaction --no-progress --working-dir=/opt/plugin-slurp

WORKDIR /opt/plugin-slurp

FROM basebuild AS prodbuild

ENTRYPOINT ["php", "/opt/plugin-slurp/assetgrabber"]
