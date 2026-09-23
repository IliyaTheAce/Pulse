FROM php:8.4-fpm
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    $PHPIZE_DEPS\
    && docker-php-ext-install \
        pdo_pgsql \
        mbstring \
        bcmath \
        exif \
        pcntl \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

RUN usermod -u 1000 www-data

CMD ["php-fpm"]
