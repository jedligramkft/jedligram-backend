# Base image
FROM php:8.5.3-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libldap2-dev \
    default-mysql-client \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu \
    && docker-php-ext-install pdo pdo_mysql mbstring xml zip ldap \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for better layer caching
COPY composer.json composer.lock* ./

# Install Laravel dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy the rest of the project files
COPY . .

# Re-run composer to generate autoload with all classes present
RUN composer dump-autoload --no-dev --optimize

# Set permissions for Laravel storage and cache directories
RUN php artisan storage:link
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Copy entrypoint and make executable
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x entrypoint.sh

EXPOSE 8000
ENTRYPOINT ["./entrypoint.sh"]
# CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]