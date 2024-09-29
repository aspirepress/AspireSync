FROM php:8.3-cli-alpine

RUN apk update && apk upgrade \
    && apk add subversion

COPY ./code /opt/plugin-slurp
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /opt/plugin-slurp

ENTRYPOINT ["php", "/opt/plugin-slurp/update"]