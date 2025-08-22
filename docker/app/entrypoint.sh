#!/bin/sh

echo "Aguardando banco de dados..."
until php artisan db:show > /dev/null 2>&1; do
  sleep 2
done

echo "Rodando migrations..."
php artisan migrate --force

echo "Limpando caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

exec "$@"
