<?php
echo "Testing includes...<br>";

echo "Step 1: Testing config.php<br>";
try {
    require_once __DIR__ . '/../includes/config.php';
    echo "✓ config.php loaded successfully<br>";
    echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "<br>";
} catch (Exception $e) {
    echo "✗ Error loading config.php: " . $e->getMessage() . "<br>";
}

echo "Step 2: Testing db.php<br>";
try {
    require_once __DIR__ . '/../includes/db.php';
    echo "✓ db.php loaded successfully<br>";
} catch (Exception $e) {
    echo "✗ Error loading db.php: " . $e->getMessage() . "<br>";
}

echo "Step 3: Testing functions.php<br>";
try {
    require_once __DIR__ . '/../includes/functions.php';
    echo "✓ functions.php loaded successfully<br>";
} catch (Exception $e) {
    echo "✗ Error loading functions.php: " . $e->getMessage() . "<br>";
}

echo "Step 4: Testing database connection<br>";
try {
    $pdo = DB::conn();
    echo "✓ Database connection successful<br>";
    
    // Test a simple query
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks");
    $stmt->execute();
    $count = $stmt->fetch()['count'];
    echo "✓ Total tasks in database: {$count}<br>";
    
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "<br>";
}

echo "<br>Test completed!";
?>
