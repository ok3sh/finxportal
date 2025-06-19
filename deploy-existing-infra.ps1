# FinFinity Portal Deployment Script for Windows (Existing Infrastructure)
# PowerShell script to deploy frontend and backend containers

Write-Host "🚀 FinFinity Portal Deployment (Existing Infrastructure)" -ForegroundColor Blue

# Function to print colored output
function Write-Status($Message) {
    Write-Host $Message -ForegroundColor Blue
}

function Write-Success($Message) {
    Write-Host "✅ $Message" -ForegroundColor Green
}

function Write-Error($Message) {
    Write-Host "❌ $Message" -ForegroundColor Red
}

function Write-Warning($Message) {
    Write-Host "⚠️ $Message" -ForegroundColor Yellow
}

# Check prerequisites
Write-Status "🔍 Checking prerequisites..."

# Check if Docker is running
try {
    $null = docker info 2>$null
    if ($LASTEXITCODE -ne 0) {
        Write-Error "Docker is not running. Please start Docker Desktop."
        exit 1
    }
} catch {
    Write-Error "Docker is not available. Please install Docker Desktop."
    exit 1
}

# Check if docker-compose is available
try {
    $null = docker-compose --version 2>$null
    if ($LASTEXITCODE -ne 0) {
        Write-Error "docker-compose is not available."
        exit 1
    }
} catch {
    Write-Error "docker-compose is not installed."
    exit 1
}

Write-Success "Prerequisites check completed"

# Verify existing infrastructure
Write-Status "🔍 Verifying existing infrastructure..."

# Check if external network exists
$networkExists = docker network ls | Select-String "finxPortal"
if (-not $networkExists) {
    Write-Error "External network 'finxPortal' not found!"
    Write-Host "Please create the network with: docker network create finxPortal"
    exit 1
}

# Check if PostgreSQL container is running
$postgresRunning = docker ps | Select-String "finx-postgres"
if (-not $postgresRunning) {
    Write-Warning "PostgreSQL container 'finx-postgres' not found or not running"
    Write-Host "Expected: container named 'finx-postgres' on network 'finxPortal'"
}

# Check if Redis container is running  
$redisRunning = docker ps | Select-String "redis_dms"
if (-not $redisRunning) {
    Write-Warning "Redis container 'redis_dms' not found or not running"
    Write-Host "Expected: container named 'redis_dms' on network 'finxPortal'"
}

# Check if Paperless container is running
$paperlessRunning = docker ps | Select-String "paperless"
if (-not $paperlessRunning) {
    Write-Warning "Paperless container 'paperless' not found or not running"
    Write-Host "Expected: container named 'paperless' on network 'finxPortal'"
}

Write-Success "Infrastructure verification completed"

# Stop existing containers if they exist
Write-Status "🛑 Stopping existing FinFinity Portal containers..."
try {
    docker stop finfinity_portal_backend finfinity_portal_frontend 2>$null
    docker rm finfinity_portal_backend finfinity_portal_frontend 2>$null
} catch {
    # Containers may not exist, continue
}

# Clean up unused Docker objects
Write-Status "🧹 Cleaning up unused Docker objects..."
docker system prune -f

# Build and start new containers
Write-Status "🔨 Building and starting FinFinity Portal containers..."
docker-compose -f docker-compose.existing-infra.yml up --build -d

# Check if build was successful
if ($LASTEXITCODE -ne 0) {
    Write-Error "Docker build/deployment failed!"
    exit 1
}

# Wait for containers to be ready
Write-Status "⏳ Waiting for containers to start..."
Start-Sleep -Seconds 20

# Check container status
Write-Status "🔍 Checking container status..."

$backendRunning = $false
$frontendRunning = $false

$backendContainer = docker ps | Select-String "finfinity_portal_backend"
if ($backendContainer) {
    Write-Success "Backend container is running"
    $backendRunning = $true
} else {
    Write-Error "Backend container is not running"
}

$frontendContainer = docker ps | Select-String "finfinity_portal_frontend"
if ($frontendContainer) {
    Write-Success "Frontend container is running"
    $frontendRunning = $true
} else {
    Write-Error "Frontend container is not running"
}

# Test connectivity
Write-Status "🧪 Testing application connectivity..."

# Test backend health
try {
    $response = Invoke-WebRequest -Uri "http://localhost:8080/api/health" -UseBasicParsing -TimeoutSec 10 -ErrorAction SilentlyContinue
    if ($response.StatusCode -eq 200) {
        Write-Success "Backend health check passed"
    } else {
        Write-Warning "Backend health check failed (may need more time to start)"
    }
} catch {
    Write-Warning "Backend health check failed (may need more time to start)"
}

# Test frontend
try {
    $response = Invoke-WebRequest -Uri "http://localhost:3000" -UseBasicParsing -TimeoutSec 10 -ErrorAction SilentlyContinue
    if ($response.StatusCode -eq 200) {
        Write-Success "Frontend connectivity check passed"
    } else {
        Write-Warning "Frontend connectivity check failed (may need more time to start)"
    }
} catch {
    Write-Warning "Frontend connectivity check failed (may need more time to start)"
}

# Display results
Write-Host ""
if ($backendRunning -and $frontendRunning) {
    Write-Success "🎉 FinFinity Portal deployment completed successfully!"
    Write-Host ""
    Write-Host "🌐 Application URLs:" -ForegroundColor Blue
    Write-Host "   - Frontend (Development): http://localhost:3000"
    Write-Host "   - Backend API: http://localhost:8080"
    Write-Host "   - Backend Health: http://localhost:8080/api/health"
    Write-Host ""
    Write-Host "🔗 Production URLs (via Apache proxy):" -ForegroundColor Blue
    Write-Host "   - Frontend: https://portal.finfinity.co.in"
    Write-Host "   - Backend API: https://portal.finfinity.co.in:8443"
    Write-Host "   - Paperless: https://paperless.finfinity.co.in"
    Write-Host ""
    Write-Host "🏗️ Infrastructure Status:" -ForegroundColor Blue
    Write-Host "   - PostgreSQL: finx-postgres:5432 (external)"
    Write-Host "   - Redis: redis_dms:6379 (external)"
    Write-Host "   - Paperless: paperless:8000 (external)"
    Write-Host "   - Frontend: finfinity_portal_frontend:3000 (containerized)"
    Write-Host "   - Backend: finfinity_portal_backend:80→8080 (containerized)"
    Write-Host ""
    Write-Host "🔧 Useful commands:" -ForegroundColor Blue
    Write-Host "   - View backend logs: docker logs -f finfinity_portal_backend"
    Write-Host "   - View frontend logs: docker logs -f finfinity_portal_frontend"
    Write-Host "   - Access backend container: docker exec -it finfinity_portal_backend bash"
    Write-Host "   - Access frontend container: docker exec -it finfinity_portal_frontend sh"
    Write-Host "   - Stop containers: docker-compose -f docker-compose.existing-infra.yml down"
    Write-Host "   - View all containers: docker ps"
    Write-Host "   - Check network: docker network inspect finxPortal"
} else {
    Write-Error "❌ Deployment completed with issues!"
    Write-Host ""
    Write-Host "Troubleshooting steps:"
    Write-Host "1. Check container logs:"
    Write-Host "   docker logs finfinity_portal_backend"
    Write-Host "   docker logs finfinity_portal_frontend"
    Write-Host "2. Check container status:"
    Write-Host "   docker ps -a"
    Write-Host "3. Check network connectivity:"
    Write-Host "   docker network inspect finxPortal"
    Write-Host "4. Check existing infrastructure (PostgreSQL, Redis, Paperless)"
    exit 1
} 