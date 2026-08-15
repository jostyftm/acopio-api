# ACOPIO API

API backend for **ACOPIO**, a disaster-response platform that registers and
locates affected people in Colombia (web + SMS, offline-first, admin dashboard).

Built with **Laravel 12**, **Laravel Passport 13 (OAuth2)**, **Laravel
Socialite** (Google / Microsoft SSO) and **spatie/laravel-permission**.

## Stack

- PHP 8.4, PostgreSQL 18 (shared `erp_postgres` on the external `dokploy-network`)
- Auth: Passport OAuth2 — public PKCE client for the SPA + confidential
  client for password-grant testing; web-session SSO via Socialite
- RBAC: `admin` and `viewer` roles (Spatie)
- Tests: Pest

## Setup

```bash
composer install
cp .env.example .env
# set DB_*, FRONTEND_URL, GOOGLE_CLIENT_*, MICROSOFT_CLIENT_*

./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan acopio:setup-oauth   # Passport keys + OAuth clients
./vendor/bin/sail artisan acopio:sms:simulate --simulate "REG,Maria Garcia,CC,1234567890,Medellin,La Candelaria"
```

> The local Sail stack runs **only the API** (`compose.yaml`). It connects to the
> shared database `erp_postgres` (container on the external `dokploy-network`),
> the same used by the dev and prod deployments. Run every Artisan command
> through `sail` so the database hostname resolves inside the Docker network.
>
> A queue worker starts automatically with the container (`deploy/local/supervisord.conf`).
> PHP overrides for local development live in `deploy/local/99-sail.ini`.

Locally, run the API without Sail only if you can reach the shared database
(e.g. an SSH tunnel to `erp_postgres:5432`). Otherwise use `sail artisan`.

## Authentication flow (SPA)

```
SPA /login
  → {API}/oauth/authorize (PKCE, client_id = SPA public client)
  → unauthenticated → {API}/login (SSO buttons)
  → Google/Microsoft → SocialAuthController logs in the web session
  → redirect back to /oauth/authorize → auto-approved (first-party client)
  → {FRONTEND_URL}/auth/callback?code=…&state=…
  → SPA exchanges code with PKCE at /oauth/token
```

First-party clients (created with no owner) skip Passport's approval screen
via `App\Models\PassportClient::skipsAuthorization()`.

## API

- `POST /api/v1/registrations` — public registration (duplicate-safe)
- `GET /api/v1/people/search?term=&filter[status]=` — public search, masked documents
- `POST /api/v1/sms/webhook` — SMS intake (twilio-compatible webhook)
- `POST /api/v1/search-reports` — public search reports
- Authenticated (`Bearer` token): `GET /api/v1/me`, `GET /api/v1/stats`,
  `GET/PUT /api/v1/people`, `POST /api/v1/people/{id}/verify`,
  search-reports, user management (admin)
- OpenAPI (Scramble): `GET /docs/api`

## Tests

```bash
./vendor/bin/sail test
```

> Tests use the `testing` database on the shared `erp_postgres` (they do not
> touch the `acopio` database).

## Deployment

The API ships as an nginx + php-fpm container (Laravel code baked into the
image). All environments connect to the shared `erp_postgres` database on the
external `dokploy-network`; the local Sail stack (see above) connects to it too.

### Local (development) — Sail

```bash
./vendor/bin/sail up -d          # compose.yaml, runs only the API (port 8080)
./vendor/bin/sail down
```

### Pre-production — `docker-compose.dev.yml`

```bash
docker compose -f docker-compose.dev.yml build
docker compose -f docker-compose.dev.yml up -d
```

Environment: `deploy/dev/.env` (`.env.example` is the template). `APP_ENV=staging`.
Healthcheck: `GET /up` on port 80 inside the container.

> Note: dev and prod share the same `acopio` database (`erp_postgres`). A
> separate pre-prod database is recommended before going live.
>
> Both stacks allow file uploads up to 100 MB (`deploy/{dev,prod}/php.ini` +
> `client_max_body_size 100m` in `nginx.conf`).

### Production — `docker-compose.prod.yml`

```bash
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d
```

Environment: `deploy/prod/.env` (`.env.example` is the template). `APP_ENV=production`,
optimized build (`composer --no-dev`, opcache, cached routes/views).
