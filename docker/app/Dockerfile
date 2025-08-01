# ---- Stage 1: build ----
FROM php:8.3-fpm AS builder

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev zip libpng-dev libonig-dev curl \
    && docker-php-ext-install pdo pdo_mysql gd zip mbstring opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copia apenas composer.* para cache eficiente
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-scripts

# Copia todo o código do Laravel
COPY . .

# Executa scripts do Composer agora que artisan existe
RUN composer run-script post-autoload-dump

# Otimizações de cache (apenas se for staging/prod)
ARG APP_ENV=local
RUN if [ "$APP_ENV" != "local" ]; then \
      php artisan config:cache && \
      php artisan route:cache && \
      php artisan view:cache; \
    fi

# ---- Stage 2: final ----
FROM php:8.3-fpm

WORKDIR /var/www

COPY --from=builder /var/www /var/www

EXPOSE 9000
CMD ["php-fpm"]
