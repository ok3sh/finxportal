#!/bin/bash

# Set environment variables
export PHP_FPM_LISTEN="9000"
export PHP_MEMORY_LIMIT="512M"
export PHP_MAX_EXECUTION_TIME="300"
export PHP_UPLOAD_MAX_FILESIZE="100M"
export PHP_POST_MAX_SIZE="100M"

# Wait for database to be ready
echo "Waiting for database connection..."
while ! nc -z db 3306; do
  sleep 1
done
echo "Database is ready!"

# Change to Laravel directory
cd /var/www/html

# Create storage directories if they don't exist
mkdir -p storage/app/public
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs

# Set proper permissions
chown -R www-data:www-data /var/www/html
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Generate application key if not exists
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cp .env.example .env
fi

# Check if APP_KEY is empty and generate if needed
if grep -q "APP_KEY=$" .env || ! grep -q "APP_KEY=" .env; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Create storage link
if [ ! -L public/storage ]; then
    echo "Creating storage link..."
    php artisan storage:link
fi

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Cache configuration for production
if [ "$APP_ENV" = "production" ]; then
    echo "Caching configuration for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Clear any existing caches
php artisan cache:clear
php artisan config:clear

echo "Laravel setup completed!"

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf 