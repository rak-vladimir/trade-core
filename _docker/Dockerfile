FROM php:8.5-fpm-alpine

ARG UID=1000
ARG GID=1000

# Runtime dependencies
RUN apk add --no-cache \
    freetype \
    icu-libs \
    libjpeg-turbo \
    libpng \
    libpq \
    libwebp \
    libzip \
    zlib

# Build dependencies and additional PHP extensions
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    linux-headers \
    freetype-dev \
    icu-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libpq-dev \
    libwebp-dev \
    libzip-dev \
    zlib-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        pcntl \
        pdo_mysql \
        pdo_pgsql \
        sockets \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /tmp/pear /var/cache/apk/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

ENV HOME=/home/app \
    COMPOSER_HOME=/home/app/.composer

# Application user
RUN addgroup -g "${GID}" -S app \
    && adduser -u "${UID}" -S -D -H -G app app \
    && mkdir -p "${COMPOSER_HOME}" \
    && chown -R app:app "${HOME}"

WORKDIR /var/www/html

# PHP configuration
COPY _docker/php/local.ini \
    /usr/local/etc/php/conf.d/99-local.ini

# PHP-FPM pool configuration
COPY _docker/php/www.conf \
    /usr/local/etc/php-fpm.d/www.conf

EXPOSE 9000

CMD ["php-fpm", "-F"]
