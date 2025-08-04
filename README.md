## Executando o Projeto com Docker

Este projeto Laravel utiliza `docker-compose` para orquestrar os ambientes de desenvolvimento, staging e produção com serviços como NGINX, MySQL, Redis, MinIO e Mailhog.

---

### Ambiente de Desenvolvimento

O ambiente de desenvolvimento roda o Laravel com `php artisan serve`, e inclui ferramentas úteis como:

* Mailhog (visualização de e-mails)
* MinIO (armazenamento S3 fake)

```bash
docker-compose up -d
```

> Esse comando utiliza automaticamente os arquivos:
>
> * `docker-compose.yml`
> * `docker-compose.override.yml`

Acessos padrão:

* Laravel: [http://localhost:8000](http://localhost:8000)
* Mailhog: [http://localhost:8026](http://localhost:8026)
* MinIO Console: [http://localhost:9001](http://localhost:9001)

---

### Ambiente de Staging

O ambiente de staging simula a produção, mas ainda roda com `php artisan serve`. Inclui Mailhog e MinIO para testes.

```bash
docker-compose -f docker-compose.yml -f docker-compose.staging.yml up -d
```

> O Laravel será executado com:
>
> * `APP_ENV=staging`
> * `APP_DEBUG=false`

---

### Ambiente de Produção

Ambiente real com otimizações para produção:

* Laravel roda com `php-fpm`
* Servido via NGINX
* Suporte a HTTPS com Certbot (Let's Encrypt)
* Sem ferramentas de desenvolvimento como Mailhog e MinIO

```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

> Certifique-se de configurar corretamente:
>
> * Portas 80/443 no host
> * Domínio e e-mail no serviço `certbot`
> * Volumes persistentes para certificados SSL

---

### Comandos Úteis

Acessar o container da aplicação (Laravel):

```bash
docker-compose exec app bash
```

Rodar as migrations manualmente:

```bash
docker-compose exec app php artisan migrate
```

Derrubar os containers e remover volumes:

```bash
docker-compose down -v
```

Renovar certificados SSL (produção):

```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml run --rm certbot renew
```

---

### Volumes Utilizados

| Nome         | Serviço         | Descrição                        |
| ------------ | --------------- | -------------------------------- |
| `db_data`    | MySQL           | Dados do banco                   |
| `redis_data` | Redis           | Armazenamento de cache           |
| `minio_data` | MinIO           | Objetos S3                       |
| `ssl`        | Certbot / NGINX | Certificados TLS (Let's Encrypt) |

---

### Requisitos

* Docker 20+
* Docker Compose 1.29+
* Permissão para usar portas 80/443 (produção)
