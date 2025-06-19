#!/bin/bash
set -e

echo "🚀 Starting FinFinity Portal Backend..."

# Database connection details (using existing infrastructure)
DB_HOST=${DB_HOST:-finx-postgres}
DB_PORT=${DB_PORT:-5432}
DB_DATABASE=${DB_DATABASE:-laraveldb}
REDIS_HOST=${REDIS_HOST:-redis_dms}

echo "📊 Environment Information:"
echo "  - Environment: ${APP_ENV:-production}"
echo "  - Database: ${DB_HOST}:${DB_PORT}/${DB_DATABASE}"
echo "  - Redis: ${REDIS_HOST}:${REDIS_PORT:-6379}"
echo "  - Laravel Version: $(php artisan --version 2>/dev/null || echo 'Unknown')"

# Wait for database connection
echo "🔍 Checking database connectivity..."
timeout=60
counter=0
while [ $counter -lt $timeout ]; do
    if timeout 5 bash -c "</dev/tcp/$DB_HOST/$DB_PORT" 2>/dev/null; then
        echo "✅ Database connection successful!"
        break
    fi
    echo "⏳ Waiting for database... ($counter/$timeout)"
    sleep 2
    counter=$((counter + 2))
done

if [ $counter -ge $timeout ]; then
    echo "⚠️ Database connection timeout. Continuing anyway..."
fi

# Wait for Redis connection
echo "🔍 Checking Redis connectivity..."
timeout=30
counter=0
while [ $counter -lt $timeout ]; do
    if timeout 3 bash -c "</dev/tcp/$REDIS_HOST/${REDIS_PORT:-6379}" 2>/dev/null; then
        echo "✅ Redis connection successful!"
        break
    fi
    echo "⏳ Waiting for Redis... ($counter/$timeout)"
    sleep 1
    counter=$((counter + 1))
done

if [ $counter -ge $timeout ]; then
    echo "⚠️ Redis connection timeout. Continuing anyway..."
fi

# Ensure proper permissions
echo "📁 Setting up directories and permissions..."
mkdir -p storage/app/public
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Clear development caches
echo "🧹 Clearing caches..."
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Optimize for production
echo "⚡ Optimizing Laravel for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage symlink if needed
if [ ! -L public/storage ]; then
    echo "🔗 Creating storage symlink..."
    php artisan storage:link
fi

# Test database connection
echo "🧪 Testing application connectivity..."
if php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connection: OK';" 2>/dev/null; then
    echo "✅ Laravel database connection verified!"
else
    echo "⚠️ Laravel database connection test failed, but continuing..."
fi

# Start supervisor for background jobs
echo "👷 Starting background job manager..."
supervisord -c /etc/supervisor/conf.d/supervisord.conf &

# Wait a moment for supervisor to start
sleep 2

echo "✅ Backend initialization completed!"
echo "🌐 Starting Apache web server..."

# Start Apache in foreground
exec apache2-foreground 