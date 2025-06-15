# Docker Setup for FinFinity Portal

This document provides comprehensive instructions for containerizing and running the FinFinity Portal application using Docker.

## Prerequisites

- Docker Engine 20.10+ installed
- Docker Compose 2.0+ installed
- At least 4GB RAM available for containers
- At least 10GB disk space for images and volumes

## Architecture Overview

The Docker setup includes the following services:

### Production Services
- **app**: Laravel application with PHP-FPM, Nginx, and Supervisor
- **db**: PostgreSQL 15 database server
- **redis**: Redis for caching and sessions
- **nginx**: Reverse proxy and load balancer (separate service)

### Development Services (Additional)
- **pgadmin**: PostgreSQL administration interface
- **redis-commander**: Redis management interface
- **mailhog**: Email testing service
- **xdebug**: PHP debugging support

## Quick Start

### 1. Clone and Prepare the Project

```bash
# Navigate to your project directory
cd "finfinity/portal ver2"

# Copy environment file
cp .env.example .env

# Update database configuration in .env
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=finfinity_portal
DB_USERNAME=laravel
DB_PASSWORD=secret

# Update Redis configuration
REDIS_HOST=redis
REDIS_PORT=6379

# Update session driver
SESSION_DRIVER=redis
CACHE_STORE=redis
```

### 2. Production Deployment

```bash
# Build and start all services
docker-compose up -d

# View logs
docker-compose logs -f

# Access the application
open http://localhost:8080
```

### 3. Development Environment

```bash
# Start development environment with debugging tools
docker-compose -f docker-compose.dev.yml up -d

# Access services:
# - Application: http://localhost:8080
# - pgAdmin: http://localhost:8081
# - Redis Commander: http://localhost:8082
# - MailHog: http://localhost:8025
```

## Detailed Configuration

### Environment Variables

Create a `.env` file with the following Docker-specific configurations:

```env
# Application
APP_NAME="FinFinity Portal"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8080

# Database
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=finfinity_portal
DB_USERNAME=laravel
DB_PASSWORD=secret

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
SESSION_DRIVER=redis
CACHE_STORE=redis

# Microsoft Graph (Update with your values)
MICROSOFT_CLIENT_ID=your_client_id_here
MICROSOFT_CLIENT_SECRET=your_client_secret_here
MICROSOFT_TENANT_ID=your_tenant_id_here
REDIRECT_URI=http://localhost:8080/auth/callback

# File uploads
UPLOAD_MAX_FILESIZE=100M
POST_MAX_SIZE=100M
```

### Docker Compose Services

#### Application Service
- **Image**: Custom PHP 8.2 + Nginx + Supervisor
- **Ports**: 8080:80
- **Volumes**: Source code, storage, logs
- **Features**: Laravel queue workers, scheduled tasks

#### Database Service
- **Image**: PostgreSQL 15
- **Ports**: 5432:5432
- **Volume**: Persistent database storage
- **Initialization**: Automatic database setup

#### Redis Service
- **Image**: Redis 7 Alpine
- **Ports**: 6379:6379
- **Volume**: Persistent cache storage
- **Usage**: Sessions, caching, queues

## File Structure

```
finfinity/portal ver2/
├── docker/
│   ├── nginx/
│   │   └── nginx.conf          # Nginx configuration
│   ├── supervisor/
│   │   └── supervisord.conf    # Process management
│   └── entrypoint.sh           # Container initialization
├── Dockerfile                  # Production image
├── Dockerfile.dev              # Development image
├── docker-compose.yml          # Production services
├── docker-compose.dev.yml      # Development services
├── .dockerignore              # Docker build exclusions
└── DOCKER_README.md           # This file
```

## Commands Reference

### Basic Operations

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# Restart specific service
docker-compose restart app

# View logs
docker-compose logs -f app

# Execute commands in container
docker-compose exec app bash
docker-compose exec app php artisan migrate
docker-compose exec app php artisan queue:work
```

### Database Operations

```bash
# Access MySQL shell
docker-compose exec db mysql -u laravel -p finfinity_portal

# Run migrations
docker-compose exec app php artisan migrate

# Seed database
docker-compose exec app php artisan db:seed

# Create backup
docker-compose exec db mysqldump -u laravel -p finfinity_portal > backup.sql
```

### Development Commands

```bash
# Start development environment
docker-compose -f docker-compose.dev.yml up -d

# Install PHP dependencies
docker-compose exec app composer install

# Clear Laravel caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear

# Generate application key
docker-compose exec app php artisan key:generate
```

## Performance Optimization

### Production Optimizations

```bash
# Inside the container, Laravel caches are automatically configured:
# - Config cache: php artisan config:cache
# - Route cache: php artisan route:cache
# - View cache: php artisan view:cache
```

### Resource Limits

Update `docker-compose.yml` to set resource limits:

```yaml
services:
  app:
    deploy:
      resources:
        limits:
          memory: 1G
          cpus: '1.0'
        reservations:
          memory: 512M
          cpus: '0.5'
