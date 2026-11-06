FROM php:8.4-apache-bookworm AS php-base

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libicu-dev libonig-dev libsqlite3-dev libxml2-dev libcurl4-openssl-dev \
    poppler-utils supervisor \
    && docker-php-ext-install -j"$(nproc)" bcmath curl dom intl mbstring pdo_sqlite simplexml xml xmlwriter zip pcntl opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html

FROM php-base AS dependencies
COPY . .
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader \
    && composer check-platform-reqs --no-dev

FROM node:24-bookworm-slim AS assets
WORKDIR /app
COPY --from=dependencies /var/www/html /app
RUN npm ci && npm run build

FROM php-base AS runtime
COPY --from=dependencies /var/www/html /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build
COPY render-apache.conf /etc/apache2/sites-available/000-default.conf
COPY render-supervisord.conf /etc/supervisor/conf.d/application.conf
RUN chmod +x render-start.sh \
    && printf 'memory_limit=128M\nupload_max_filesize=3M\npost_max_size=4M\nexpose_php=Off\n' > /usr/local/etc/php/conf.d/application.ini
EXPOSE 10000
CMD ["/var/www/html/render-start.sh"]
