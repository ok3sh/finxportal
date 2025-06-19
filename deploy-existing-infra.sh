#!/bin/bash

echo "🚀 FinFinity Portal Deployment (Existing Infrastructure)"

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}$1${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# Check prerequisites
print_status "🔍 Checking prerequisites..."

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    print_error "Docker is not running. Please start Docker service."
    exit 1
fi

# Check if docker-compose is available
if ! command -v docker-compose > /dev/null 2>&1; then
    print_error "docker-compose is not installed."
    exit 1
fi

print_success "Prerequisites check completed"

# Verify existing infrastructure
print_status "🔍 Verifying existing infrastructure..."

# Check if external network exists
if ! docker network ls | grep -q "finxPortal"; then
    print_error "External network 'finxPortal' not found!"
    echo "Please create the network with: docker network create finxPortal"
    exit 1
fi

# Check if PostgreSQL container is running
if ! docker ps | grep -q "finx-postgres"; then
    print_warning "PostgreSQL container 'finx-postgres' not found or not running"
    echo "Expected: container named 'finx-postgres' on network 'finxPortal'"
fi

# Check if Redis container is running  
if ! docker ps | grep -q "redis_dms"; then
    print_warning "Redis container 'redis_dms' not found or not running"
    echo "Expected: container named 'redis_dms' on network 'finxPortal'"
fi

# Check if Paperless container is running
if ! docker ps | grep -q "paperless"; then
    print_warning "Paperless container 'paperless' not found or not running"
    echo "Expected: container named 'paperless' on network 'finxPortal'"
fi

print_success "Infrastructure verification completed"

# Stop existing containers if they exist
print_status "🛑 Stopping existing FinFinity Portal containers..."
docker stop finfinity_portal_backend finfinity_portal_frontend 2>/dev/null || true
docker rm finfinity_portal_backend finfinity_portal_frontend 2>/dev/null || true

# Clean up unused Docker objects
print_status "🧹 Cleaning up unused Docker objects..."
docker system prune -f

# Build and start new containers
print_status "🔨 Building and starting FinFinity Portal containers..."
docker-compose -f docker-compose.existing-infra.yml up --build -d

# Check if build was successful
if [ $? -ne 0 ]; then
    print_error "Docker build/deployment failed!"
    exit 1
fi

# Wait for containers to be ready
print_status "⏳ Waiting for containers to start..."
sleep 20

# Check container status
print_status "🔍 Checking container status..."

BACKEND_RUNNING=false
FRONTEND_RUNNING=false

if docker ps | grep -q "finfinity_portal_backend"; then
    print_success "Backend container is running"
    BACKEND_RUNNING=true
else
    print_error "Backend container is not running"
fi

if docker ps | grep -q "finfinity_portal_frontend"; then
    print_success "Frontend container is running"
    FRONTEND_RUNNING=true
else
    print_error "Frontend container is not running"
fi

# Test connectivity
print_status "🧪 Testing application connectivity..."

# Test backend health
if curl -f -s http://localhost:8080/api/health > /dev/null 2>&1; then
    print_success "Backend health check passed"
else
    print_warning "Backend health check failed (may need more time to start)"
fi

# Test frontend
if curl -f -s http://localhost:3000 > /dev/null 2>&1; then
    print_success "Frontend connectivity check passed"
else
    print_warning "Frontend connectivity check failed (may need more time to start)"
fi

# Display results
echo
if [ "$BACKEND_RUNNING" = true ] && [ "$FRONTEND_RUNNING" = true ]; then
    print_success "🎉 FinFinity Portal deployment completed successfully!"
    echo
    echo -e "${BLUE}🌐 Application URLs:${NC}"
    echo "   - Frontend (Development): http://localhost:3000"
    echo "   - Backend API: http://localhost:8080"
    echo "   - Backend Health: http://localhost:8080/api/health"
    echo
    echo -e "${BLUE}🔗 Production URLs (via Apache proxy):${NC}"
    echo "   - Frontend: https://portal.finfinity.co.in"
    echo "   - Backend API: https://portal.finfinity.co.in:8443"
    echo "   - Paperless: https://paperless.finfinity.co.in"
    echo
    echo -e "${BLUE}🏗️ Infrastructure Status:${NC}"
    echo "   - PostgreSQL: finx-postgres:5432 (external)"
    echo "   - Redis: redis_dms:6379 (external)"
    echo "   - Paperless: paperless:8000 (external)"
    echo "   - Frontend: finfinity_portal_frontend:3000 (containerized)"
    echo "   - Backend: finfinity_portal_backend:80→8080 (containerized)"
    echo
    echo -e "${BLUE}🔧 Useful commands:${NC}"
    echo "   - View backend logs: docker logs -f finfinity_portal_backend"
    echo "   - View frontend logs: docker logs -f finfinity_portal_frontend"
    echo "   - Access backend container: docker exec -it finfinity_portal_backend bash"
    echo "   - Access frontend container: docker exec -it finfinity_portal_frontend sh"
    echo "   - Stop containers: docker-compose -f docker-compose.existing-infra.yml down"
    echo "   - View all containers: docker ps"
    echo "   - Check network: docker network inspect finxPortal"
else
    print_error "❌ Deployment completed with issues!"
    echo
    echo "Troubleshooting steps:"
    echo "1. Check container logs:"
    echo "   docker logs finfinity_portal_backend"
    echo "   docker logs finfinity_portal_frontend"
    echo "2. Check container status:"
    echo "   docker ps -a"
    echo "3. Check network connectivity:"
    echo "   docker network inspect finxPortal"
    echo "4. Check existing infrastructure (PostgreSQL, Redis, Paperless)"
    exit 1
fi 