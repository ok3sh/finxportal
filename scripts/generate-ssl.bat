@echo off
REM Generate SSL certificates for local development
REM Usage: scripts\generate-ssl.bat [domain]

setlocal enabledelayedexpansion

set DOMAIN=%1
if "%DOMAIN%"=="" set DOMAIN=localhost

set SSL_DIR=docker\nginx\ssl

echo 🔐 Generating SSL certificates for %DOMAIN%...

REM Create SSL directory if it doesn't exist
if not exist "%SSL_DIR%" mkdir "%SSL_DIR%"

REM Check if OpenSSL is available
openssl version >nul 2>&1
if errorlevel 1 (
    echo ❌ Error: OpenSSL is not installed or not in PATH
    echo Please install OpenSSL:
    echo   - Download from: https://slproweb.com/products/Win32OpenSSL.html
    echo   - Or use chocolatey: choco install openssl
    echo   - Or use Git Bash which includes OpenSSL
    exit /b 1
)

REM Generate private key
echo Generating private key...
openssl genrsa -out "%SSL_DIR%\nginx.key" 2048

REM Generate certificate signing request
echo Generating certificate signing request...
openssl req -new -key "%SSL_DIR%\nginx.key" -out "%SSL_DIR%\nginx.csr" -subj "/C=IN/ST=State/L=City/O=FinFinity/OU=IT/CN=%DOMAIN%"

REM Generate self-signed certificate
echo Generating self-signed certificate...
openssl x509 -req -days 365 -in "%SSL_DIR%\nginx.csr" -signkey "%SSL_DIR%\nginx.key" -out "%SSL_DIR%\nginx.crt"

REM Clean up CSR file
del "%SSL_DIR%\nginx.csr"

echo.
echo ✅ SSL certificates generated successfully!
echo 📍 Certificate location: %SSL_DIR%\
echo 🌐 You can now access your application at: https://%DOMAIN%:8443
echo.
echo ⚠️  Note: This is a self-signed certificate. Your browser will show a security warning.
echo    For production, use proper SSL certificates from a trusted CA.
echo.
echo 🔧 To trust this certificate in your browser:
echo    1. Open https://%DOMAIN%:8443
echo    2. Click 'Advanced' when you see the security warning
echo    3. Click 'Proceed to %DOMAIN% (unsafe)'
echo    4. Or add the certificate to your browser's trusted certificates
echo.
echo 💡 Alternative: Use Git Bash to run the .sh version:
echo    bash scripts/generate-ssl.sh %DOMAIN%

pause 