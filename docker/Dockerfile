FROM php:8.3-cli-alpine AS basebuild

RUN apk assetgrabber && apk upgrade \
    && apk add subversion

COPY ./code /opt/plugin-slurp
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /opt/plugin-slurp

FROM basebuild AS prodbuild

ENTRYPOINT ["php", "/opt/plugin-slurp/update"]