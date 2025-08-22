<?php
/**
 * Secret Key Generator for Environment Variables
 * 
 * This script generates secure, random secrets for your .env file
 * Run this script to generate new secrets whenever you need them
 */

echo "🔐 Generating Secure Secret Keys for Environment Variables\n";
echo "========================================================\n\n";

// Generate CSRF Secret (32 characters)
$csrf_secret = bin2hex(random_bytes(16));
echo "CSRF_SECRET=\n";
echo $csrf_secret . "\n\n";

// Generate Session Secret (32 characters)
$session_secret = bin2hex(random_bytes(16));
echo "SESSION_SECRET=\n";
echo $session_secret . "\n\n";

// Generate API Key (64 characters)
$api_key = bin2hex(random_bytes(32));
echo "API_KEY=\n";
echo $api_key . "\n\n";

// Generate JWT Secret (64 characters)
$jwt_secret = bin2hex(random_bytes(32));
echo "JWT_SECRET=\n";
echo $jwt_secret . "\n\n";

// Generate Encryption Key (32 characters)
$encryption_key = base64_encode(random_bytes(24));
echo "ENCRYPTION_KEY=\n";
echo $encryption_key . "\n\n";

echo "========================================================\n";
echo "✅ All secrets generated successfully!\n\n";

echo "📝 Copy these to your .env file:\n";
echo "CSRF_SECRET=" . $csrf_secret . "\n";
echo "SESSION_SECRET=" . $session_secret . "\n";
echo "API_KEY=" . $api_key . "\n";
echo "JWT_SECRET=" . $jwt_secret . "\n";
echo "ENCRYPTION_KEY=" . $encryption_key . "\n\n";

echo "🔒 Security Notes:\n";
echo "- Keep these secrets secure and never share them\n";
echo "- Use different secrets for each environment (dev/staging/prod)\n";
echo "- Rotate secrets regularly in production\n";
echo "- Never commit .env files to version control\n";
?>
