#!/bin/bash

echo "🔐 Generating Secure Secret Keys for Environment Variables"
echo "========================================================"
echo ""

# Method 1: Using OpenSSL (if available)
if command -v openssl &> /dev/null; then
    echo "📝 Method 1: Using OpenSSL"
    echo "CSRF_SECRET="
    openssl rand -hex 16
    echo ""
    echo "SESSION_SECRET="
    openssl rand -hex 16
    echo ""
    echo "API_KEY="
    openssl rand -hex 32
    echo ""
    echo "JWT_SECRET="
    openssl rand -hex 32
    echo ""
    echo "ENCRYPTION_KEY="
    openssl rand -base64 24
    echo ""
    echo "========================================================"
    echo "✅ OpenSSL secrets generated successfully!"
    echo ""
fi

# Method 2: Using /dev/urandom (fallback)
echo "📝 Method 2: Using /dev/urandom (fallback)"
echo "CSRF_SECRET="
head -c 16 /dev/urandom | xxd -p
echo ""
echo "SESSION_SECRET="
head -c 16 /dev/urandom | xxd -p
echo ""
echo "API_KEY="
head -c 32 /dev/urandom | xxd -p
echo ""
echo "JWT_SECRET="
head -c 32 /dev/urandom | xxd -p
echo ""
echo "ENCRYPTION_KEY="
head -c 24 /dev/urandom | base64
echo ""

# Method 3: Using PHP (if available)
if command -v php &> /dev/null; then
    echo "📝 Method 3: Using PHP"
    php generate_secrets.php
fi

echo "========================================================"
echo "🔒 Security Notes:"
echo "- Keep these secrets secure and never share them"
echo "- Use different secrets for each environment (dev/staging/prod)"
echo "- Rotate secrets regularly in production"
echo "- Never commit .env files to version control"
echo ""
echo "💡 Recommended: Use the OpenSSL method for best security"
echo "   If OpenSSL is not available, use the PHP method"
echo ""
