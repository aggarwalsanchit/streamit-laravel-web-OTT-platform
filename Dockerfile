FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libzip-dev \
    libfreetype6-dev libjpeg62-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql mbstring exif pcntl bcmath zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

RUN if [ ! -f .env ]; then cp .env.example .env 2>/dev/null || echo "APP_ENV=production" > .env; fi
RUN php artisan key:generate --force || true
RUN rm -f bootstrap/cache/*.php || true

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

RUN echo "display_errors = On" >> /usr/local/etc/php/conf.d/errors.ini \
    && echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/errors.ini \
    && echo "error_reporting = E_ALL & ~E_DEPRECATED" >> /usr/local/etc/php/conf.d/errors.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/errors.ini \
    && echo "error_log = /dev/stderr" >> /usr/local/etc/php/conf.d/errors.ini

EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "server-entry.php"]
