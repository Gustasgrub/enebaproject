# Production image for the API, built and run by Render (render.yaml).
# FrankenPHP is the Caddy web server with PHP built in. It runs in classic mode: see docker/Caddyfile.
# The tag pins PHP 8.5, the same minor version as local development.
FROM dunglas/frankenphp:1-php8.5

# pdo_pgsql: the Postgres driver. zip: lets Composer unpack downloaded packages.
# install-php-extensions comes with the base image and installs prebuilt extensions.
RUN install-php-extensions pdo_pgsql zip

# PHP's recommended production settings: errors are logged, never shown to the client.
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
# Composer runs as root only while the image is built; the app itself runs as www-data (below).
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

# Dependencies get their own layer, so it is rebuilt only when composer.json or composer.lock
# change, not on every code change.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY . .
# Writes the optimized class map and runs Laravel's package discovery (the post-autoload-dump script).
RUN composer dump-autoload --no-dev --optimize --no-interaction

# Run as an unprivileged user. Laravel writes to storage/ and bootstrap/cache/, and Caddy keeps its
# state in /config and /data (set by the base image as XDG_CONFIG_HOME and XDG_DATA_HOME).
RUN chown -R www-data:www-data storage bootstrap/cache /config /data
# The base image gives the frankenphp binary a capability for binding ports below 1024. The server
# listens on Render's high port, so it is not needed, and Render does not grant it: Linux then refuses
# to start the binary at all ("Operation not permitted"). Removing it lets it run with no capabilities.
RUN setcap -r /usr/local/bin/frankenphp
USER www-data

CMD ["sh", "docker/start.sh"]
