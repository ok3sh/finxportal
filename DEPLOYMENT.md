# FinFinity Portal - Linux Production Deployment Guide

## 🚀 Overview

This guide provides complete instructions for deploying the FinFinity Portal on Linux production servers using Docker containerization.

## 📋 Prerequisites

### System Requirements
- **OS**: Ubuntu 20.04 LTS / Ubuntu 22.04 LTS / CentOS 8+ / RHEL 8+
- **RAM**: Minimum 4GB, Recommended 8GB+
- **Storage**: Minimum 20GB free space
- **CPU**: 2+ cores recommended

### Required Software
- Docker Engine 20.10+
- Docker Compose 1.29+
- Git
- SSL certificates for HTTPS

## 🛠️ Installation Steps

### 1. Install Docker (Ubuntu/Debian)

```bash
# Update package index
sudo apt-get update

# Install prerequisites
sudo apt-get install apt-transport-https ca-certificates curl gnupg lsb-release

# Add Docker's official GPG key
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# Add Docker repository
echo "deb [arch=amd64 signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Install Docker Engine
sudo apt-get update
sudo apt-get install docker-ce docker-ce-cli containerd.io

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/download/1.29.2/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Add user to docker group
sudo usermod -aG docker $USER
```

### 2. Install Docker (CentOS/RHEL)

```bash
# Install prerequisites
sudo yum install -y yum-utils device-mapper-persistent-data lvm2

# Add Docker repository
sudo yum-config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo

# Install Docker
sudo yum install -y docker-ce docker-ce-cli containerd.io

# Start and enable Docker
sudo systemctl start docker
sudo systemctl enable docker

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/download/1.29.2/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Add user to docker group
sudo usermod -aG docker $USER
```

### 3. Clone and Setup Project

```bash
# Clone the repository
git clone <your-repo-url> finfinity-portal
cd finfinity-portal/finfinity/portal\ ver2/

# Copy and configure environment
cp .env.example .env
nano .env  # Edit configuration

# Ensure SSL certificates are in place
ls -la certs/
# Should contain: localhost+2.pem and localhost+2-key.pem
```

### 4. Configure Environment (.env)

```bash
# Application Settings
APP_NAME="FinFinity Portal"
APP_ENV=production
APP_KEY=  # Will be generated automatically
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database Configuration
DB_CONNECTION=pgsql
DB_HOST=91.108.110.65
DB_PORT=5432
DB_DATABASE=intranet
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=redis_secret_123

# Microsoft Graph Configuration
MICROSOFT_CLIENT_ID=your_client_id
MICROSOFT_CLIENT_SECRET=your_client_secret
MICROSOFT_TENANT_ID=your_tenant_id
REDIRECT_URI=https://your-domain.com/auth/callback

# Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.office365.com
MAIL_PORT=587
MAIL_USERNAME=noreply@finfinity.co.in
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls

# Paperless Integration
PAPERLESS_TOKEN=your_paperless_token
```

## 🐳 Deployment Options

### Option 1: Development Deployment (Current Setup)

```bash
# Make deployment script executable
chmod +x deploy-production.sh

# Deploy using current configuration
./deploy-production.sh
```

### Option 2: Production Deployment (Recommended)

```bash
# Deploy using production-optimized configuration
docker-compose -f docker-compose.prod.yml up --build -d

# Or use production Dockerfile
docker-compose -f docker-compose.prod.yml --profile production up --build -d
```

### Option 3: Manual Deployment

```bash
# Build production image
docker build -f Dockerfile.prod -t finfinity-portal:production .

# Run with production compose
docker-compose -f docker-compose.prod.yml up -d
```

## 🔧 Post-Deployment Configuration

### 1. Verify Deployment

```bash
# Check container status
docker ps

# Check logs
docker logs -f finfinity_portal_app

# Test health endpoint
curl -k https://localhost/api/health
```

### 2. SSL Certificate Setup

For production domains, replace self-signed certificates:

```bash
# Install certbot (Let's Encrypt)
sudo apt-get install certbot

# Generate SSL certificate
sudo certbot certonly --standalone -d your-domain.com

# Copy certificates to project
sudo cp /etc/letsencrypt/live/your-domain.com/fullchain.pem certs/localhost+2.pem
sudo cp /etc/letsencrypt/live/your-domain.com/privkey.pem certs/localhost+2-key.pem
sudo chown $USER:$USER certs/*

# Restart container
docker-compose restart
```

