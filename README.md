# Conectate TV v0.1.0

MVP inicial del panel propio de TV para Conéctate.

## Arquitectura

- Laravel 13 / PHP 8.4
- PostgreSQL 17
- Redis 7
- NGINX
- Astra Cesbo en `10.0.14.2:8000`
- Panel publicado inicialmente en el puerto `8098`
- Sincronización automática de Astra cada minuto
- Protección inicial con HTTP Basic Auth

## Primer despliegue

```bash
tar -xzf conectate-tv-v0.1.0.tar.gz
cd conectate-tv-v0.1.0
./setup.sh
docker compose up -d --build
docker compose ps
docker compose logs -f app
```

Abrir:

`http://10.1.19.245:8098` (administración) o `http://10.0.21.2:8098` (red 10G)

## Comandos útiles

Sincronización manual:

```bash
docker compose exec app php artisan astra:sync
```

Migraciones:

```bash
docker compose exec app php artisan migrate --force
```

Estado:

```bash
docker compose ps
```

Logs:

```bash
docker compose logs -f --tail=100
```

Rebuild:

```bash
docker compose up -d --build
```

## Seguridad

`setup.sh` solicita en modo oculto la contraseña del panel y la contraseña de Astra.
El archivo `.env` queda con permisos `600`. Cambia la contraseña temporal de Astra
cuando terminen las pruebas.

## Alcance v0.1

- Dashboard de Astra
- Importación automática de `make_stream`
- Estado de streams
- CPU / memoria de Astra
- Base para clientes, planes, dispositivos y API de Conectate OSS
