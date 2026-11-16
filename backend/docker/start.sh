#!/bin/sh
# Starts the API container on Render (render.yaml). Free web services have no pre-deploy command
# and no shell, so everything that must happen before requests are served runs here, on every start.
set -e

# Cache the configuration and routes into single files. Classic mode boots Laravel on every request,
# so this saves reading every config file each time. It runs here, not in the Dockerfile, because the
# cached config captures environment variables (APP_KEY, DB_URL, ...), which only exist at runtime.
php artisan config:cache
php artisan route:cache

# Bring the database schema up to date. --force confirms running migrations in production.
php artisan migrate --force

# Seed the demo catalog on the first start, when the database is still empty; later starts skip it.
php artisan catalog:seed-if-empty

# Make sure the public demo account exists (does nothing when it already does).
php artisan db:seed --class=DemoAccountSeeder --force

# exec replaces this shell with FrankenPHP, so Render's stop signal reaches the server directly.
exec frankenphp run --config docker/Caddyfile
