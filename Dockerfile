FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install mbstring \
    && docker-php-ext-install exif \
    && docker-php-ext-install pcntl \
    && docker-php-ext-install bcmath \
    && docker-php-ext-install zip \
    && docker-php-ext-install opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# 🔧 Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 🔧 Create .env file with proper variables
RUN cp .env.example .env 2>/dev/null || echo "APP_ENV=production" > .env

# 🔧 Generate app key
RUN php artisan key:generate --force || echo "Key generation failed"

# 🔧 Run migrations
RUN php artisan migrate --force || echo "Migration failed"

# 🔧 Create storage link
RUN php artisan storage:link || echo "Storage link failed"

# 🔧 Clear caches
RUN php artisan config:clear || echo "Config clear failed"
RUN php artisan cache:clear || echo "Cache clear failed"
RUN php artisan view:clear || echo "View clear failed"

# 🔧 Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# 🔧 Enable FULL error reporting
RUN echo "display_errors = On" >> /usr/local/etc/php/conf.d/errors.ini && \
    echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/errors.ini && \
    echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/errors.ini && \
    echo "log_errors = On" >> /usr/local/etc/php/conf.d/errors.ini && \
    echo "error_log = /var/www/html/storage/logs/php-error.log" >> /usr/local/etc/php/conf.d/errors.ini

EXPOSE 8080

# 🔧 Start server with error display
CMD ["sh", "-c", "php -S 0.0.0.0:8080 -t public 2>&1"]
