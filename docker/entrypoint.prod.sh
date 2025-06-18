#!/bin/bash
set -e

# Production entrypoint script
echo "🚀 Starting FinFinity Portal in PRODUCTION mode..."

# Set environment variables for production
export PHP_FPM_LISTEN="9000"
export PHP_MEMORY_LIMIT="256M"
export PHP_MAX_EXECUTION_TIME="300"
export PHP_UPLOAD_MAX_FILESIZE="50M"
export PHP_POST_MAX_SIZE="50M"

# Wait for external database connection
echo "🔍 Checking database connectivity..."
DB_HOST=${DB_HOST:-91.108.110.65}
DB_PORT=${DB_PORT:-5432}

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

# Change to Laravel directory
cd /var/www/html

# Ensure storage directories exist with proper permissions
echo "📁 Setting up storage directories..."
mkdir -p storage/app/public
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Set strict production permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Clear any existing caches
echo "🧹 Clearing development caches..."
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Generate application key if not exists (production safety)
if [ ! -f /var/www/html/.env ]; then
    echo "⚠️ No .env file found! Creating from example..."
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Check if APP_KEY is set
if ! grep -q "APP_KEY=" /var/www/html/.env || [ -z "$(grep APP_KEY= /var/www/html/.env | cut -d '=' -f2)" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --no-interaction
fi

# Run Laravel optimizations for production
echo "⚡ Optimizing Laravel for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage symlink if it doesn't exist
if [ ! -L /var/www/html/public/storage ]; then
    echo "🔗 Creating storage symlink..."
    php artisan storage:link
fi

# Set final permissions
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 755 /var/www/html/storage
chmod -R 755 /var/www/html/bootstrap/cache

# Database migrations (optional - uncomment if needed)
# echo "🗄️ Running database migrations..."
# php artisan migrate --force --no-interaction

# Create health check endpoint
echo "🏥 Setting up health check..."
mkdir -p /var/www/html/public/api
cat > /var/www/html/public/api/health.php << 'EOF'
<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$health = [
    'status' => 'ok',
    'timestamp' => date('c'),
    'service' => 'FinFinity Portal',
    'version' => '1.0.0',
    'environment' => 'production'
];

// Check database connection
try {
    $pdo = new PDO(
        "pgsql:host=" . ($_ENV['DB_HOST'] ?? '91.108.110.65') . ";port=" . ($_ENV['DB_PORT'] ?? '5432') . ";dbname=" . ($_ENV['DB_DATABASE'] ?? 'intranet'),
        $_ENV['DB_USERNAME'] ?? 'postgres',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_TIMEOUT => 5]
    );
    $health['database'] = 'connected';
} catch (Exception $e) {
    $health['database'] = 'disconnected';
    $health['status'] = 'degraded';
}

// Check Redis connection
try {
    $redis = new Redis();
    $redis->connect($_ENV['REDIS_HOST'] ?? '127.0.0.1', $_ENV['REDIS_PORT'] ?? 6379, 5);
    $health['redis'] = 'connected';
    $redis->close();
} catch (Exception $e) {
    $health['redis'] = 'disconnected';
}

http_response_code($health['status'] === 'ok' ? 200 : 503);
echo json_encode($health, JSON_PRETTY_PRINT);
EOF

chown www-data:www-data /var/www/html/public/api/health.php

echo "✅ Production setup completed!"
echo "🌐 Starting services..."

# Switch to www-data user and start supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf 