FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev libpq-dev librabbitmq-dev libicu-dev \
    && docker-php-ext-install pdo pdo_mysql zip intl \
    && pecl install amqp \
    && docker-php-ext-enable amqp \
    && apt-get clean

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Aligne l'UID/GID de www-data sur celui de l'hôte WSL (1000)
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

WORKDIR /var/www/html

COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-scripts --no-autoloader --ignore-platform-req=ext-amqp

COPY backend/ .
RUN composer dump-autoload --optimize

# Donne les droits à www-data sur les dossiers Symfony
RUN chown -R www-data:www-data var/
