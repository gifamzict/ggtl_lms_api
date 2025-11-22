#!/bin/bash
set -e

echo "Starting Laravel application..."

# Create storage link if it doesn't exist (ignore errors)
php artisan storage:link 2>/dev/null || echo "Storage link already exists or failed"

# Start the server first (migrations will run on first request if needed)
echo "Starting PHP server on port $PORT..."
exec php artisan serve --host=0.0.0.0 --port=$PORT
