FROM php:8.4.27-fpm-alpine3.23@sha256:9eb263ac0fa59dfbef3dbaeebeb5f2319e212fc28c168a8c416050f77705b5b4

# System deps
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    zip \
    unzip \
    git \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    sqlite-dev \
    && docker-php-ext-install pdo_mysql pdo_sqlite mbstring zip opcache pcntl

# Install composer
COPY --from=composer:2@sha256:b6dcd72ca98fa19d0e5b8476de179859704891fad06dd9e0b96833d4c430a5fc /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy app files
COPY . .

# Install PHP dependencies
RUN php docker/php-runtime-guard.php
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/framework/testing storage/app/private storage/app/public storage/logs bootstrap/cache
RUN composer install --no-dev --optimize-autoloader --no-interaction

# File permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Nginx config
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Supervisor config
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# PHP opcache tuning
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php-security.ini /usr/local/etc/php/conf.d/zz-uss-security.ini

COPY docker/php-entrypoint.sh /usr/local/bin/uss-entrypoint
COPY docker/php-runtime-guard.php /usr/local/bin/php-runtime-guard.php
RUN chmod 755 /usr/local/bin/uss-entrypoint
ENTRYPOINT ["/usr/local/bin/uss-entrypoint"]

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
