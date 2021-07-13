# Copyright (c) 2019 Andreas Goetz <cpuidle@gmx.de>

FROM composer:2 AS builder

WORKDIR /vz

COPY composer.json /vz

RUN composer install --ignore-platform-reqs --no-ansi --no-scripts --no-dev --no-interaction --no-progress --optimize-autoloader

COPY . /vz


FROM php:8.0-alpine

EXPOSE 8080
EXPOSE 8082
EXPOSE 5582

RUN apk add postgresql-dev
RUN docker-php-ext-install pcntl pdo_mysql pdo_pgsql mysqli

COPY --from=builder /vz /vz
COPY --from=builder /vz/etc/config.dist.yaml /vz/etc/config.yaml

# modify options.js
RUN sed -i "s/url: 'api'/url: '',/" /vz/htdocs/js/options.js

CMD /vz/vendor/bin/ppm start -c /vz/etc/middleware.json --static-directory /vz/htdocs --cgi-path=/usr/local/bin/php
