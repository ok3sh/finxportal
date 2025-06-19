# FinFinity Portal - Existing Infrastructure Deployment Guide

## 🚀 Overview

This guide provides deployment instructions for the FinFinity Portal when you already have PostgreSQL, Redis, and Paperless running as separate Docker containers.

## 📋 Current Infrastructure

Your existing setup includes:
- **PostgreSQL**: `finx-postgres` container on port 5432
- **Redis**: `redis_dms` container on port 6379  
- **Paperless**: `paperless` container on port 8000
- **Apache Proxy**: Virtual hosts configured for domain routing
- **External Network**: `finxPortal` network connecting all services

## 🛠️ Deployment Steps

### 1. Navigate to Project Directory
```bash
cd /home/worklog/portal
# or wherever your FinFinity Portal code is located
```

### 2. Ensure Your .env File is Ready
Make sure you have your `.env` file with all required configuration in the project root before running the deployment.

### 3. Stop Existing Portal Containers
```bash
# Stop the old containers
docker stop portal_backend portal_frontend
docker rm portal_backend portal_frontend
```

### 4. Deploy New Optimized Containers
```bash
# Run the deployment script
./deploy-existing-infra.sh

# Or manually with docker-compose
docker-compose -f docker-compose.existing-infra.yml up --build -d
```

## 🔧 Configuration Details

### Docker Compose Configuration

The new setup uses `docker-compose.existing-infra.yml` with:

- **Backend Container**: `finfinity_portal_backend`
  - Connects to existing `finx-postgres` database
  - Uses existing `redis_dms` for caching
  - Integrates with existing `paperless` container
  - Exposes port 8080 (matches Apache proxy)

- **Frontend Container**: `finfinity_portal_frontend`  
  - Optimized React build with Vite
  - Exposes port 3000 (matches Apache proxy)
  - Configured for production API endpoints

### Network Integration

Both containers join the existing `finxPortal` network:
```yaml
networks:
  finxPortal:
    external: true
    name: finxPortal
```

### Environment Variables

Key environment variables for existing infrastructure:
```bash
# Database (connects to existing PostgreSQL)
DB_HOST=finx-postgres
DB_PORT=5432
DB_DATABASE=laraveldb
DB_USERNAME=laraveldb
DB_PASSWORD=L2r2v3l

# Redis (connects to existing Redis)
REDIS_HOST=redis_dms
REDIS_PORT=6379

# Paperless Integration
PAPERLESS_URL=http://paperless:8000
PAPERLESS_TOKEN=your_paperless_token

# Application URLs (matches Apache proxy)
APP_URL=https://portal.finfinity.co.in:8443
VITE_API_URL=https://portal.finfinity.co.in:8443
VITE_APP_URL=https://portal.finfinity.co.in
```

## 🌐 URL Structure

After deployment, your services will be accessible via:

### Direct Container Access
- Frontend: http://localhost:3000
- Backend API: http://localhost:8080
- Backend Health: http://localhost:8080/api/health

### Apache Proxy URLs (Production)
- Frontend: https://portal.finfinity.co.in (Apache → localhost:3000)
- Backend API: https://portal.finfinity.co.in:8443 (Apache → localhost:8080)
- Paperless: https://paperless.finfinity.co.in (Apache → localhost:8000)

## 🔍 Verification Steps

### 1. Check Container Status
```bash
docker ps

# Should show:
# finfinity_portal_backend   (port 8080->80)
# finfinity_portal_frontend  (port 3000->3000)
# finx-postgres              (port 5432->5432)
# redis_dms                  (internal)
# paperless                  (port 8000->8000)
```

### 2. Test Connectivity
```bash
# Test backend health
curl http://localhost:8080/api/health

# Test frontend
curl http://localhost:3000

# Test database connection from backend
docker exec finfinity_portal_backend php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB OK';"
```

### 3. Check Container Status
```bash
# Backend container
docker logs finfinity_portal_backend

# Frontend container
docker logs finfinity_portal_frontend
```

