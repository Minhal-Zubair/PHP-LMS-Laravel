FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
        nginx \
        supervisor \
        gettext-base \
        libzip-dev \
        unzip \
        git \
    && docker-php-ext-install mysqli pdo_mysql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# --- Plain PHP site (repo root) ---
COPY . /var/www/html
RUN rm -rf /var/www/html/settings-app /var/www/html/PHP-LMS-Laravel /var/www/html/docker

# --- Laravel app (settings-app) ---
COPY settings-app /var/www/laravel
RUN composer install --working-dir=/var/www/laravel --no-dev --optimize-autoloader --no-interaction \
    && chown -R www-data:www-data /var/www/laravel/storage /var/www/laravel/bootstrap/cache \
    && chmod -R 775 /var/www/laravel/storage /var/www/laravel/bootstrap/cache

COPY docker/nginx.conf.template /etc/nginx/templates/default.conf.template
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && rm -f /etc/nginx/sites-enabled/default

ENV PORT=10000
EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf", "-n"]
