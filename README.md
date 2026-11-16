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

Production runs on Render's free plan in Frankfurt. All of it is defined in [render.yaml](render.yaml):

| Resource    | Type                 | What it is |
|-------------|----------------------|------------|
| `anabe-web` | Static site          | The React app, built from `frontend/` and served from Render's CDN |
| `anabe-api` | Docker web service   | The Laravel API, built from `backend/Dockerfile` and served by FrankenPHP. On start it caches config, runs migrations, then serves requests |
| `anabe-db`  | Render Postgres 17   | Reachable only from Render's private network |

Every push to `main` redeploys whichever of the two apps changed.

The free web service sleeps after 15 minutes without traffic, so the first request after that takes
30–60 seconds; the frontend shows "Waking up the server…" while it waits.

**First-time setup** (once, in the Render dashboard): in the `anabe` workspace choose
New → Blueprint, pick this repository and apply. Render asks for the one secret, `APP_KEY`.
Generate it locally with `php artisan key:generate --show`, copy it into the dashboard, and do not
keep it anywhere else.
