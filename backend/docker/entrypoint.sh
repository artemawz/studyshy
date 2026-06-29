#!/bin/sh
set -e

cd /var/www/html

if [ ! -f .env ]; then
  cp .env.example .env
fi

# Host-.env überschreibt Docker-DB-Einstellungen – im Container immer korrigieren
if [ "${DB_HOST:-}" = "mariadb" ]; then
  echo "Applying Docker database settings to .env..."
  for pair in \
    "DB_CONNECTION=${DB_CONNECTION:-mariadb}" \
    "DB_HOST=mariadb" \
    "DB_PORT=${DB_PORT:-3306}" \
    "DB_DATABASE=${DB_DATABASE:-studyshy}" \
    "DB_USERNAME=${DB_USERNAME:-studyshy}" \
    "DB_PASSWORD=${DB_PASSWORD:-studyshy}"
  do
    key="${pair%%=*}"
    value="${pair#*=}"
    if grep -q "^${key}=" .env 2>/dev/null; then
      sed -i "s|^${key}=.*|${key}=${value}|" .env
    else
      echo "${key}=${value}" >> .env
    fi
  done
fi

composer install --no-interaction --prefer-dist

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  php artisan key:generate --force --no-interaction
fi

php artisan config:clear --no-interaction 2>/dev/null || true
php artisan storage:link --force --no-interaction 2>/dev/null || true

echo "Waiting for database..."
until php -r "new PDO('mysql:host='.getenv('DB_HOST').';port='.(getenv('DB_PORT')?:3306).';dbname='.getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" 2>/dev/null; do
  sleep 2
done

php artisan migrate --force --no-interaction

if [ ! -f storage/app/.seeded ]; then
  php artisan db:seed --force --no-interaction
  touch storage/app/.seeded
fi

exec php artisan serve --host=0.0.0.0 --port=8000
