# FoundIt API — PHP 8.2 + Apache serving the Slim REST API.
FROM php:8.2-apache

# Composer runs as root inside the image.
ENV COMPOSER_ALLOW_SUPERUSER=1

# System libs + PHP extensions + tooling:
#   pdo_mysql -> connect to MySQL/MariaDB via PDO (App\Database)
#   mbstring  -> multibyte-safe strings (validation + PHPMailer)
#   unzip/git -> used by Composer to fetch packages
RUN apt-get update \
 && apt-get install -y --no-install-recommends libonig-dev unzip git \
 && docker-php-ext-install pdo_mysql mbstring \
 && a2enmod rewrite \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

# Composer binary from the official image.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache vhost: document root = public/, AllowOverride All so the .htaccess
# front controller (and Authorization passthrough) works.
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# Application code (vendor/ is excluded via .dockerignore and installed fresh below).
COPY . /var/www/html/

# Install PHP dependencies from the committed composer.lock. src/ is already
# present, so --optimize-autoloader builds a correct classmap.
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader --no-scripts

# Container-specific environment (DB_HOST=db, etc.). Overrides any copied .env.
COPY .env.docker /var/www/html/.env

# Apache (www-data) must be able to write uploaded item photos.
RUN mkdir -p /var/www/html/public/uploads \
 && chown -R www-data:www-data /var/www/html/public/uploads

EXPOSE 80
