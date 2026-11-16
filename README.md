# anabe

A digital game key marketplace in the style of eneba.com, built for the Eneba Intern Assignment 2027.
Demo project, not affiliated with Eneba. No real payments.

> Work in progress. The live URL, demo account, test cards, architecture and data model are added
> as the project is built (see [PLAN.md](PLAN.md), Section 11).

## Purchase flow

```
Homepage / Search → Product page → Cart → (sign in) → Checkout → Payment → Order + key
```

Built in depth: **typo-tolerant search** and a **homepage assembled from independent blocks**.
Everything else works end-to-end at a solid baseline. Scope and trade-offs are explained in
[docs/DECISIONS.md](docs/DECISIONS.md).

## Tech stack

- **Frontend:** React 19, TypeScript, Vite, TanStack Query, React Router, Tailwind CSS v4
- **Backend:** PHP 8.5, Laravel 13 (REST API), Laravel Sanctum, FrankenPHP in production
- **Database:** PostgreSQL 17 with `pg_trgm`
- **Hosting:** Render (static site, Docker web service, Postgres), defined in `render.yaml`

## Repository layout

```
backend/    Laravel API
frontend/   React app
docs/       DECISIONS.md: decisions, alternatives and reasons
ai-logs/    Full AI agent chat history (assignment deliverable)
PLAN.md     Project plan
```

## Running locally

Requirements: PHP 8.5 with `pdo_pgsql`, Composer, Node 24, Docker.

```sh
# 1. Database (Postgres 17 on localhost:5432, plus a separate anabe_test database for tests)
docker compose up -d --wait

# 2. Backend on http://localhost:8000
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve

# 3. Frontend on http://localhost:5173 (in a second terminal)
cd frontend
npm install
cp .env.example .env
npm run dev
```

## Tests

```sh
cd backend && php artisan test        # PHPUnit, against the anabe_test Postgres database
cd frontend && npm test               # Vitest + React Testing Library
cd frontend && npm run typecheck      # strict TypeScript
```

## Deployment

**Live:** https://anabe-web.onrender.com (API: https://anabe-api.onrender.com/api/health)

Production runs on Render's free plan in Frankfurt. All of it is defined in [render.yaml](render.yaml):

| Resource    | Type                 | What it is |
|-------------|----------------------|------------|
| `anabe-web` | Static site          | The React app, built from `frontend/` and served from Render's CDN |
| `anabe-api` | Docker web service   | The Laravel API, built from `backend/Dockerfile` and served by FrankenPHP. On start it caches config, runs migrations, then serves requests |
| `anabe-db`  | Render Postgres 17   | Reachable only from Render's private network |

Every push to `main` redeploys whichever of the two apps changed.

The free web service sleeps after 15 minutes without traffic, so the first request after that takes
30–60 seconds; the frontend shows "Waking up the server…" while it waits.

The free Render Postgres database was created on 2026-11-16 and **expires on 2026-12-16**. To replace it,
recreate the database from the Blueprint and redeploy the API, which migrates the new database on start.

**First-time setup** (once, in the Render dashboard): in the `anabe` workspace choose
New → Blueprint, pick this repository and apply. Render asks for the one secret, `APP_KEY`.
Generate it locally with `php artisan key:generate --show`, copy it into the dashboard, and do not
keep it anywhere else.

## What I would do next

Left out on purpose, to spend the time on the two deep features:

- **CI:** a GitHub Actions workflow running the backend tests against a Postgres service and the
  frontend typecheck, tests and build on every push, with Render deploying only after checks pass
  (`autoDeployTrigger: checksPass`).
- **Concurrency test for checkout:** a test that holds a key locked from a second database connection
  while checkout runs, proving `FOR UPDATE SKIP LOCKED` reports "out of stock" instead of waiting, and
  that two buyers of the last two keys get different keys.
- **Reservation expiry:** if the server stops between reserving keys and confirming payment, the keys
  stay `reserved`. A scheduled job that releases keys of `pending` orders older than a few minutes
  would return them to stock.
- **Admin:** a back office for sellers to add offers and upload keys, and for staff to edit products,
  banners and homepage blocks (today they are changed in the database or the seeders).
