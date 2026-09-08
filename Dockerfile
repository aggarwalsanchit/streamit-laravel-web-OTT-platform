# Use PHP 8.2 with Apache
FROM php:8.2-apache

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

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Copy .env.example to .env if not exists
RUN if [ ! -f .env ]; then cp .env.example .env; fi

# 🔧 FIX: Remove Telescope references
RUN if ! grep -q "laravel/telescope" composer.json; then \
        sed -i '/TelescopeServiceProvider/d' config/app.php 2>/dev/null || true; \
        rm -f app/Providers/TelescopeServiceProvider.php 2>/dev/null || true; \
    fi

# 🔧 FIX: Rename files to match class names
RUN find . -type f -name "Import*.php" -exec grep -l "class Import" {} \; | while read file; do \
        CLASS_NAME=$(grep -o "class [A-Za-z]*" "$file" | head -1 | cut -d' ' -f2); \
        DIR=$(dirname "$file"); \
        if [ ! -z "$CLASS_NAME" ] && [ ! -f "$DIR/$CLASS_NAME.php" ]; then \
            mv "$file" "$DIR/$CLASS_NAME.php" 2>/dev/null || true; \
        fi; \
    done

# 🔧 FIX: Rename Resource files
RUN find Modules/Entertainment/Transformers -type f -name "*Resource.php" -exec grep -l "class.*Resource" {} \; | while read file; do \
        CLASS_NAME=$(grep -o "class [A-Za-z]*" "$file" | head -1 | cut -d' ' -f2); \
        DIR=$(dirname "$file"); \
        if [ ! -z "$CLASS_NAME" ] && [ ! -f "$DIR/$CLASS_NAME.php" ]; then \
            mv "$file" "$DIR/$CLASS_NAME.php" 2>/dev/null || true; \
        fi; \
    done

# 🔧 Update composer.json autoload
RUN composer dump-autoload --no-interaction || true

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=php || \
    composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=php --no-scripts

# Generate application key
RUN php artisan key:generate --force || true

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Configure DocumentRoot
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configure PHP
RUN echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/memory.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/timeout.ini

EXPOSE 80
CMD ["apache2-foreground"]