## 🚨 Troubleshooting

### Common Issues

#### 1. Network Connectivity
```bash
# Verify all containers are on the same network
docker network inspect finxPortal

# Ensure containers can reach each other
docker exec finfinity_portal_backend ping finx-postgres
docker exec finfinity_portal_backend ping redis_dms
```

#### 2. Database Connection Issues
```bash
# Check PostgreSQL container
docker logs finx-postgres

# Test connection from host
psql -h localhost -p 5432 -U laraveldb -d laraveldb

# Test from backend container
docker exec finfinity_portal_backend php artisan migrate:status
```

#### 3. Permission Issues
```bash
# Fix storage permissions in backend container
docker exec finfinity_portal_backend chown -R www-data:www-data storage bootstrap/cache
docker exec finfinity_portal_backend chmod -R 775 storage bootstrap/cache
```

## 🔧 Management Commands

### Container Management
```bash
# Stop containers
docker-compose -f docker-compose.existing-infra.yml down

# Restart containers
docker-compose -f docker-compose.existing-infra.yml restart

# Rebuild and restart
docker-compose -f docker-compose.existing-infra.yml up --build -d

# View container status
docker-compose -f docker-compose.existing-infra.yml ps
```

### Laravel Commands
```bash
# Run migrations
docker exec finfinity_portal_backend php artisan migrate

# Clear caches
docker exec finfinity_portal_backend php artisan cache:clear
docker exec finfinity_portal_backend php artisan config:clear

# Run queue workers (if needed)
docker exec finfinity_portal_backend php artisan queue:work

# Generate storage link
docker exec finfinity_portal_backend php artisan storage:link
```

### Database Operations
```bash
# Access PostgreSQL
docker exec -it finx-postgres psql -U laraveldb -d laraveldb

# Run database seeder
docker exec finfinity_portal_backend php artisan db:seed

# Create database backup
docker exec finx-postgres pg_dump -U laraveldb laraveldb > backup.sql
```

## 📊 Monitoring

### Health Checks
```bash
# Backend health endpoint
curl -s http://localhost:8080/api/health

# Container resource usage
docker stats finfinity_portal_backend finfinity_portal_frontend

# System resource usage
docker system df
```

### Container Monitoring
```bash
# Backend container status
docker logs finfinity_portal_backend

# Frontend container status
docker logs finfinity_portal_frontend

# Apache access logs (on host)
sudo tail -f /var/log/apache2/portal_frontend_access.log
sudo tail -f /var/log/apache2/portal_backend_access.log
```

## 🔒 Security Considerations

### Production Checklist
- [ ] .env file contains production credentials
- [ ] APP_DEBUG=false in .env
- [ ] APP_ENV=production in .env
- [ ] Strong APP_KEY generated
- [ ] Database credentials secured
- [ ] Microsoft Graph tokens configured
- [ ] SSL certificates valid and current
- [ ] File permissions properly set
- [ ] Firewall rules configured

### Backup Strategy
```bash
# Database backup
docker exec finx-postgres pg_dump -U laraveldb laraveldb | gzip > backup_$(date +%Y%m%d_%H%M%S).sql.gz

# Container image backup
docker save finfinity_portal_backend:latest | gzip > backend_image_backup.tar.gz
```

## 🎯 Performance Optimization

### Resource Limits
Consider adding resource limits to docker-compose.existing-infra.yml:
```yaml
services:
  portal-backend:
    deploy:
      resources:
        limits:
          memory: 1G
          cpus: '1.0'
        reservations:
          memory: 512M
          cpus: '0.5'
```

### Scaling
```bash
# Scale frontend containers
docker-compose -f docker-compose.existing-infra.yml up --scale portal_frontend=2 -d

# Load balance with nginx or Apache
```

This deployment approach maintains your existing infrastructure while optimizing the FinFinity Portal containers for production use. 