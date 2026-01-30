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
npm install

echo "Creating storage symlink..."
php artisan storage:link --force

echo "Starting Vite dev server in background..."
npm run dev &

echo "Waiting for Vite to be ready..."
sleep 5

echo "Starting Laravel development server..."
exec php artisan serve --host=0.0.0.0 --port=8001
