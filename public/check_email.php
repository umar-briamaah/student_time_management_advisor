<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';

// Set JSON content type
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Verify CSRF token
if (!verify_csrf($_POST['csrf'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

// Get email from request
$email = trim($_POST['email'] ?? '');

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['exists' => false, 'error' => 'Invalid email format']);
    exit;
}

try {
    $pdo = DB::conn();
    
    // Check if email exists in database
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo json_encode(['exists' => true, 'message' => 'Email is registered']);
    } else {
        echo json_encode(['exists' => false, 'message' => 'Email not registered']);
    }
    
} catch (PDOException $e) {
    // Log error (in production, don't expose database errors)
    error_log("Database error in check_email.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
} catch (Exception $e) {
    // Log error
    error_log("General error in check_email.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred']);
}
?>
