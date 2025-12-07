#!/bin/bash
set -e

echo "========================================="
echo "Starting Laravel application..."
echo "Port: $PORT"
echo "APP_ENV: $APP_ENV"
echo "DB_CONNECTION: $DB_CONNECTION"
echo "========================================="

# Start PHP built-in server
echo "Executing: php artisan serve --host=0.0.0.0 --port=$PORT"
exec php artisan serve --host=0.0.0.0 --port=$PORT
