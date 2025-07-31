local:
	docker-compose -f docker-compose.yml -f docker-compose.override.yml up -d

staging:
	docker-compose -f docker-compose.yml -f docker-compose.staging.yml up -d

prod:
	docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d

renew-cert:
	docker-compose run certbot
