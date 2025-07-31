# ---- Stage 1: build ----
FROM php:8.3-fpm AS builder

# Instala dependências
RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev zip libpng-dev libonig-dev curl \
    && docker-php-ext-install pdo pdo_mysql gd zip mbstring opcache \
    && rm -rf /var/lib/apt/lists/*

# Instala composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copia e instala dependências do Laravel
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --optimize-autoloader

COPY . .

# ---- Stage 2: final ----
FROM php:8.3-fpm

WORKDIR /var/www

# Copia arquivos da build
COPY --from=builder /var/www /var/www

# Ajusta permissões
RUN chown -R www-data:www-data /var/www

EXPOSE 9000
CMD ["php-fpm"]
