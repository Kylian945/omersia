#!/bin/sh
set -e

echo "Running composer install..."
composer install --no-interaction

echo "Installing NPM dependencies..."
npm install

echo "Building Vite assets..."
npm run build

echo "Creating storage symlink..."
php artisan storage:link --force

echo "Starting Laravel development server..."
exec php artisan serve --host=0.0.0.0 --port=8001
