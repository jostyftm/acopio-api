# ACOPIO API

API backend for **ACOPIO**, a disaster-response platform that registers and
locates affected people in Colombia (web + SMS, offline-first, admin dashboard).

Built with **Laravel 12**, **Laravel Passport 13 (OAuth2)**, **Laravel
Socialite** (Google / Microsoft SSO) and **spatie/laravel-permission**.

## Stack

- PHP 8.4, PostgreSQL 18 (via Sail / docker-compose)
- Auth: Passport OAuth2 — public PKCE client for the SPA + confidential
  client for password-grant testing; web-session SSO via Socialite
- RBAC: `admin` and `viewer` roles (Spatie)
- Tests: Pest

## Setup

```bash
composer install
cp .env.example .env
# set DB_*, FRONTEND_URL, GOOGLE_CLIENT_*, MICROSOFT_CLIENT_*

./vendor/bin/sail up -d --build
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan acopio:setup-oauth   # Passport keys + OAuth clients
./vendor/bin/sail artisan acopio:sms:simulate --simulate "REG,Maria Garcia,CC,1234567890,Medellin,La Candelaria"
```

Locally (without Sail) run the API against the dockerized Postgres:

```bash
DB_HOST=127.0.0.1 php artisan serve --port=8000
```

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
DB_HOST=127.0.0.1 php artisan test
```
