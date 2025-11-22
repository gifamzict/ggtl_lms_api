#!/bin/bash
set -e

echo "Waiting for database to be ready..."

# Wait for database to be ready
until php artisan db:show 2>/dev/null; do
  echo "Database is unavailable - sleeping"
  sleep 2
done

echo "Database is ready!"

# Create storage link if it doesn't exist
php artisan storage:link 2>/dev/null || true

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Start the server
echo "Starting Laravel server..."
exec php artisan serve --host=0.0.0.0 --port=$PORT
