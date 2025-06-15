#!/bin/bash

# FinFinity Portal Docker Deployment Script
# Usage: ./scripts/deploy.sh [production|development|build-only]

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Default mode
MODE="${1:-production}"

echo -e "${GREEN}=== FinFinity Portal Docker Deployment ===${NC}"
echo -e "${YELLOW}Mode: $MODE${NC}"

# Check if Docker and Docker Compose are installed
if ! command -v docker &> /dev/null; then
    echo -e "${RED}Error: Docker is not installed${NC}"
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}Error: Docker Compose is not installed${NC}"
    exit 1
fi

# Function to check if .env file exists
check_env_file() {
    if [ ! -f .env ]; then
        echo -e "${YELLOW}Warning: .env file not found. Creating from .env.example...${NC}"
        if [ -f .env.example ]; then
            cp .env.example .env
            echo -e "${YELLOW}Please update .env file with your configuration before proceeding.${NC}"
            echo -e "${YELLOW}Press any key to continue after updating .env...${NC}"
            read -n 1
        else
            echo -e "${RED}Error: .env.example file not found${NC}"
            exit 1
        fi
    fi
}

# Function to build Docker images
build_images() {
    echo -e "${GREEN}Building Docker images...${NC}"
    
    if [ "$MODE" = "development" ]; then
        docker-compose -f docker-compose.dev.yml build --no-cache
    else
        docker-compose build --no-cache
    fi
    
    echo -e "${GREEN}Docker images built successfully${NC}"
}

# Function to start services
start_services() {
    echo -e "${GREEN}Starting Docker services...${NC}"
    
    if [ "$MODE" = "development" ]; then
        docker-compose -f docker-compose.dev.yml up -d
    else
        docker-compose up -d
    fi
    
    echo -e "${GREEN}Services started successfully${NC}"
}

# Function to wait for services to be ready
wait_for_services() {
    echo -e "${GREEN}Waiting for services to be ready...${NC}"
    
    # Wait for database
    echo -e "${YELLOW}Waiting for database...${NC}"
    timeout=60
    while ! docker-compose exec -T db pg_isready -U laravel; do
        sleep 2
        timeout=$((timeout - 2))
        if [ $timeout -le 0 ]; then
            echo -e "${RED}Error: Database failed to start within 60 seconds${NC}"
            exit 1
        fi
    done
    
    # Wait for Redis
    echo -e "${YELLOW}Waiting for Redis...${NC}"
    timeout=30
    while ! docker-compose exec -T redis redis-cli ping | grep -q PONG; do
        sleep 2
        timeout=$((timeout - 2))
        if [ $timeout -le 0 ]; then
            echo -e "${RED}Error: Redis failed to start within 30 seconds${NC}"
            exit 1
        fi
    done
    
    echo -e "${GREEN}All services are ready${NC}"
}

# Function to run post-deployment tasks
post_deployment_tasks() {
    echo -e "${GREEN}Running post-deployment tasks...${NC}"
    
    # Generate application key if needed
    docker-compose exec -T app php artisan key:generate --force
    
    # Run migrations
    docker-compose exec -T app php artisan migrate --force
    
    # Create storage link
    docker-compose exec -T app php artisan storage:link
    
    # Clear and cache configuration (production only)
    if [ "$MODE" = "production" ]; then
        docker-compose exec -T app php artisan config:cache
        docker-compose exec -T app php artisan route:cache
        docker-compose exec -T app php artisan view:cache
    else
        docker-compose exec -T app php artisan config:clear
        docker-compose exec -T app php artisan route:clear
        docker-compose exec -T app php artisan view:clear
    fi
    
    echo -e "${GREEN}Post-deployment tasks completed${NC}"
}

# Function to show service URLs
show_urls() {
    echo -e "${GREEN}=== Service URLs ===${NC}"
    echo -e "Application: ${YELLOW}https://localhost:8443${NC} (HTTPS)"
    echo -e "HTTP Redirect: ${YELLOW}http://localhost:8080${NC} → HTTPS"
    
    if [ "$MODE" = "development" ]; then
        echo -e "pgAdmin: ${YELLOW}http://localhost:8081${NC}"
        echo -e "Redis Commander: ${YELLOW}http://localhost:8082${NC}"
        echo -e "MailHog: ${YELLOW}http://localhost:8025${NC}"
    fi
    
    echo ""
    echo -e "${GREEN}=== Container Status ===${NC}"
    docker-compose ps
}

# Function to show logs
show_logs() {
    echo -e "${GREEN}Recent application logs:${NC}"
    docker-compose logs --tail=20 app
}

# Main deployment logic
case $MODE in
    "production")
        echo -e "${GREEN}Starting production deployment...${NC}"
        check_env_file
        build_images
        start_services
        wait_for_services
        post_deployment_tasks
        show_urls
        ;;
    "development")
        echo -e "${GREEN}Starting development deployment...${NC}"
        check_env_file
        build_images
        start_services
        wait_for_services
        post_deployment_tasks
        show_urls
        ;;
    "build-only")
        echo -e "${GREEN}Building images only...${NC}"
        build_images
        echo -e "${GREEN}Build completed. Use 'docker-compose up -d' to start services.${NC}"
        ;;
    "stop")
        echo -e "${YELLOW}Stopping all services...${NC}"
        docker-compose down
        docker-compose -f docker-compose.dev.yml down 2>/dev/null || true
        echo -e "${GREEN}All services stopped${NC}"
        ;;
    "logs")
        show_logs
        ;;
    "status")
        show_urls
        ;;
    *)
        echo -e "${RED}Usage: $0 [production|development|build-only|stop|logs|status]${NC}"
        echo ""
        echo "  production   - Deploy for production use"
        echo "  development  - Deploy with development tools"
        echo "  build-only   - Only build Docker images"
        echo "  stop         - Stop all services"
        echo "  logs         - Show application logs"
        echo "  status       - Show service status and URLs"
        exit 1
        ;;
esac

echo -e "${GREEN}Deployment completed successfully!${NC}" 