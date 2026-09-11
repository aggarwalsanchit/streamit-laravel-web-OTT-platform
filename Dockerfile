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

# 🔧 Install dependencies with network resilience
# - COMPOSER_PROCESS_TIMEOUT: longer timeout for slow GitHub API
# - COMPOSER_IPRESOLVE=4: force IPv4 to avoid IPv6 routing issues
# - --prefer-dist: use zip archives instead of git clones
RUN COMPOSER_PROCESS_TIMEOUT=2000 \
    COMPOSER_IPRESOLVE=4 \
    composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --prefer-dist

# Create .env if missing
RUN if [ ! -f .env ]; then cp .env.example .env 2>/dev/null || echo "APP_ENV=production" > .env; fi

# Generate key
RUN php artisan key:generate --force || true

# Remove any cached config (critical)
RUN rm -f bootstrap/cache/*.php || true

# 🔧 Create required storage folders and "installed" flag
RUN mkdir -p /var/www/html/storage/framework/{cache,sessions,views,testing} \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/app/public \
    && echo "installed" > /var/www/html/storage/installed

# 🔧 Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache \
    && chmod 644 /var/www/html/storage/installed

# Enable error reporting (visible in Render logs)
RUN echo "display_errors = On" >> /usr/local/etc/php/conf.d/errors.ini \
    && echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/errors.ini \
    && echo "error_reporting = E_ALL & ~E_DEPRECATED" >> /usr/local/etc/php/conf.d/errors.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/errors.ini \
    && echo "error_log = /dev/stderr" >> /usr/local/etc/php/conf.d/errors.ini

# Bind to Render's PORT
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} server-entry.php"]
