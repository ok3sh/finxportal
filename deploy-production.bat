@echo off
echo 🚀 Starting FinFinity Portal Production Deployment...

REM Check if .env exists
if not exist .env (
    echo ❌ .env file not found!
    echo Please ensure your .env file exists in the project root.
    echo Your existing .env file should work perfectly for Docker.
    pause
    exit /b 1
)

REM Check if certificates exist
if not exist certs\localhost+2.pem (
    echo ❌ SSL certificate localhost+2.pem not found in certs\ directory!
    pause
    exit /b 1
)

if not exist certs\localhost+2-key.pem (
    echo ❌ SSL certificate localhost+2-key.pem not found in certs\ directory!
    pause
    exit /b 1
)

echo ✅ Environment files and certificates found

REM Stop existing containers
echo 🛑 Stopping existing containers...
docker-compose down

REM Build and start the application
echo 🔨 Building and starting containers...
docker-compose up --build -d

REM Wait for container to be ready
echo ⏳ Waiting for application to start...
timeout /t 15 /nobreak >nul

REM Check if container is running
docker ps -q -f name=finfinity_portal_app >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ Container is running!
    echo.
    echo 🌐 Application URLs:
    echo    - Main App: https://localhost:3000 ^(Vite dev server^)
    echo    - Laravel API: http://localhost:8000
    echo.
    echo 📋 Next steps:
    echo    1. Your Laravel app is now containerized
    echo    2. It connects to your external PostgreSQL ^(91.108.110.65:5432^)
    echo    3. It uses your existing Redis container ^(127.0.0.1:6379^)
    echo    4. All Microsoft Graph settings remain unchanged
    echo.
    echo 🔧 To view logs: docker logs -f finfinity_portal_app
    echo 🔧 To access container: docker exec -it finfinity_portal_app bash
) else (
    echo ❌ Container failed to start. Check logs with: docker logs finfinity_portal_app
    pause
    exit /b 1
)

pause 