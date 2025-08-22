#!/bin/sh

# Espera o MySQL ficar disponível antes de rodar migrations
echo "Aguardando banco de dados..."
until php artisan db:show > /dev/null 2>&1; do
  sleep 2
done

# Rodar migrations apenas na primeira vez
if [ ! -f /var/www/.migrated ]; then
  echo "Rodando migrations..."
  php artisan migrate --force
  touch /var/www/.migrated
fi

# Rodar o comando padrão do container (Laravel + Queue)
exec "$@"
