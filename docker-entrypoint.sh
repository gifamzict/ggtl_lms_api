#!/bin/bash
set -e

echo "========================================="
echo "Laravel Docker Container Starting"
echo "========================================="

# Use PORT from Railway or default to 80
PORT=${PORT:-80}
echo "Configuring Apache to listen on port $PORT"

# Update Apache ports configuration
sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
sed -i "s/:80/:$PORT/g" /etc/apache2/sites-available/000-default.conf

# Create necessary directories if they don't exist
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache

# Set permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "Directory permissions set"
echo "Starting Apache on port $PORT..."
echo "========================================="

# Start Apache in foreground
exec apache2-foreground
