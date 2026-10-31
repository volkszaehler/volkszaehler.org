# Copyright (c) 2019 Andreas Goetz <cpuidle@gmx.de>

ARG PHP_IMAGE_TAG=8.5-alpine

FROM php:$PHP_IMAGE_TAG AS builder

WORKDIR /vz

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json /vz

RUN docker-php-ext-install pcntl \
    && composer install --no-ansi --no-scripts --no-dev --no-interaction --no-progress --optimize-autoloader

COPY . /vz


FROM php:$PHP_IMAGE_TAG

EXPOSE 8080
EXPOSE 8082
EXPOSE 5582

# production ini keeps deprecation notices off stdout, they break php-pm's headers on php 8.4+
RUN apk add --no-cache postgresql-libs postgresql-dev \
    && docker-php-ext-install pcntl pdo_mysql pdo_pgsql mysqli \
    && apk del postgresql-dev \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --from=builder /vz /vz
COPY --from=builder /vz/etc/config.dist.yaml /vz/etc/config.yaml

# modify options.js
RUN sed -i "s/url: 'api'/url: '',/" /vz/htdocs/js/options.js

CMD /vz/vendor/bin/ppm start -c /vz/etc/middleware.json --static-directory /vz/htdocs --cgi-path=/usr/local/bin/php
