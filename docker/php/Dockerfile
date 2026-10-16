FROM php:8.4-fpm

WORKDIR /var/www/html

#Instalamos dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev

#Extension de PostgreSQL para php
RUN docker-php-ext-install pdo pdo_pgsql zip

#Extension GD para phpspreadsheet
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

#Limite de memoria PHP (PhpSpreadsheet requiere mas de 128M)
RUN echo 'memory_limit=512M' > /usr/local/etc/php/conf.d/zz-memory.ini

#copiar Composer desde una imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