```

## Volumes and Data Persistence

### Persistent Data
- **Database**: `db_data` volume stores MySQL data
- **Redis**: `redis_data` volume stores cache data
- **Storage**: `./storage/app` mounted for file uploads
- **Logs**: `./storage/logs` mounted for application logs

### Backup Strategy

```bash
# Database backup
docker-compose exec db mysqldump -u laravel -p finfinity_portal > backup-$(date +%Y%m%d).sql

# Application files backup
tar -czf storage-backup-$(date +%Y%m%d).tar.gz storage/

# Volume backup
docker run --rm -v finfinity_portal_db_data:/data -v $(pwd):/backup ubuntu tar czf /backup/db-backup-$(date +%Y%m%d).tar.gz /data
```

## Security Considerations

### Production Security

1. **Environment Variables**: Never commit `.env` files
2. **Database Passwords**: Use strong, unique passwords
3. **SSL/TLS**: Configure HTTPS with proper certificates
4. **File Permissions**: Containers run with proper user permissions
5. **Network Security**: Use Docker networks for service isolation

### SSL Configuration

```bash
# Generate SSL certificates
mkdir -p docker/nginx/ssl
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout docker/nginx/ssl/nginx.key \
  -out docker/nginx/ssl/nginx.crt
```

## Troubleshooting

### Common Issues

#### 1. Database Connection Failed
```bash
# Check if database is running
docker-compose ps db

# Check database logs
docker-compose logs db

# Verify connection
docker-compose exec app php artisan tinker
>>> DB::connection()->getPdo();
```

#### 2. Permission Denied Errors
```bash
# Fix storage permissions
docker-compose exec app chown -R www-data:www-data storage/
docker-compose exec app chmod -R 755 storage/
```

#### 3. Redis Connection Issues
```bash
# Check Redis status
docker-compose exec redis redis-cli ping

# Clear Redis cache
docker-compose exec redis redis-cli FLUSHALL
```

#### 4. High Memory Usage
```bash
# Check container resource usage
docker stats

# Optimize PHP-FPM settings in nginx.conf
# Adjust worker processes and memory limits
```

#### 5. Slow Performance
```bash
# Enable opcache (already configured in production)
# Increase memory limits
# Use Redis for sessions and cache
# Optimize database queries
```

### Debugging

#### Application Logs
```bash
# Laravel logs
docker-compose exec app tail -f storage/logs/laravel.log

# Nginx logs
docker-compose exec app tail -f /var/log/nginx/error.log

# PHP-FPM logs
docker-compose exec app tail -f /var/log/php8.2-fpm.log
```

#### Debug Mode (Development)
```bash
# Enable debug mode
docker-compose -f docker-compose.dev.yml up -d

# Access with Xdebug
# Configure your IDE to connect to localhost:9003
```

## Monitoring and Maintenance

### Health Checks

```bash
# Check service health
docker-compose ps

# Test application endpoints
curl http://localhost:8080/health
curl http://localhost:8080/api/status
```

### Regular Maintenance

```bash
# Update containers
docker-compose pull
docker-compose up -d

# Clean up unused images
docker system prune -a

# Backup before updates
./scripts/backup.sh
```

## Scaling and Load Balancing

### Horizontal Scaling

```yaml
# Scale application instances
services:
  app:
    deploy:
      replicas: 3
    
# Add load balancer
  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
    depends_on:
      - app
```

### Database Scaling

```yaml
# Add read replicas
  db-read:
    image: mysql:8.0
    environment:
      MYSQL_MASTER_HOST: db
      MYSQL_REPLICATION_USER: repl
      MYSQL_REPLICATION_PASSWORD: repl_password
```

## Migration from Existing Setup

### 1. Database Migration
```bash
# Export existing database
mysqldump -u user -p existing_db > migration.sql

# Import to Docker
docker-compose exec db mysql -u laravel -p finfinity_portal < migration.sql
```

### 2. File Migration
```bash
# Copy existing storage files
cp -r /path/to/existing/storage/* ./storage/
```

### 3. Environment Migration
```bash
# Copy and update existing .env
cp /path/to/existing/.env .env
# Update database and Redis hosts as shown above
```

## Support and Resources

### Documentation
- [Laravel Documentation](https://laravel.com/docs)
- [Docker Documentation](https://docs.docker.com/)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [Redis Documentation](https://redis.io/documentation)

### Logs and Debugging
- Application logs: `storage/logs/laravel.log`
- Docker logs: `docker-compose logs -f`
- Database logs: `docker-compose logs db`
- Redis logs: `docker-compose logs redis`

### Performance Monitoring
- Use tools like New Relic, Datadog, or custom monitoring
- Monitor container resources with `docker stats`
- Set up log aggregation with ELK stack or similar

For additional support, refer to the project's main documentation or contact the development team. 