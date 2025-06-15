#!/bin/bash
set -e

# Set environment variables
export PHP_FPM_LISTEN="9000"
export PHP_MEMORY_LIMIT="512M"
export PHP_MAX_EXECUTION_TIME="300"
export PHP_UPLOAD_MAX_FILESIZE="100M"
export PHP_POST_MAX_SIZE="100M"

# Wait for database to be ready (external PostgreSQL)
echo "Waiting for database connection..."
DB_HOST=${DB_HOST:-91.108.110.65}
DB_PORT=${DB_PORT:-5432}

# Use timeout and curl/wget instead of nc for better compatibility
timeout=60
counter=0
while [ $counter -lt $timeout ]; do
    if timeout 5 bash -c "</dev/tcp/$DB_HOST/$DB_PORT" 2>/dev/null; then
        echo "Database is ready!"
        break
    fi
    echo "Waiting for database... ($counter/$timeout)"
    sleep 2
    counter=$((counter + 2))
done

if [ $counter -ge $timeout ]; then
    echo "Warning: Database connection timeout. Continuing anyway..."
fi

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
if [ ! -f /var/www/html/.env ]; then
    echo "Creating .env file..."
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Generate app key if not set
if ! grep -q "APP_KEY=" /var/www/html/.env || [ -z "$(grep APP_KEY= /var/www/html/.env | cut -d '=' -f2)" ]; then
    echo "Generating application key..."
    php artisan key:generate --no-interaction
fi

# Clear and cache configurations
echo "Optimizing Laravel..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Cache configurations for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage symlink if it doesn't exist
if [ ! -L /var/www/html/public/storage ]; then
    echo "Creating storage symlink..."
    php artisan storage:link
fi

# Set proper permissions
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

echo "Starting services..."

# Start Laravel development server in background
php artisan serve --host=0.0.0.0 --port=8000 &

# Start Vite development server
npm run vite-dev 