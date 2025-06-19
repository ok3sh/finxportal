@echo off
setlocal enabledelayedexpansion

echo 🚀 FinFinity Portal Deployment (Existing Infrastructure)
echo.

:: Check if Docker is running
echo 🔍 Checking prerequisites...
docker info >nul 2>&1
if errorlevel 1 (
    echo ❌ Docker is not running. Please start Docker Desktop.
    pause
    exit /b 1
)

:: Check if docker-compose is available
docker-compose --version >nul 2>&1
if errorlevel 1 (
    echo ❌ docker-compose is not available.
    pause
    exit /b 1
)

echo ✅ Prerequisites check completed
echo.

:: Verify existing infrastructure
echo 🔍 Verifying existing infrastructure...

:: Check if external network exists
docker network ls | findstr "finxPortal" >nul
if errorlevel 1 (
    echo ❌ External network 'finxPortal' not found!
    echo Please create the network with: docker network create finxPortal
    pause
    exit /b 1
)

:: Check if PostgreSQL container is running
docker ps | findstr "finx-postgres" >nul
if errorlevel 1 (
    echo ⚠️ PostgreSQL container 'finx-postgres' not found or not running
    echo Expected: container named 'finx-postgres' on network 'finxPortal'
)

:: Check if Redis container is running
docker ps | findstr "redis_dms" >nul
if errorlevel 1 (
    echo ⚠️ Redis container 'redis_dms' not found or not running
    echo Expected: container named 'redis_dms' on network 'finxPortal'
)

:: Check if Paperless container is running
docker ps | findstr "paperless" >nul
if errorlevel 1 (
    echo ⚠️ Paperless container 'paperless' not found or not running
    echo Expected: container named 'paperless' on network 'finxPortal'
)

echo ✅ Infrastructure verification completed
echo.

:: Stop existing containers if they exist
echo 🛑 Stopping existing FinFinity Portal containers...
docker stop finfinity_portal_backend finfinity_portal_frontend >nul 2>&1
docker rm finfinity_portal_backend finfinity_portal_frontend >nul 2>&1

:: Clean up unused Docker objects
echo 🧹 Cleaning up unused Docker objects...
docker system prune -f

:: Build and start new containers
echo 🔨 Building and starting FinFinity Portal containers...
docker-compose -f docker-compose.existing-infra.yml up --build -d

if errorlevel 1 (
    echo ❌ Docker build/deployment failed!
    pause
    exit /b 1
)

:: Wait for containers to be ready
echo ⏳ Waiting for containers to start...
timeout /t 20 /nobreak >nul

:: Check container status
echo 🔍 Checking container status...

set backendRunning=false
set frontendRunning=false

docker ps | findstr "finfinity_portal_backend" >nul
if not errorlevel 1 (
    echo ✅ Backend container is running
    set backendRunning=true
) else (
    echo ❌ Backend container is not running
)

docker ps | findstr "finfinity_portal_frontend" >nul
if not errorlevel 1 (
    echo ✅ Frontend container is running
    set frontendRunning=true
) else (
    echo ❌ Frontend container is not running
)

:: Test connectivity
echo 🧪 Testing application connectivity...

:: Test backend health (simple check)
curl -f -s http://localhost:8080/api/health >nul 2>&1
if not errorlevel 1 (
    echo ✅ Backend health check passed
) else (
    echo ⚠️ Backend health check failed ^(may need more time to start^)
)

:: Test frontend (simple check)
curl -f -s http://localhost:3000 >nul 2>&1
if not errorlevel 1 (
    echo ✅ Frontend connectivity check passed
) else (
    echo ⚠️ Frontend connectivity check failed ^(may need more time to start^)
)

echo.

:: Display results
if "%backendRunning%"=="true" if "%frontendRunning%"=="true" (
    echo ✅ 🎉 FinFinity Portal deployment completed successfully!
    echo.
    echo 🌐 Application URLs:
    echo    - Frontend ^(Development^): http://localhost:3000
    echo    - Backend API: http://localhost:8080
    echo    - Backend Health: http://localhost:8080/api/health
    echo.
    echo 🔗 Production URLs ^(via Apache proxy^):
    echo    - Frontend: https://portal.finfinity.co.in
    echo    - Backend API: https://portal.finfinity.co.in:8443
    echo    - Paperless: https://paperless.finfinity.co.in
    echo.
    echo 🏗️ Infrastructure Status:
    echo    - PostgreSQL: finx-postgres:5432 ^(external^)
    echo    - Redis: redis_dms:6379 ^(external^)
    echo    - Paperless: paperless:8000 ^(external^)
    echo    - Frontend: finfinity_portal_frontend:3000 ^(containerized^)
    echo    - Backend: finfinity_portal_backend:80→8080 ^(containerized^)
    echo.
    echo 🔧 Useful commands:
    echo    - View backend logs: docker logs -f finfinity_portal_backend
    echo    - View frontend logs: docker logs -f finfinity_portal_frontend
    echo    - Access backend container: docker exec -it finfinity_portal_backend bash
    echo    - Access frontend container: docker exec -it finfinity_portal_frontend sh
    echo    - Stop containers: docker-compose -f docker-compose.existing-infra.yml down
    echo    - View all containers: docker ps
    echo    - Check network: docker network inspect finxPortal
) else (
    echo ❌ Deployment completed with issues!
    echo.
    echo Troubleshooting steps:
    echo 1. Check container logs:
    echo    docker logs finfinity_portal_backend
    echo    docker logs finfinity_portal_frontend
    echo 2. Check container status:
    echo    docker ps -a
    echo 3. Check network connectivity:
    echo    docker network inspect finxPortal
    echo 4. Check existing infrastructure ^(PostgreSQL, Redis, Paperless^)
    pause
    exit /b 1
)

echo.
echo Deployment completed. Press any key to exit...
pause >nul 