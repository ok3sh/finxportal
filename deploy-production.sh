#!/bin/bash

echo "🚀 Starting FinFinity Portal Production Deployment..."

# Check if .env exists
if [ ! -f .env ]; then
    echo "❌ .env file not found!"
    echo "Please ensure your .env file exists in the project root."
    echo "Your existing .env file should work perfectly for Docker."
    exit 1
fi

# Check if certificates exist
if [ ! -f certs/localhost+2.pem ] || [ ! -f certs/localhost+2-key.pem ]; then
    echo "❌ SSL certificates not found in certs/ directory!"
    echo "Please ensure localhost+2.pem and localhost+2-key.pem are in the certs folder."
    exit 1
fi

echo "✅ Environment files and certificates found"

# Stop existing containers
echo "🛑 Stopping existing containers..."
docker-compose down

# Build and start the application
echo "🔨 Building and starting containers..."
docker-compose up --build -d

# Wait for container to be ready
echo "⏳ Waiting for application to start..."
sleep 10

# Check if container is running
if [ "$(docker ps -q -f name=finfinity_portal_app)" ]; then
    echo "✅ Container is running!"
    echo ""
    echo "🌐 Application URLs:"
    echo "   - Laravel API: http://localhost:8000"
    echo "   - HTTPS: https://localhost:8443"
    echo ""
    echo "📋 Next steps:"
    echo "   1. Your Laravel app is now containerized"
    echo "   2. It connects to your external PostgreSQL (91.108.110.65:5432)"
    echo "   3. It uses your existing Redis container (127.0.0.1:6379)"
    echo "   4. All Microsoft Graph settings remain unchanged"
    echo ""
    echo "🔧 To view logs: docker logs -f finfinity_portal_app"
    echo "🔧 To access container: docker exec -it finfinity_portal_app bash"
else
    echo "❌ Container failed to start. Check logs with: docker logs finfinity_portal_app"
    exit 1
fi 