# the different stages of this Dockerfile are meant to be built into separate images## https://docs.docker.com/compose/compose-file/#target

ARG PHP_VERSION=8.0
ARG NODE_VERSION=14
ARG NGINX_VERSION=1.16

FROM php:${PHP_VERSION}-fpm-alpine AS sylius_php

# persistent / runtime deps
RUN apk add --no-cache \
		acl \
		file \
		gettext \
		git \
		mariadb-client \
	;

ARG APCU_VERSION=5.1.17
RUN set -eux
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    coreutils \
    freetype-dev \
    icu-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libtool \
    libwebp-dev \
    libzip-dev \
    mariadb-dev \
    zlib-dev \
  ;

RUN docker-php-ext-configure gd --with-jpeg=/usr/include/ --with-webp=/usr/include --with-freetype=/usr/include/
RUN docker-php-ext-install -j$(nproc) \
    exif \
    gd \
    intl \
    pdo_mysql \
    zip \
  ;

RUN pecl install \
    apcu-${APCU_VERSION} \
  ;

RUN pecl clear-cache
RUN docker-php-ext-enable \
    apcu \
    opcache \
  ;

RUN runDeps="$( \
    scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
      | tr ',' '\n' \
      | sort -u \
      | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
  )"; \
  apk add --no-cache --virtual .sylius-phpexts-rundeps $runDeps

RUN apk del .build-deps

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/php-cli.ini /usr/local/etc/php/php-cli.ini

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN set -eux
RUN composer clear-cache
ENV PATH="${PATH}:/root/.composer/vendor/bin"

COPY ./ /srv/sylius

WORKDIR /srv/sylius/

RUN set -eux
RUN composer install --prefer-dist --no-interaction
RUN composer clear-cache
RUN composer dump-autoload  --classmap-authoritative
RUN mkdir -p tests/Application/var/cache tests/Applicationvar/log
RUN chmod +x tests/Application/bin/console
RUN sync
RUN tests/Application/bin/console sylius:install:assets
RUN tests/Application/bin/console sylius:theme:assets:install

VOLUME /srv/sylius/tests/Application/var

VOLUME /srv/sylius/tests/Application/public/media

COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]

FROM node:${NODE_VERSION}-alpine3.14 AS sylius_nodejs

RUN set -eux

RUN apk add --no-cache --virtual .build-deps \
		g++ \
		gcc \
		git \
		make \
		python2 \
	;

WORKDIR /srv/sylius/tests/Application

# prevent the reinstallation of vendors at every changes in the source code
COPY --from=sylius_php /srv/sylius /srv/sylius

RUN set -eux
RUN yarn install
RUN yarn cache clean

RUN set -eux
RUN yarn build

COPY docker/nodejs/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["yarn", "watch"]

FROM nginx:${NGINX_VERSION}-alpine AS sylius_nginx

COPY docker/nginx/conf.d/default.conf /etc/nginx/conf.d/

WORKDIR /srv/sylius/tests/Application
