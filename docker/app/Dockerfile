# ---- Stage 1: builder ----
FROM php:8.3-fpm AS builder

# Instala dependências do sistema e extensões PHP necessárias
RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev zip libpng-dev libonig-dev libpq-dev libxml2-dev \
    libjpeg-dev libfreetype6-dev libicu-dev libxslt1-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo pdo_mysql mbstring zip opcache gd intl xsl bcmath sockets \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensão Redis
RUN pecl install redis \
    && docker-php-ext-enable redis

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
    storage/app/public public/storage \
    && chown -R www-data:www-data storage public/storage

COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-scripts

COPY . .
RUN composer run-script post-autoload-dump

ARG APP_ENV=local
RUN if [ "$APP_ENV" != "local" ]; then \
      php artisan config:cache && \
      php artisan route:cache && \
      php artisan view:cache; \
    fi

RUN php artisan storage:link || true

# ---- Stage 2: final ----
FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    libzip-dev zip libpng-dev libonig-dev libpq-dev libxml2-dev \
    libjpeg-dev libfreetype6-dev libicu-dev libxslt1-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo pdo_mysql mbstring zip opcache gd intl xsl bcmath sockets \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

COPY --from=builder /var/www /var/www
RUN chown -R www-data:www-data storage public/storage

EXPOSE 9000

CMD ["php-fpm"]

COPY docker/app/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["entrypoint.sh"]
