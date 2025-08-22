<?php
/**
 * Security and middleware functions
 */

require_once __DIR__ . '/config.php';

// API Key validation
function validate_api_key($api_key) {
    if (empty($api_key)) {
        log_message('warning', 'API request without API key', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
            return false;
    }
    
    if (!hash_equals(API_KEY, $api_key)) {
        log_message('warning', 'Invalid API key provided', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'provided_key' => substr($api_key, 0, 8) . '...'
        ]);
        return false;
    }
    
    log_message('info', 'API key validated successfully', [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    return true;
}

// JWT Token functions
function generate_jwt_token($payload, $expiry = 3600) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload['iat'] = time();
    $payload['exp'] = time() + $expiry;
    
    $base64_header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64_payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
    
    $signature = hash_hmac('sha256', $base64_header . '.' . $base64_payload, JWT_SECRET, true);
    $base64_signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64_header . '.' . $base64_payload . '.' . $base64_signature;
}

function verify_jwt_token($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }
    
    list($header, $payload, $signature) = $parts;
    
    $expected_signature = hash_hmac('sha256', $header . '.' . $payload, JWT_SECRET, true);
    $expected_signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($expected_signature));
    
    if (!hash_equals($signature, $expected_signature)) {
            return false;
        }
        
    $payload_data = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
    
    if ($payload_data['exp'] < time()) {
        return false;
    }
    
    return $payload_data;
}

// Encryption functions
function encrypt_data($data) {
    $method = 'AES-256-CBC';
    $key = hash('sha256', ENCRYPTION_KEY, true);
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
    
    $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
    
    return base64_encode($iv . $encrypted);
}

function decrypt_data($encrypted_data) {
    $method = 'AES-256-CBC';
    $key = hash('sha256', ENCRYPTION_KEY, true);
    
    $data = base64_decode($encrypted_data);
    $iv_length = openssl_cipher_iv_length($method);
    $iv = substr($data, 0, $iv_length);
    $encrypted = substr($data, $iv_length);
    
    return openssl_decrypt($encrypted, $method, $key, 0, $iv);
}

// Rate limiting
function check_rate_limit($identifier, $max_requests = 60, $time_window = 3600) {
    $cache_file = sys_get_temp_dir() . '/rate_limit_' . md5($identifier);
    $current_time = time();
    
    if (file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true);
        
        // Remove old entries
        $data['requests'] = array_filter($data['requests'], function($timestamp) use ($current_time, $time_window) {
            return $timestamp > ($current_time - $time_window);
        });
        
        if (count($data['requests']) >= $max_requests) {
            log_message('warning', 'Rate limit exceeded', [
                'identifier' => $identifier,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            return false;
        }
    } else {
        $data = ['requests' => []];
    }
    
    $data['requests'][] = $current_time;
    file_put_contents($cache_file, json_encode($data));
    
    return true;
}

// Security headers
function set_security_headers() {
    // Content Security Policy
    $csp = "default-src 'self'; ";
    $csp .= "script-src 'self' 'unsafe-inline'; ";
    $csp .= "style-src 'self' 'unsafe-inline'; ";
    $csp .= "img-src 'self' data: https:; ";
    $csp .= "font-src 'self' data:; ";
    $csp .= "connect-src 'self'; ";
    $csp .= "frame-ancestors 'none';";
    
    header("Content-Security-Policy: " . $csp);
    
    // Other security headers
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
    
    // HSTS for HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
    }
}

// Log security events
function log_security_event($event_type, $details = []) {
    log_message('warning', 'Security event: ' . $event_type, array_merge([
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown'
    ], $details));
}
