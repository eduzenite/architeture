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

# Define diretório de trabalho
WORKDIR /var/www

# Garante que as pastas storage e public/storage existam
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
    storage/app/public public/storage \
    && chown -R www-data:www-data storage public/storage

# Copia apenas as dependências para cache eficiente
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-scripts

# Copia o restante do código da aplicação
COPY . .

# Executa scripts do Composer depois que o projeto completo estiver copiado
RUN composer run-script post-autoload-dump

# Otimizações para ambientes diferentes de local
ARG APP_ENV=local
RUN if [ "$APP_ENV" != "local" ]; then \
      php artisan config:cache && \
      php artisan route:cache && \
      php artisan view:cache; \
    fi

# Cria link simbólico para public/storage (Laravel)
RUN php artisan storage:link || true

# ---- Stage 2: final ----
FROM php:8.3-fpm

# Instala extensões novamente no container final
RUN apt-get update && apt-get install -y \
    libzip-dev zip libpng-dev libonig-dev libpq-dev libxml2-dev \
    libjpeg-dev libfreetype6-dev libicu-dev libxslt1-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo pdo_mysql mbstring zip opcache gd intl xsl bcmath sockets \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Define diretório de trabalho
WORKDIR /var/www

# Copia a aplicação do estágio anterior
COPY --from=builder /var/www /var/www

# Garante permissões corretas
RUN chown -R www-data:www-data storage public/storage

# Expondo porta do php-fpm (não serve a aplicação diretamente)
EXPOSE 9000

# EntryPoint padrão
CMD ["php-fpm"]
