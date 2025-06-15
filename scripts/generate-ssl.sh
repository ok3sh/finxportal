#!/bin/bash

# Generate SSL certificates for local development
# Usage: ./scripts/generate-ssl.sh [domain]

DOMAIN="${1:-localhost}"
SSL_DIR="./docker/nginx/ssl"

echo "🔐 Generating SSL certificates for $DOMAIN..."

# Create SSL directory if it doesn't exist
mkdir -p "$SSL_DIR"

# Generate private key
echo "Generating private key..."
openssl genrsa -out "$SSL_DIR/nginx.key" 2048

# Generate certificate signing request
echo "Generating certificate signing request..."
openssl req -new -key "$SSL_DIR/nginx.key" -out "$SSL_DIR/nginx.csr" -subj "/C=IN/ST=State/L=City/O=FinFinity/OU=IT/CN=$DOMAIN"

# Generate self-signed certificate
echo "Generating self-signed certificate..."
openssl x509 -req -days 365 -in "$SSL_DIR/nginx.csr" -signkey "$SSL_DIR/nginx.key" -out "$SSL_DIR/nginx.crt"

# Set proper permissions
chmod 600 "$SSL_DIR/nginx.key"
chmod 644 "$SSL_DIR/nginx.crt"

# Clean up CSR file
rm "$SSL_DIR/nginx.csr"

echo "✅ SSL certificates generated successfully!"
echo "📍 Certificate location: $SSL_DIR/"
echo "🌐 You can now access your application at: https://$DOMAIN:8443"
echo ""
echo "⚠️  Note: This is a self-signed certificate. Your browser will show a security warning."
echo "   For production, use proper SSL certificates from a trusted CA."
echo ""
echo "🔧 To trust this certificate in your browser:"
echo "   1. Open https://$DOMAIN:8443"
echo "   2. Click 'Advanced' when you see the security warning"
echo "   3. Click 'Proceed to $DOMAIN (unsafe)'"
echo "   4. Or add the certificate to your browser's trusted certificates" 