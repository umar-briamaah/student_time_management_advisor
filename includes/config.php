<?php
// Load .env
$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, '"\'');
        $_ENV[$key] = $value;
    }
}

define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'student_time_advisor');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// Debug mode
define('DEBUG', filter_var($_ENV['DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN));

// For development, use localhost (will work with any port)
define('APP_URL', rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/'));

// Security settings
define('CSRF_SECRET', $_ENV['CSRF_SECRET'] ?? 'default-csrf-secret-change-in-production');
define('SESSION_SECRET', $_ENV['SESSION_SECRET'] ?? 'default-session-secret-change-in-production');