#!/bin/sh

certbot certonly --webroot \
  --webroot-path=/var/www/public \
  -d $DOMAIN \
  --email $EMAIL \
  --agree-tos \
  --non-interactive

# Renova certificados a cada 12h
while true; do
  certbot renew
  sleep 12h
done
