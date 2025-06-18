#!/bin/bash

echo "🚀 Starting FinFinity Portal Production Deployment..."

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

# Check if .env exists
if [ ! -f .env ]; then
    print_error ".env file not found!"
    echo "Please ensure your .env file exists in the project root."
    echo "Your existing .env file should work perfectly for Docker."
    exit 1
fi

# Check if certificates exist
if [ ! -f certs/localhost+2.pem ]; then
    print_error "SSL certificate localhost+2.pem not found in certs/ directory!"
    exit 1
fi

if [ ! -f certs/localhost+2-key.pem ]; then
    print_error "SSL certificate localhost+2-key.pem not found in certs/ directory!"
    exit 1
fi

print_success "Environment files and certificates found"

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    print_error "Docker is not running. Please start Docker service."
    exit 1
fi

# Check if docker-compose is available
if ! command -v docker-compose > /dev/null 2>&1; then
    print_error "docker-compose is not installed. Please install docker-compose."
    exit 1
fi

# Stop existing containers
print_status "🛑 Stopping existing containers..."
docker-compose down

# Prune unused Docker objects to free space
print_status "🧹 Cleaning up unused Docker objects..."
docker system prune -f

# Build and start the application
print_status "🔨 Building and starting containers..."
docker-compose up --build -d

# Check if build was successful
if [ $? -ne 0 ]; then
    print_error "Docker build failed!"
    exit 1
fi

# Wait for container to be ready
print_status "⏳ Waiting for application to start..."
sleep 15

# Check if container is running
if docker ps -q -f name=finfinity_portal_app > /dev/null 2>&1; then
    print_success "Container is running!"
    echo
    echo -e "${BLUE}🌐 Application URLs:${NC}"
    echo "   - Main App: https://localhost:3000 (Vite dev server)"
    echo "   - Laravel API: http://localhost:8000"
    echo
    echo -e "${BLUE}📋 Next steps:${NC}"
    echo "   1. Your Laravel app is now containerized"
    echo "   2. It connects to your external PostgreSQL (91.108.110.65:5432)"
    echo "   3. It uses your existing Redis container (127.0.0.1:6379)"
    echo "   4. All Microsoft Graph settings remain unchanged"
    echo
    echo -e "${BLUE}🔧 Useful commands:${NC}"
    echo "   - View logs: docker logs -f finfinity_portal_app"
    echo "   - Access container: docker exec -it finfinity_portal_app bash"
    echo "   - Stop container: docker-compose down"
    echo "   - View all containers: docker ps"
    echo
    print_success "Deployment completed successfully!"
else
    print_error "Container failed to start. Check logs with: docker logs finfinity_portal_app"
    echo
    echo "Troubleshooting steps:"
    echo "1. Check container logs: docker logs finfinity_portal_app"
    echo "2. Check compose logs: docker-compose logs"
    echo "3. Verify .env configuration"
    echo "4. Check SSL certificate paths"
    exit 1
fi 