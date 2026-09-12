# Conectate TV v0.9

## Arquitectura

Cliente IPTV / TiviMate
        |
        v
      NGINX
        |
        v
 Laravel / PHP
   |         |
   |         +---- Redis
   |
   +-------------- PostgreSQL
   |
   +-------------- Astra IPTV
   |
   +-------------- EPG/XMLTV

## Docker

Servicios:

- app
- scheduler
- nginx
- db
- redis

## Panel administrativo

- Dashboard
- Canales
- Categorías
- Planes
- Clientes
- Dispositivos
- Sesiones
- EPG
- Operación IPTV
- Monitoreo
- Centro NOC
- Actividad
- API
- Configuración
- Usuarios y roles

## Seguridad

El panel utiliza autenticación Laravel.

Middleware:

- RequirePanelAuth
- RequirePanelAdmin

Roles:

- admin
- tecnico
- comercial

Los endpoints IPTV permanecen separados de la sesión administrativa.

## Xtream / IPTV

Endpoints principales:

- /astra-auth
- /playlist/{token}.m3u
- /player_api.php
- /panel_api.php
- /get.php
- /xmltv.php
- /live/...m3u8
- /live/...ts
- /api/v1

## Base de datos

PostgreSQL almacena entre otros:

- canales
- categorías
- planes
- abonados
- dispositivos
- sesiones
- EPG
- alertas
- incidentes
- configuración
- usuarios administrativos
