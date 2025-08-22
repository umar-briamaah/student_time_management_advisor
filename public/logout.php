<?php
require_once __DIR__ . '/../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

session_destroy();

// Redirect to login - server is running from public directory
header('Location: /login.php');
exit;