FROM php:7.4-apache

# gd and zip for OpenCart
RUN set -eux; \
    printf 'deb http://snapshot.debian.org/archive/debian/20260825T000000Z bullseye main\ndeb http://snapshot.debian.org/archive/debian-security/20260825T000000Z bullseye-security main\n' > /etc/apt/sources.list; \
    echo 'Acquire::Check-Valid-Until "false";' > /etc/apt/apt.conf.d/99snapshot; \
    apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install zip \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN echo "memory_limit = 256M" > /usr/local/etc/php/conf.d/memory-limit.ini

# composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN a2enmod rewrite