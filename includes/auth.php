<?php
/**
 * Authentication and session management
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Redirect to login if not authenticated
function require_auth() {
    if (!is_logged_in()) {
        log_message('info', 'Unauthenticated access attempt', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ]);
        header('Location: /login.php');
        exit();
    }
}

// Legacy function for backward compatibility
function require_login() {
    require_auth();
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user ID
function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user data
function get_current_user_data() {
    if (!is_logged_in()) {
        return null;
    }
    
    try {
        $pdo = DB::conn();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([get_current_user_id()]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        log_message('error', 'Failed to get current user data', [
            'user_id' => get_current_user_id(),
            'error' => $e->getMessage()
        ]);
        return null;
    }
}

// Legacy function for backward compatibility
function current_user() {
    return get_current_user_data();
}

// CSRF Protection
function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function verify_csrf($token) {
    return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

// Log user activity
function log_user_activity($action, $details = []) {
    if (is_logged_in()) {
        log_message('info', 'User activity: ' . $action, array_merge([
            'user_id' => get_current_user_id(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ], $details));
    }
}