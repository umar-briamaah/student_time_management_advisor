<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = DB::conn();

echo "<h1>Debug Information</h1>";
echo "<h2>Session Data:</h2>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<h2>Current User:</h2>";
$current_user = current_user();
if ($current_user) {
    echo "<p><strong>User ID:</strong> " . $current_user['id'] . "</p>";
    echo "<p><strong>Name:</strong> " . $current_user['name'] . "</p>";
    echo "<p><strong>Email:</strong> " . $current_user['email'] . "</p>";
    
    // Check tasks for this user
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks WHERE user_id = ?");
    $stmt->execute([$current_user['id']]);
    $task_count = $stmt->fetch()['count'];
    
    echo "<p><strong>Tasks for this user:</strong> " . $task_count . "</p>";
    
    if ($task_count > 0) {
        $stmt = $pdo->prepare("SELECT id, title, category, due_at, created_at FROM tasks WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$current_user['id']]);
        $tasks = $stmt->fetchAll();
        
        echo "<h3>Recent Tasks:</h3>";
        echo "<ul>";
        foreach ($tasks as $task) {
            echo "<li>ID: {$task['id']} - {$task['title']} ({$task['category']}) - Due: {$task['due_at']}</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p><strong>No user logged in</strong></p>";
}

echo "<h2>All Users in Database:</h2>";
$stmt = $pdo->query("SELECT id, name, email FROM users ORDER BY id");
$users = $stmt->fetchAll();

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Task Count</th></tr>";
foreach ($users as $user) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $task_count = $stmt->fetch()['count'];
    
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>{$user['name']}</td>";
    echo "<td>{$user['email']}</td>";
    echo "<td>{$task_count}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>All Tasks in Database:</h2>";
$stmt = $pdo->query("SELECT id, user_id, title, category, due_at, created_at FROM tasks ORDER BY user_id, created_at DESC LIMIT 20");
$tasks = $stmt->fetchAll();

echo "<table border='1'>";
echo "<tr><th>ID</th><th>User ID</th><th>Title</th><th>Category</th><th>Due</th><th>Created</th></tr>";
foreach ($tasks as $task) {
    echo "<tr>";
    echo "<td>{$task['id']}</td>";
    echo "<td>{$task['user_id']}</td>";
    echo "<td>{$task['title']}</td>";
    echo "<td>{$task['category']}</td>";
    echo "<td>{$task['due_at']}</td>";
    echo "<td>{$task['created_at']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><a href='tasks.php'>Back to Tasks</a></p>";
echo "<p><a href='dashboard.php'>Back to Dashboard</a></p>";
?>
