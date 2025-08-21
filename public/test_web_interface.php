<?php
// Test page to verify web interface functionality
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Task Creation Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        form { margin: 20px 0; padding: 20px; border: 1px solid #ccc; }
        input, select, textarea { margin: 5px; padding: 5px; }
        button { padding: 10px 20px; background: #007cba; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Task Creation Test Interface</h1>";

try {
    $pdo = DB::conn();
    echo "<p class='success'>✓ Database connection successful</p>";
    
    // Check current tasks
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks");
    $stmt->execute();
    $total_tasks = $stmt->fetch()['count'];
    echo "<p class='info'>Total tasks in database: {$total_tasks}</p>";
    
    // Check tasks for user 5
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks WHERE user_id = 5");
    $stmt->execute();
    $user_tasks = $stmt->fetch()['count'];
    echo "<p class='info'>Tasks for user ID 5: {$user_tasks}</p>";
    
    // Show existing tasks for user 5
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = 5 ORDER BY created_at DESC");
    $stmt->execute();
    $tasks = $stmt->fetchAll();
    
    if (!empty($tasks)) {
        echo "<h3>Existing Tasks for User 5:</h3><ul>";
        foreach ($tasks as $task) {
            echo "<li><strong>{$task['title']}</strong> - {$task['category']} - Due: {$task['due_at']} - Created: {$task['created_at']}</li>";
        }
        echo "</ul>";
    }
    
    // Test form
    echo "<h3>Create Test Task</h3>
    <form method='post'>
        <div>
            <label>Title:</label>
            <input type='text' name='title' value='Test Task " . date('Y-m-d H:i:s') . "' required>
        </div>
        <div>
            <label>Description:</label>
            <textarea name='description'>This is a test task</textarea>
        </div>
        <div>
            <label>Category:</label>
            <select name='category'>
                <option value='Other'>Other</option>
                <option value='Lecture'>Lecture</option>
                <option value='Lab'>Lab</option>
                <option value='Exam'>Exam</option>
                <option value='Assignment'>Assignment</option>
            </select>
        </div>
        <div>
            <label>Due Date:</label>
            <input type='datetime-local' name='due_at' value='" . date('Y-m-d\TH:i', strtotime('+1 day')) . "' required>
        </div>
        <div>
            <label>Estimated Minutes:</label>
            <input type='number' name='estimated_minutes' value='30' min='1'>
        </div>
        <button type='submit' name='create' value='1'>Create Task</button>
    </form>";
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
        echo "<h3>Form Submission Results:</h3>";
        
        $task_data = [
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'category' => $_POST['category'] ?? 'Other',
            'due_at' => $_POST['due_at'] ?? '',
            'estimated_minutes' => $_POST['estimated_minutes'] ?? 30
        ];
        
        echo "<p class='info'>Task data received:</p><pre>" . print_r($task_data, true) . "</pre>";
        
        // Validate data
        $errors = validate_task_data($task_data);
        if (!empty($errors)) {
            echo "<p class='error'>Validation errors:</p><ul>";
            foreach ($errors as $error) {
                echo "<li class='error'>{$error}</li>";
            }
            echo "</ul>";
        } else {
            echo "<p class='success'>✓ Data validation passed</p>";
            
            // Create task
            try {
                $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, description, category, due_at, estimated_minutes) VALUES (?, ?, ?, ?, ?, ?)");
                $result = $stmt->execute([5, $task_data['title'], $task_data['description'], $task_data['category'], $task_data['due_at'], $task_data['estimated_minutes']]);
                
                if ($result) {
                    $task_id = $pdo->lastInsertId();
                    echo "<p class='success'>✓ Task created successfully with ID: {$task_id}</p>";
                    
                    // Refresh page to show new task
                    echo "<script>setTimeout(function(){ location.reload(); }, 2000);</script>";
                    echo "<p class='info'>Page will refresh in 2 seconds to show the new task...</p>";
                } else {
                    echo "<p class='error'>✗ Failed to create task</p>";
                    $error_info = $stmt->errorInfo();
                    echo "<p class='error'>Error: " . implode(', ', $error_info) . "</p>";
                }
            } catch (Exception $e) {
                echo "<p class='error'>✗ Exception: " . $e->getMessage() . "</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
