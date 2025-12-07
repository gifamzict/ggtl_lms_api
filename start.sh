#!/bin/bash
set -e

echo "Starting Laravel application on port $PORT..."

# Just start the server - no database operations
exec php artisan serve --host=0.0.0.0 --port=$PORT
