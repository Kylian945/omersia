#!/bin/sh
set -e

if [ -f composer.lock ]; then
    echo "Running composer install..."
    composer install --no-interaction
else
    echo "No composer.lock found, running composer update..."
    composer update --no-interaction
fi

echo "Installing NPM dependencies..."
if [ -f package-lock.json ]; then
    npm ci --no-audit --no-fund
else
    npm install
fi

echo "Creating storage symlink..."
php artisan storage:link --force

echo "Starting Vite dev server in background..."
npm run dev &

echo "Starting Reverb server in background..."
php artisan reverb:start --host=0.0.0.0 --port=8080 &

echo "Waiting for Vite to be ready..."
MAX_WAIT=30
WAITED=0
while [ $WAITED -lt $MAX_WAIT ]; do
    if wget -q --spider http://localhost:5173 2>/dev/null; then
        echo "Vite is ready!"
        break
    fi
    sleep 1
    WAITED=$((WAITED + 1))
done
if [ $WAITED -eq $MAX_WAIT ]; then
    echo "Warning: Vite may not be ready yet, proceeding anyway..."
fi

echo "Starting Laravel development server..."
exec php artisan serve --host=0.0.0.0 --port=8001
