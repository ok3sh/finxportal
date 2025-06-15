# FinFinity Portal - Docker Deployment Guide

## Overview
This document describes how to deploy your Laravel FinFinity Portal application using Docker while maintaining your existing external database and Redis setup.

## Prerequisites
- Docker and Docker Compose installed
- Your external PostgreSQL database (91.108.110.65:5432) accessible
- Your existing Redis container running
- SSL certificates (localhost+2.pem and localhost+2-key.pem) in the `certs/` folder

## Configuration

### 1. Environment Setup
Your existing `.env` file in the project root is perfect! Docker will use it automatically. 

**No changes needed** - your current `.env` file contains:

```env
APP_NAME=Laravel
APP_ENV=production
APP_KEY=base64:mvENUUvkH4t6I1LUKizn344LvOfMjzteuC8w3261CEc=
APP_DEBUG=false
APP_URL=https://localhost

DB_CONNECTION=pgsql
DB_HOST=91.108.110.65
DB_PORT=5432
DB_DATABASE=finPortal
DB_USERNAME=finPortal
DB_PASSWORD=finXp0r7@1

# Microsoft OAuth (UNCHANGED)
MICROSOFT_CLIENT_ID=1a08a7ee-192b-428f-bb6e-4f961ee18abd
MICROSOFT_CLIENT_SECRET=44Z80~u1wodKAYQ1wC._gLRQv4kU0bWu2PrPabn
MICROSOFT_PROVIDER_URL=https://login.microsoftonline.com/fe2a29a6-1811-4b6b-9aa3-51773ff80ead/v2.0
REDIRECT_URI=https://localhost/auth/callback
MICROSOFT_TENANT_ID=fe2a29a6-1811-4b6b-9aa3-51773ff80ead

# Redis (your existing container)
SESSION_DRIVER=redis
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

# Other settings
PAPERLESS_TOKEN=efb718fdf81c7e98306e53046c6f0809a28d4df4
SESSION_LIFETIME=120
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
```

### 2. SSL Certificates
Ensure your SSL certificates are in the `certs/` directory:
- `certs/localhost+2.pem`
- `certs/localhost+2-key.pem`

## Deployment

### Option 1: Using Deployment Scripts

#### Windows:
```cmd
deploy-production.bat
```

#### Linux/macOS:
```bash
./deploy-production.sh
```

### Option 2: Manual Deployment

1. **Stop any existing containers:**
   ```bash
   docker-compose down
   ```

2. **Build and start the application:**
   ```bash
   docker-compose up --build -d
   ```

3. **Check container status:**
   ```bash
   docker ps
   ```

## Architecture

### Container Configuration
- **Single Container**: PHP-FPM + Nginx
- **Network Mode**: Host (for external service access)
- **Ports**: 8000 (Laravel), 8443 (HTTPS)
- **External Services**: PostgreSQL, Redis, Paperless

### File Structure
```
finfinity/portal ver2/
├── docker-compose.yml          # Container orchestration
├── Dockerfile                  # Container image definition
├── docker/
│   ├── nginx/nginx.conf       # Nginx configuration
│   ├── supervisor/            # Process management
│   └── entrypoint.sh         # Container startup script
├── certs/                     # SSL certificates
└── storage/                   # Laravel storage (persisted)
```

## Service URLs

After deployment, your application will be available at:
- **Laravel API**: http://localhost:8000
- **HTTPS Application**: https://localhost:8443

## Management Commands

### View Logs
```bash
docker logs -f finfinity_portal_app
```

### Access Container Shell
```bash
docker exec -it finfinity_portal_app bash
```

### Restart Application
```bash
docker-compose restart
```

### Stop Application
```bash
docker-compose down
```

### Update Application
```bash
docker-compose down
docker-compose up --build -d
```

## Troubleshooting

### Container Won't Start
1. Check logs: `docker logs finfinity_portal_app`
2. Verify .env file exists and is properly configured
3. Ensure SSL certificates are in place
4. Check external database connectivity

### Database Connection Issues
- Verify PostgreSQL server (91.108.110.65:5432) is accessible
- Check database credentials in .env file
- Ensure container can reach external network

### Redis Connection Issues
- Verify Redis container is running on 127.0.0.1:6379
- Check Redis configuration in .env file

### SSL Certificate Issues
- Ensure certificates exist in certs/ directory
- Verify certificate file names match configuration
- Check certificate validity

## Production Notes

### Security Considerations
- Uses host network mode for external service access
- SSL/TLS encryption for HTTPS traffic
- Secure session management with Redis
- Microsoft Graph OAuth integration maintained

### Performance
- Optimized for production with cached configurations
- PHP-FPM for efficient PHP processing
- Nginx for high-performance web serving
- External PostgreSQL for database performance

### Maintenance
- Storage directory is persisted across container restarts
- Logs are stored in container (accessible via docker logs)
- No data loss on container updates (external database)

## Support

This containerization maintains all your existing functionality:
- ✅ External PostgreSQL database connection
- ✅ Existing Redis container usage
- ✅ Microsoft Graph OAuth (no redirect URI changes)
- ✅ HTTPS with your certificates
- ✅ Paperless integration
- ✅ All Laravel application features

For issues, check container logs and verify all external services are accessible. 