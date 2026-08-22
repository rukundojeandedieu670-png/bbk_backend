FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
        nginx \
        bash \
        libpng-dev \
        libzip-dev \
        oniguruma-dev \
        icu-dev \
        postgresql-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring zip exif pcntl bcmath gd intl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 10000

CMD ["/bin/sh", "-c", "php artisan migrate:fresh --seed --force && exec /start.sh"]