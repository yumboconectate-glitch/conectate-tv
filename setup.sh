#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")"

if [ ! -f .env ]; then
  cp .env.example .env
fi

rand_b64() {
  openssl rand -base64 36 | tr -d '\n'
}

APP_KEY="base64:$(openssl rand -base64 32 | tr -d '\n')"
DB_PASSWORD="$(rand_b64)"

read -rp "Usuario del panel [admin]: " PANEL_USER
PANEL_USER="${PANEL_USER:-admin}"
read -srp "Contraseña del panel: " PANEL_PASS
echo
if [ -z "$PANEL_PASS" ]; then
  echo "La contraseña del panel no puede estar vacía."
  exit 1
fi

read -rp "Usuario Astra [admin]: " ASTRA_USER
ASTRA_USER="${ASTRA_USER:-admin}"
read -srp "Contraseña Astra (temporal o definitiva): " ASTRA_PASS
echo
if [ -z "$ASTRA_PASS" ]; then
  echo "La contraseña Astra no puede estar vacía."
  exit 1
fi

python3 - "$APP_KEY" "$DB_PASSWORD" "$PANEL_USER" "$ASTRA_USER" "$ASTRA_PASS" <<'PY'
from pathlib import Path
import sys
p = Path(".env")
s = p.read_text()
values = {
    "APP_KEY": sys.argv[1],
    "DB_PASSWORD": sys.argv[2],
    "PANEL_USER": sys.argv[3],
    "ASTRA_USER": sys.argv[4],
    "ASTRA_PASSWORD": sys.argv[5],
}
lines=[]
for line in s.splitlines():
    key=line.split("=",1)[0] if "=" in line else None
    if key in values:
        val=values[key].replace("\\","\\\\").replace('"','\\"')
        line=f'{key}="{val}"'
    lines.append(line)
p.write_text("\n".join(lines)+"\n")
PY

HASH="$(openssl passwd -apr1 "$PANEL_PASS")"
printf '%s:%s\n' "$PANEL_USER" "$HASH" > nginx/.htpasswd
chmod 600 .env nginx/.htpasswd

echo
echo "Configuración creada."
echo "Panel administración: http://10.1.19.245:8098"
echo "Panel red 10G:       http://10.0.21.2:8098"
echo "Ahora ejecuta: docker compose up -d --build"
