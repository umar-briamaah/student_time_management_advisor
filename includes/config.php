<?php
/**
 * Configuration file for Student Time Management Advisor
 * Loads environment variables and sets up application constants
 */

// Load .env file if it exists
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') === false) continue;
        if (strpos($line, '#') === 0) continue;
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        // Remove quotes if present
        if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
            $value = $matches[2];
        }
        
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

// Database Configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'student_time_advisor');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// Debug mode - default to true for development
define('DEBUG', filter_var($_ENV['DEBUG'] ?? 'true', FILTER_VALIDATE_BOOLEAN));

// Application URL
if (isset($_ENV['APP_URL'])) {
    define('APP_URL', rtrim($_ENV['APP_URL'], '/'));
} else {
    // For development server running from public directory
    $protocol = 'http';
    $host = 'localhost';
    $port = ':8000';
    $path = '';
    
    define('APP_URL', $protocol . '://' . $host . $port . $path);
}

// Environment
define('ENVIRONMENT', $_ENV['ENVIRONMENT'] ?? 'development');

// Security Settings
define('CSRF_SECRET', $_ENV['CSRF_SECRET'] ?? 'dev-csrf-secret-change-in-production');
define('SESSION_SECRET', $_ENV['SESSION_SECRET'] ?? 'dev-session-secret-change-in-production');
define('API_KEY', $_ENV['API_KEY'] ?? 'dev-api-key-change-in-production');
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'dev-jwt-secret-change-in-production');
define('ENCRYPTION_KEY', $_ENV['ENCRYPTION_KEY'] ?? 'dev-encryption-key-change-in-production');

// Mail Configuration
define('MAIL_HOST', $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com');
define('MAIL_PORT', (int)($_ENV['MAIL_PORT'] ?? 587));
define('MAIL_USER', $_ENV['MAIL_USER'] ?? 'your-email@gmail.com');
define('MAIL_PASS', $_ENV['MAIL_PASS'] ?? 'your-app-password');
define('MAIL_FROM', $_ENV['MAIL_FROM'] ?? 'noreply@studenttimeadvisor.com');
define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? 'Student Time Advisor');
define('MAIL_ENCRYPTION', $_ENV['MAIL_ENCRYPTION'] ?? 'tls');
define('MAIL_REPLY_TO', $_ENV['MAIL_REPLY_TO'] ?? 'support@studenttimeadvisor.com');

// System Configuration
define('TIMEZONE', $_ENV['TIMEZONE'] ?? 'UTC');
define('LOG_LEVEL', $_ENV['LOG_LEVEL'] ?? 'info');
define('LOG_FILE', $_ENV['LOG_FILE'] ?? '/var/log/student_time_advisor.log');

// Cron Configuration
define('CRON_REMINDER_INTERVAL', (int)($_ENV['CRON_REMINDER_INTERVAL'] ?? 15));
define('CRON_STREAK_TIME', $_ENV['CRON_STREAK_TIME'] ?? '00:10');

// Set timezone
date_default_timezone_set(TIMEZONE);

// Error reporting based on debug mode
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', LOG_FILE);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOG_FILE);
}

// Logging function
if (!function_exists('log_message')) {
    function log_message($level, $message, $context = []) {
        $log_levels = [
            'debug' => 0,
            'info' => 1,
            'warning' => 2,
            'error' => 3,
            'critical' => 4
        ];
        
        $current_level = $log_levels[LOG_LEVEL] ?? 1;
        $message_level = $log_levels[$level] ?? 1;
        
        if ($message_level >= $current_level) {
            $timestamp = date('Y-m-d H:i:s');
            $level_upper = strtoupper($level);
            $context_str = !empty($context) ? ' ' . json_encode($context) : '';
            $log_entry = "[$timestamp] [$level_upper] $message$context_str" . PHP_EOL;
            
            if (file_exists(dirname(LOG_FILE)) && is_writable(dirname(LOG_FILE))) {
                file_put_contents(LOG_FILE, $log_entry, FILE_APPEND | LOCK_EX);
            }
            
            if (DEBUG && $level === 'error') {
                error_log($log_entry);
            }
        }
    }
}

// Log application startup
log_message('info', 'Application started', [
    'environment' => ENVIRONMENT,
    'debug' => DEBUG,
    'timezone' => TIMEZONE,
    'app_url' => APP_URL
]);