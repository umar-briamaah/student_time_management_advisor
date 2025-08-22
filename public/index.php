<?php
require_once __DIR__ . '/../includes/config.php';

// Redirect to login - server is running from public directory
header('Location: /login.php');
exit;