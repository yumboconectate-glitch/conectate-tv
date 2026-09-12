# Conectate TV v0.9

Plataforma IPTV autoalojada para **Conectate Comunicaciones**.

Conectate TV centraliza la administración de canales, clientes, planes, dispositivos, sesiones, EPG, monitoreo, alertas y operación IPTV, integrándose con Astra/Cesbo y ofreciendo compatibilidad con clientes Xtream como TiviMate.

## Arquitectura

```text
Panel administrativo / Apps IPTV
              |
              v
            NGINX
              |
              v
        Laravel / PHP
         /    |     \
        v     v      v
 PostgreSQL  Redis  Astra/Cesbo
        |
        v
    EPG / XMLTV
```

## Stack

- Laravel 13
- PHP 8.4
- PostgreSQL 17
- Redis 7
- NGINX 1.28
- Docker / Docker Compose
- Astra Cesbo
- XMLTV / EPG
- API REST
- Endpoints compatibles con Xtream

## Servicios Docker

- `app`: backend Laravel principal
- `scheduler`: tareas programadas y sincronizaciones
- `nginx`: publicación HTTP y proxy hacia Laravel
- `db`: PostgreSQL
- `redis`: cache y servicios auxiliares

## Panel administrativo

El panel incluye:

- Dashboard
- Canales
- Categorías
- Clientes
- Planes
- Dispositivos
- Sesiones
- EPG
- Operación IPTV
- Monitoreo
- Centro NOC
- Alertas e incidentes
- Actividad
- API
- Configuración
- Usuarios y roles

## Seguridad v0.9

El panel utiliza autenticación Laravel mediante:

- `RequirePanelAuth`
- `RequirePanelAdmin`
- `RequireApiToken`

Roles actuales:

- `admin`
- `tecnico`
- `comercial`

Las rutas administrativas requieren sesión válida. La sección `/settings` requiere además rol `admin`.

La autenticación incluye hash de contraseñas, control de usuarios activos, timeout por inactividad, regeneración de sesión, protección CSRF y limitación de intentos de login.

## IPTV / Xtream

Los endpoints IPTV permanecen separados de la sesión administrativa:

```text
/astra-auth
/playlist/{token}.m3u
/player_api.php
/panel_api.php
/get.php
/xmltv.php
/live/{username}/{password}/{streamId}.m3u8
/live/{username}/{password}/{streamId}.ts
```

Compatibles con clientes como TiviMate, reproductores M3U y aplicaciones Xtream.

## API

La API principal se publica bajo:

```text
/api/v1
```

Está protegida mediante token e incluye operaciones para consultar, crear, activar, suspender, renovar y cambiar plan de abonados, además de health checks.

## EPG

El motor EPG/XMLTV soporta:

- múltiples fuentes XMLTV
- importación automática
- normalización y emparejamiento de canales
- cobertura EPG
- programación futura
- generación XMLTV para clientes IPTV
- control de duplicados
- tareas programadas desde el scheduler

## Astra / Cesbo

La integración con Astra permite:

- sincronizar canales
- consultar estado de streams
- controlar disponibilidad
- consultar sesiones
- autorizar abonados
- relacionar clientes con Astra
- detectar streams OFF AIR
- alimentar dashboard, alertas y monitoreo

## Monitoreo y NOC

Incluye monitoreo de disponibilidad de canales, alertas, incidentes, sesiones activas, dispositivos, actividad administrativa y métricas de Astra.

## Base de datos

PostgreSQL almacena, entre otras, las siguientes entidades:

```text
astra_servers
channels
channel_categories
plans
subscribers
auth_sessions
devices
activity_logs
epg_sources
epg_channels
epg_programmes
alert_events
channel_incidents
system_settings
panel_users
```

El esquema se administra mediante migraciones Laravel.

## Estructura del repositorio

```text
.
├── docker/
│   └── php/
├── docs/
│   └── ARCHITECTURE.md
├── nginx/
│   └── default.conf
├── overlay/
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/
│   ├── resources/
│   └── routes/
├── scripts/
├── docker-compose.yml
├── setup.sh
├── .env.example
└── README.md
```

## Instalación

En este servidor se usa un alias SSH específico:

```bash
git clone git@github-conectate-tv:yumboconectate-glitch/conectate-tv.git
cd conectate-tv
```

En otra máquina con SSH estándar configurado para GitHub puede usarse:

```bash
git clone git@github.com:yumboconectate-glitch/conectate-tv.git
```

Crear configuración:

```bash
cp .env.example .env
```

Editar `.env` con los parámetros reales. Nunca almacenar credenciales reales dentro del repositorio.

Construir:

```bash
docker compose up -d --build
```

Migraciones:

```bash
docker compose exec app php artisan migrate --force
```

Limpiar caché Laravel:

```bash
docker compose exec app php artisan optimize:clear
```

## Actualización

```bash
git pull origin main
docker compose up -d --build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize:clear
```

Antes de actualizar producción se recomienda realizar backup.

## Comandos útiles

```bash
docker compose ps
docker compose logs -f --tail=100
docker compose exec app php artisan route:list
docker compose exec app php artisan --version
docker compose exec app php artisan epg:import
docker compose exec app php artisan astra:sync
```

## Variables sensibles

Nunca deben versionarse:

```text
.env
.htpasswd
*.pem
*.key
dumps de base de datos
backups
tokens
API keys
llaves SSH privadas
```

El repositorio incluye únicamente `.env.example` con valores vacíos o de ejemplo.

## Versionado

Versión estable de referencia: `v0.9.0`

Rama principal: `main`

## Documentación adicional

Consultar `docs/ARCHITECTURE.md` para una descripción ampliada de componentes, seguridad y flujos.

## Proyecto

**Conectate TV** — plataforma IPTV autoalojada orientada a operación ISP, monitoreo y automatización.