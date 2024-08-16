ARG PHP_VERSION=8.2

FROM php:${PHP_VERSION}-cli-alpine

WORKDIR /app

RUN apk add --no-cache \
        tzdata \
        bash \
        curl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer