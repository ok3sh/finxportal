@echo off
REM FinFinity Portal Docker Deployment Script for Windows
REM Usage: scripts\deploy.bat [production|development|build-only|stop|logs|status]

setlocal enabledelayedexpansion

REM Default mode
set MODE=%1
if "%MODE%"=="" set MODE=production

echo === FinFinity Portal Docker Deployment ===
echo Mode: %MODE%

REM Check if Docker and Docker Compose are installed
docker --version >nul 2>&1
if errorlevel 1 (
    echo Error: Docker is not installed
    exit /b 1
)

docker-compose --version >nul 2>&1
if errorlevel 1 (
    echo Error: Docker Compose is not installed
    exit /b 1
)

REM Function to check if .env file exists
if not exist .env (
    echo Warning: .env file not found. Creating from .env.example...
    if exist .env.example (
        copy .env.example .env
        echo Please update .env file with your configuration before proceeding.
        pause
    ) else (
        echo Error: .env.example file not found
        exit /b 1
    )
)

if "%MODE%"=="production" goto production
if "%MODE%"=="development" goto development
if "%MODE%"=="build-only" goto build-only
if "%MODE%"=="stop" goto stop
if "%MODE%"=="logs" goto logs
if "%MODE%"=="status" goto status
goto usage

:production
echo Starting production deployment...
call :build_images
call :start_services production
call :wait_for_services
call :post_deployment_tasks production
call :show_urls production
goto end

:development
echo Starting development deployment...
call :build_images development
call :start_services development
call :wait_for_services development
call :post_deployment_tasks development
call :show_urls development
goto end

:build-only
echo Building images only...
call :build_images
echo Build completed. Use 'docker-compose up -d' to start services.
goto end

:stop
echo Stopping all services...
docker-compose down
docker-compose -f docker-compose.dev.yml down 2>nul
echo All services stopped
goto end

:logs
echo Recent application logs:
docker-compose logs --tail=20 app
goto end

:status
call :show_urls production
goto end

:build_images
echo Building Docker images...
if "%~1"=="development" (
    docker-compose -f docker-compose.dev.yml build --no-cache
) else (
    docker-compose build --no-cache
)
echo Docker images built successfully
exit /b 0

:start_services
echo Starting Docker services...
if "%~1"=="development" (
    docker-compose -f docker-compose.dev.yml up -d
) else (
    docker-compose up -d
)
echo Services started successfully
exit /b 0

:wait_for_services
echo Waiting for services to be ready...

REM Wait for database
echo Waiting for database...
set timeout=60
:db_wait
docker-compose exec -T db pg_isready -U laravel >nul 2>&1
if errorlevel 1 (
    if !timeout! LEQ 0 (
        echo Error: Database failed to start within 60 seconds
        exit /b 1
    )
    set /a timeout-=2
    timeout /t 2 /nobreak >nul
    goto db_wait
)

REM Wait for Redis
echo Waiting for Redis...
set timeout=30
:redis_wait
docker-compose exec -T redis redis-cli ping 2>nul | findstr "PONG" >nul
if errorlevel 1 (
    if !timeout! LEQ 0 (
        echo Error: Redis failed to start within 30 seconds
        exit /b 1
    )
    set /a timeout-=2
    timeout /t 2 /nobreak >nul
    goto redis_wait
)

echo All services are ready
exit /b 0

:post_deployment_tasks
echo Running post-deployment tasks...

REM Generate application key if needed
docker-compose exec -T app php artisan key:generate --force

REM Run migrations
docker-compose exec -T app php artisan migrate --force

REM Create storage link
docker-compose exec -T app php artisan storage:link

REM Clear and cache configuration (production only)
if "%~1"=="production" (
    docker-compose exec -T app php artisan config:cache
    docker-compose exec -T app php artisan route:cache
    docker-compose exec -T app php artisan view:cache
) else (
    docker-compose exec -T app php artisan config:clear
    docker-compose exec -T app php artisan route:clear
    docker-compose exec -T app php artisan view:clear
)

echo Post-deployment tasks completed
exit /b 0

:show_urls
echo === Service URLs ===
echo Application: https://localhost:8443 (HTTPS)
echo HTTP Redirect: http://localhost:8080 -^> HTTPS

if "%~1"=="development" (
    echo pgAdmin: http://localhost:8081
    echo Redis Commander: http://localhost:8082
    echo MailHog: http://localhost:8025
)

echo.
echo === Container Status ===
docker-compose ps
exit /b 0

:usage
echo Usage: %0 [production^|development^|build-only^|stop^|logs^|status]
echo.
echo   production   - Deploy for production use
echo   development  - Deploy with development tools
echo   build-only   - Only build Docker images
echo   stop         - Stop all services
echo   logs         - Show application logs
echo   status       - Show service status and URLs
exit /b 1

:end
echo Deployment completed successfully! 