### 3. Firewall Configuration

```bash
# Ubuntu/Debian (UFW)
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 22/tcp
sudo ufw --force enable

# CentOS/RHEL (Firewalld)
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --reload
```

## 📊 Monitoring and Maintenance

### 1. Log Management

```bash
# View application logs
docker logs -f finfinity_portal_app

# View specific service logs
docker-compose logs nginx
docker-compose logs php-fpm

# Follow all logs
docker-compose logs -f
```

### 2. Performance Monitoring

```bash
# Container resource usage
docker stats

# System resource usage
htop
df -h
```

### 3. Backup Procedures

```bash
# Backup uploaded files
sudo tar -czf backup-$(date +%Y%m%d).tar.gz storage/app/

# Backup logs
sudo tar -czf logs-backup-$(date +%Y%m%d).tar.gz storage/logs/

# Database backup (if using local database)
docker exec finfinity_portal_app pg_dump -U postgres intranet > backup-db-$(date +%Y%m%d).sql
```

## 🔄 Updates and Maintenance

### 1. Application Updates

```bash
# Pull latest code
git pull origin main

# Rebuild and redeploy
./deploy-production.sh

# Or for production
docker-compose -f docker-compose.prod.yml up --build -d
```

### 2. Container Maintenance

```bash
# Update base images
docker-compose pull

# Clean up unused images/containers
docker system prune -a

# Restart services
docker-compose restart
```

## 🚨 Troubleshooting

### Common Issues

#### 1. Container Won't Start

```bash
# Check logs
docker logs finfinity_portal_app

# Check Docker daemon
sudo systemctl status docker

# Check disk space
df -h
```

#### 2. Database Connection Issues

```bash
# Test database connectivity
docker exec finfinity_portal_app php artisan tinker
# In tinker: DB::connection()->getPdo();

# Check network connectivity
docker exec finfinity_portal_app ping 91.108.110.65
```

#### 3. SSL Certificate Issues

```bash
# Check certificate validity
openssl x509 -in certs/localhost+2.pem -text -noout

# Test SSL connection
openssl s_client -connect localhost:443
```

#### 4. Permission Issues

```bash
# Fix storage permissions
docker exec finfinity_portal_app chown -R www-data:www-data storage
docker exec fininity_portal_app chmod -R 755 storage
```

### Performance Issues

#### 1. High Memory Usage

```bash
# Check container memory usage
docker stats --no-stream

# Increase container memory limits in docker-compose.yml
deploy:
  resources:
    limits:
      memory: 2G
```

#### 2. Slow Response Times

```bash
# Check PHP-FPM status
docker exec fininity_portal_app curl http://localhost:9000/status

# Optimize PHP-FPM pool settings
# Edit docker/php/php-fpm.conf

# Check database performance
docker exec fininity_portal_app php artisan db:show
```

## 🔒 Security Considerations

### 1. Container Security

```bash
# Run security scan
docker scan fininity-portal:latest

# Update base images regularly
docker pull php:8.2-fpm-alpine
```

### 2. Network Security

```bash
# Limit container network access
# Use custom networks in docker-compose.yml

# Monitor network connections
sudo netstat -tulpn | grep docker
```

### 3. File Permissions

```bash
# Ensure proper file ownership
sudo find . -type f -exec chmod 644 {} \;
sudo find . -type d -exec chmod 755 {} \;
sudo chmod +x deploy-production.sh
```

## 📞 Support

For additional support:

1. Check application logs: `docker logs -f fininity_portal_app`
2. Review this deployment guide
3. Check Docker documentation: https://docs.docker.com/
4. Contact system administrator

## 🎯 Performance Tuning

### 1. PHP-FPM Optimization

Edit `docker/php/php-fpm.conf`:

```ini
[www]
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500
```

### 2. Nginx Optimization

For high traffic, adjust `docker/nginx/nginx.prod.conf`:

```nginx
worker_processes auto;
worker_connections 1024;
keepalive_timeout 65;
gzip_comp_level 6;
```

### 3. Database Connection Pooling

Configure Laravel database pooling in `.env`:

```env
DB_CONNECTION_POOL_SIZE=20
DB_CONNECTION_TIMEOUT=60
```

---

**Last Updated**: $(date)
**Version**: 1.0.0
**Environment**: Production Linux Deployment 