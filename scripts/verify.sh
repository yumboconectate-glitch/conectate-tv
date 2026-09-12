#!/usr/bin/env bash
set -euo pipefail
echo "===== CONTENEDORES ====="
docker compose ps
echo
echo "===== PANEL ====="
curl -I -u "${1:-admin}" "http://127.0.0.1:${PANEL_PORT:-8098}/" || true
echo
echo "===== LOG APP ====="
docker compose logs --tail=40 app
