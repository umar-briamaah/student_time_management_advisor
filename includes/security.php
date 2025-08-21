<?php
/**
 * Enhanced Security Functions for Student Time Management Advisor
 */

// Rate limiting for API endpoints
class RateLimiter {
    private static $limits = [
        'login' => ['attempts' => 5, 'window' => 300], // 5 attempts per 5 minutes
        'register' => ['attempts' => 3, 'window' => 600], // 3 attempts per 10 minutes
        'task_create' => ['attempts' => 20, 'window' => 300], // 20 tasks per 5 minutes
        'api' => ['attempts' => 100, 'window' => 300] // 100 requests per 5 minutes
    ];
    
    public static function check($action, $identifier = null) {
        if (!isset(self::$limits[$action])) {
            return true; // No limit set
        }
        
        $identifier = $identifier ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $limit = self::$limits[$action];
        $key = "rate_limit:{$action}:{$identifier}";
        
        // Check if limit exceeded
        $attempts = self::getAttempts($key);
        if ($attempts >= $limit['attempts']) {
            return false;
        }
        
        // Increment attempts
        self::incrementAttempts($key, $limit['window']);
        return true;
    }
    
    private static function getAttempts($key) {
        return $_SESSION['rate_limits'][$key]['count'] ?? 0;
    }
    
    private static function incrementAttempts($key, $window) {
        if (!isset($_SESSION['rate_limits'])) {
            $_SESSION['rate_limits'] = [];
        }
        
        $now = time();
        $data = $_SESSION['rate_limits'][$key] ?? ['count' => 0, 'reset_time' => $now + $window];
        
        if ($now > $data['reset_time']) {
            $data = ['count' => 1, 'reset_time' => $now + $window];
        } else {
            $data['count']++;
        }
        
        $_SESSION['rate_limits'][$key] = $data;
    }
}

// Enhanced input sanitization
class InputSanitizer {
    public static function sanitizeString($input, $maxLength = 255) {
        if (!is_string($input)) return '';
        
        $input = trim($input);
        $input = substr($input, 0, $maxLength);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        return $input;
    }
    
    public static function sanitizeEmail($email) {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
    }
    
    public static function sanitizeInteger($input, $min = null, $max = null) {
        $input = filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        $input = (int)$input;
        
        if ($min !== null && $input < $min) return $min;
        if ($max !== null && $input > $max) return $max;
        
        return $input;
    }
    
    public static function sanitizeDate($date) {
        $timestamp = strtotime($date);
        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
    }
    
    public static function sanitizeArray($array, $callback = null) {
        if (!is_array($array)) return [];
        
        $callback = $callback ?? [self::class, 'sanitizeString'];
        return array_map($callback, $array);
    }
}

// Security headers
class SecurityHeaders {
    public static function set() {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://unpkg.com https://cdn.jsdelivr.net; " .
               "style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdn.jsdelivr.net; " .
               "img-src 'self' data: https:; " .
               "font-src 'self' https://fonts.gstatic.com; " .
               "connect-src 'self'; " .
               "frame-ancestors 'none';";
        
        header("Content-Security-Policy: " . $csp);
        
        // Permissions Policy
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }
}

// CSRF protection enhancement
class CSRFProtection {
    private static $tokenLength = 32;
    
    public static function generateToken() {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(self::$tokenLength));
        }
        return $_SESSION['csrf'];
    }
    
    public static function verifyToken($token) {
        if (empty($_SESSION['csrf']) || empty($token)) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf'], $token);
    }
    
    public static function regenerateToken() {
        $_SESSION['csrf'] = bin2hex(random_bytes(self::$tokenLength));
        return $_SESSION['csrf'];
    }
    
    public static function getTokenField() {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf" value="' . htmlspecialchars($token) . '">';
    }
}

// Session security
class SessionSecurity {
    public static function secure() {
        // Set secure session parameters
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', 1);
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    public static function validate() {
        // Check for session hijacking
        if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            session_destroy();
            return false;
        }
        
        // Store user agent if not set
        if (!isset($_SESSION['user_agent'])) {
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        }
        
        return true;
    }
}

// File upload security
class FileSecurity {
    private static $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'application/pdf' => 'pdf',
        'text/plain' => 'txt'
    ];
    
    private static $maxSize = 5242880; // 5MB
    
    public static function validateUpload($file) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'No file uploaded'];
        }
        
        // Check file size
        if ($file['size'] > self::$maxSize) {
            return ['valid' => false, 'error' => 'File too large'];
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!array_key_exists($mimeType, self::$allowedTypes)) {
            return ['valid' => false, 'error' => 'File type not allowed'];
        }
        
        // Generate safe filename
        $extension = self::$allowedTypes[$mimeType];
        $filename = uniqid() . '_' . time() . '.' . $extension;
        
        return [
            'valid' => true,
            'filename' => $filename,
            'mime_type' => $mimeType,
            'extension' => $extension
        ];
    }
}

// SQL injection prevention enhancement
class SQLSecurity {
    public static function validateIdentifier($identifier) {
        // Only allow alphanumeric characters and underscores
        return preg_match('/^[a-zA-Z0-9_]+$/', $identifier);
    }
    
    public static function escapeLike($string) {
        // Escape LIKE wildcards
        return str_replace(['%', '_'], ['\\%', '\\_'], $string);
    }
    
    public static function buildWhereClause($conditions, $params = []) {
        $where = [];
        $values = [];
        
        foreach ($conditions as $field => $value) {
            if (self::validateIdentifier($field)) {
                if (is_array($value)) {
                    $placeholders = str_repeat('?,', count($value) - 1) . '?';
                    $where[] = "$field IN ($placeholders)";
                    $values = array_merge($values, $value);
                } else {
                    $where[] = "$field = ?";
                    $values[] = $value;
                }
            }
        }
        
        return [
            'clause' => implode(' AND ', $where),
            'params' => $values
        ];
    }
}

// Initialize security features
SessionSecurity::secure();
SecurityHeaders::set();

// Helper functions for backward compatibility
function secure_csrf_token() {
    return CSRFProtection::generateToken();
}

function verify_secure_csrf($token) {
    return CSRFProtection::verifyToken($token);
}

function sanitize_input($input, $type = 'string') {
    switch ($type) {
        case 'email':
            return InputSanitizer::sanitizeEmail($input);
        case 'int':
            return InputSanitizer::sanitizeInteger($input);
        case 'date':
            return InputSanitizer::sanitizeDate($input);
        default:
            return InputSanitizer::sanitizeString($input);
    }
}

function check_rate_limit($action, $identifier = null) {
    return RateLimiter::check($action, $identifier);
}
