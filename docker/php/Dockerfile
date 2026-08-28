FROM php:8.4-fpm

WORKDIR /var/www/html

#Instalamos dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libpq-dev

#Extension de PostgreSQL para php
RUN docker-php-ext-install pdo pdo_pgsql

#copiar Composer desde una imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
