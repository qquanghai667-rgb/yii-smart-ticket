FROM php:8.1-fpm

# extension for Yii2 and MySQL
RUN apt-get update && apt-get install -y \
    libpng-dev zip git unzip libpq-dev \
    && docker-php-ext-install pdo pdo_mysql bcmath

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Apache/Nginx
WORKDIR /var/www/html