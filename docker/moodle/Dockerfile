FROM php:8.3-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    libldap2-dev \
    libonig-dev \
    ghostscript \
    git \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pgsql \
        pdo_pgsql \
        gd \
        zip \
        intl \
        soap \
        opcache \
        exif \
        mbstring \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY php.ini /usr/local/etc/php/conf.d/moodle.ini
RUN printf '%s\n' '#!/bin/sh' 'set -e' 'mkdir -p /var/moodledata' 'chown -R www-data:www-data /var/moodledata 2>/dev/null || true' 'exec "$@"' > /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

WORKDIR /var/www/moodle

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
