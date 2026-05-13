FROM php:8.3-fpm

# System dependencies
RUN apt-get update && apt-get install -y \
    git curl unzip \
    libicu-dev \
    libzip-dev zip \
    libgd-dev libpng-dev libjpeg-dev libwebp-dev \
    libsodium-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions required by Sylius
RUN docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install \
        intl \
        pdo_mysql \
        opcache \
        zip \
        gd \
        exif \
        sodium \
        bcmath

# Xdebug (dev only — disable in production by removing this block)
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
COPY php.ini /usr/local/etc/php/conf.d/app.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Node.js (needed for Sylius assets)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
