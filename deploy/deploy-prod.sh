#!/usr/bin/env bash
set -euo pipefail

ENV_NAME="prod"
COMPOSE_FILE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/prod/docker-compose.prod.yml"
ENV_FILE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/prod/.env"
CONTAINER="acopio-api-prod"

if ! docker network inspect dokploy-network >/dev/null 2>&1; then
    echo "ERROR: external network 'dokploy-network' not found."
    echo "       On Dokploy this network already exists. If not, run:"
    echo "         docker network create dokploy-network"
    exit 1
fi

if [ ! -f "$ENV_FILE" ]; then
    echo "==> Creating $ENV_FILE from .env.example"
    cp "${ENV_FILE}.example" "$ENV_FILE"
    echo "    Edit $ENV_FILE (APP_KEY, DB_*, APP_URL, FRONTEND_URL, SSO) before continuing."
    exit 0
fi

echo "==> Building image for $ENV_NAME"
docker compose -f "$COMPOSE_FILE" build

echo "==> Starting stack for $ENV_NAME"
docker compose -f "$COMPOSE_FILE" up -d

echo "==> Waiting for the API to become healthy"
for _ in $(seq 1 30); do
    if docker inspect --format='{{.State.Health.Status}}' "$CONTAINER" 2>/dev/null | grep -q healthy; then
        break
    fi
    sleep 2
done

docker compose -f "$COMPOSE_FILE" ps
echo
echo "==> ACOPIO API ($ENV_NAME) ready. Expose port 80 of '${CONTAINER}' via the Dokploy proxy."
