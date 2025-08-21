<?php
// Test script to verify task creation
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

echo "Testing Task Creation...\n";

try {
    $pdo = DB::conn();
    echo "✓ Database connection successful\n";
    
    // Test user ID 5 (from the logs)
    $user_id = 5;
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✓ User found: {$user['name']} ({$user['email']})\n";
        
        // Check current task count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $current_count = $stmt->fetch()['count'];
        echo "✓ Current task count for user: {$current_count}\n";
        
        // Try to create a test task
        $task_data = [
            'title' => 'Test Task ' . date('Y-m-d H:i:s'),
            'description' => 'This is a test task created by the test script',
            'category' => 'Other',
            'due_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'estimated_minutes' => 30
        ];
        
        echo "Attempting to create task: {$task_data['title']}\n";
        
        $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, description, category, due_at, estimated_minutes) VALUES (?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([$user_id, $task_data['title'], $task_data['description'], $task_data['category'], $task_data['due_at'], $task_data['estimated_minutes']]);
        
        if ($result) {
            $task_id = $pdo->lastInsertId();
            echo "✓ Task created successfully with ID: {$task_id}\n";
            
            // Verify task was created
            $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$task_id]);
            $created_task = $stmt->fetch();
            
            if ($created_task) {
                echo "✓ Task verified in database:\n";
                echo "  - Title: {$created_task['title']}\n";
                echo "  - User ID: {$created_task['user_id']}\n";
                echo "  - Category: {$created_task['category']}\n";
                echo "  - Due: {$created_task['due_at']}\n";
            }
            
            // Check new task count
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $new_count = $stmt->fetch()['count'];
            echo "✓ New task count: {$new_count}\n";
            
        } else {
            echo "✗ Failed to create task\n";
            $error_info = $stmt->errorInfo();
            echo "Error: " . implode(', ', $error_info) . "\n";
        }
        
    } else {
        echo "✗ User with ID {$user_id} not found\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\nTest completed.\n";
?>
