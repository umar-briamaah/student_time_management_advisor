<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure APP_URL is defined
if (!defined('APP_URL')) {
    define('APP_URL', 'http://localhost/student-time-advisor-php/public');
}

require_login();
$user = current_user();
$pdo = DB::conn();
$errors = [];
$success_message = '';

// Debug: Log all POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST request received: " . print_r($_POST, true));
    error_log("Current user: " . print_r($user, true));
    
    // Check if this is a task creation request
    if (isset($_POST['create'])) {
        error_log("Task creation request detected!");
        error_log("POST data for task creation: " . print_r($_POST, true));
    } else {
        error_log("No 'create' parameter found in POST data");
    }
}

// Handle task completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_task_id'])) {
    if (!verify_csrf($_POST['csrf'] ?? '')) { 
        $errors[] = 'Invalid CSRF token'; 
    } else {
        $tid = (int)$_POST['complete_task_id'];
        $stmt = $pdo->prepare("UPDATE tasks SET completed=1, completed_at=NOW() WHERE id=? AND user_id=?");
        if ($stmt->execute([$tid, $user['id']])) {
            // Award first task badge if applicable
            $count = $pdo->prepare("SELECT COUNT(*) c FROM tasks WHERE user_id=? AND completed=1");
            $count->execute([$user['id']]);
            if (($count->fetch()['c'] ?? 0) == 1) {
                award_badge_if_needed($user['id'], 'FIRST_TASK');
            }
            $success_message = 'Task marked as completed!';
        } else {
            $errors[] = 'Failed to complete task';
        }
    }
}

// Handle task deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_task_id'])) {
    if (!verify_csrf($_POST['csrf'] ?? '')) { 
        $errors[] = 'Invalid CSRF token'; 
    } else {
        $tid = (int)$_POST['delete_task_id'];
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id=? AND user_id=?");
        if ($stmt->execute([$tid, $user['id']])) {
            $success_message = 'Task deleted successfully!';
        } else {
            $errors[] = 'Failed to delete task';
        }
    }
}

// Handle task creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $task_data = [
            'title' => $_POST['title'] ?? '',
            'category' => $_POST['category'] ?? 'Other',
            'due_at' => $_POST['due_at'] ?? '',
            'description' => $_POST['description'] ?? '',
            'estimated_minutes' => $_POST['estimated_minutes'] ?? 60
        ];
        
        $errors = validate_task_data($task_data);
        
        if (empty($errors)) {
            // Debug: Log the user ID being used
            error_log("Creating task for user ID: " . $user['id'] . " (Name: " . $user['name'] . ")");
            error_log("Task data: " . print_r($task_data, true));
            
            try {
                // Simple direct insertion for debugging
                $stmt = $pdo->prepare("INSERT INTO tasks (user_id,title,description,category,due_at,estimated_minutes) VALUES (?,?,?,?,?,?)");
                $result = $stmt->execute([$user['id'], $task_data['title'], $task_data['description'], $task_data['category'], $task_data['due_at'], $task_data['estimated_minutes']]);
                
                if ($result) {
                    // Schedule reminders T-48h and T-12h
                    $tid = $pdo->lastInsertId();
                    error_log("Task created with ID: " . $tid);
                    
                    $due = new DateTime($task_data['due_at']);
                    foreach([48,12] as $h){
                        $send = (clone $due)->modify("-{$h} hours")->format('Y-m-d H:i:s');
                        if (strtotime($send) > time()) {
                            $pdo->prepare("INSERT INTO reminders (task_id,user_id,send_at) VALUES (?,?,?)")->execute([$tid,$user['id'],$send]);
                        }
                    }
                    $success_message = 'Task created successfully! (User ID: ' . $user['id'] . ', Task ID: ' . $tid . ')';
                    error_log("Success message set: " . $success_message);
                } else {
                    $error_msg = 'Failed to create task: ' . implode(', ', $stmt->errorInfo());
                    error_log($error_msg);
                    $errors[] = $error_msg;
                }
            } catch (Exception $e) {
                error_log("Exception creating task: " . $e->getMessage());
                $errors[] = 'Exception creating task: ' . $e->getMessage();
            }
        } else {
            error_log("Validation errors prevented task creation: " . print_r($errors, true));
        }
    }
}

// Handle task editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_task'])) {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $task_data = [
            'id' => (int)($_POST['task_id'] ?? 0),
            'title' => $_POST['title'] ?? '',
            'category' => $_POST['category'] ?? 'Other',
            'due_at' => $_POST['due_at'] ?? '',
            'description' => $_POST['description'] ?? '',
            'estimated_minutes' => $_POST['estimated_minutes'] ?? 60
        ];
        
        $errors = validate_task_data($task_data);
        
        if (empty($errors) && $task_data['id'] > 0) {
            $stmt = $pdo->prepare("UPDATE tasks SET title=?, description=?, category=?, due_at=?, estimated_minutes=?, updated_at=NOW() WHERE id=? AND user_id=?");
            if ($stmt->execute([$task_data['title'], $task_data['description'], $task_data['category'], $task_data['due_at'], $task_data['estimated_minutes'], $task_data['id'], $user['id']])) {
                $success_message = 'Task updated successfully!';
            } else {
                $errors[] = 'Failed to update task';
            }
        }
    }
}

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$filter_category = $_GET['category'] ?? 'all';
$search = trim($_GET['search'] ?? '');

// Debug information
$debug_info = [
    'current_user_id' => $user['id'],
    'current_user_name' => $user['name'],
    'current_user_email' => $user['email']
];

// Build query with filters
$where_conditions = ["user_id = ?"];
$params = [$user['id']];

if ($filter_status === 'pending') {
    $where_conditions[] = "completed = 0";
} elseif ($filter_status === 'completed') {
    $where_conditions[] = "completed = 1";
}

if ($filter_category !== 'all') {
    $where_conditions[] = "category = ?";
    $params[] = $filter_category;
}

if (!empty($search)) {
    $where_conditions[] = "(title LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = implode(' AND ', $where_conditions);

// Debug: Log the query and parameters
error_log("Query WHERE clause: " . $where_clause);
error_log("Query parameters: " . print_r($params, true));
error_log("User ID being used: " . $user['id']);

// Fetch tasks with filters
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE $where_clause ORDER BY due_at ASC");
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Debug: Log the results
error_log("Tasks found: " . count($tasks));
if (empty($tasks)) {
    error_log("No tasks found - checking raw query...");
    // Try a simple query to see if there are any tasks at all
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $count = $stmt->fetch()['count'];
    error_log("Raw count for user {$user['id']}: {$count}");
}

include __DIR__ . '/../includes/layout/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-50 via-purple-50 to-pink-50 rounded-xl p-6 mb-6 border border-indigo-100">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Task Management</h1>
                    <p class="text-gray-600 mt-1">Organize and track your academic tasks with precision</p>
                </div>
            </div>
            <div class="flex gap-3 mt-4 sm:mt-0">
                <a href="<?php echo APP_URL; ?>/debug.php" 
                   class="bg-gradient-to-r from-gray-600 to-gray-700 text-white px-4 py-3 rounded-lg hover:from-gray-700 hover:to-gray-800 transition-all duration-300 font-medium shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Debug Info
                </a>
                <button onclick="document.getElementById('createTaskModal').classList.remove('hidden')" 
                        class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all duration-300 font-medium shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    + New Task
                </button>
            </div>
        </div>
    </div>

    <!-- Debug Information (remove this in production) -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <p class="text-blue-800 font-medium">Debug Info:</p>
                <p class="text-blue-700 text-sm">User ID: <?php echo $debug_info['current_user_id']; ?> | Name: <?php echo h($debug_info['current_user_name']); ?> | Email: <?php echo h($debug_info['current_user_email']); ?></p>
                <p class="text-blue-700 text-sm">Total tasks found: <?php echo count($tasks); ?></p>
                <p class="text-blue-700 text-sm">Database tasks for user <?php echo $user['id']; ?>: <?php 
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks WHERE user_id = ?");
                    $stmt->execute([$user['id']]);
                    echo $stmt->fetch()['count'];
                ?></p>
            </div>
        </div>
    </div>
    
    <!-- Simple Test Form -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
        <h3 class="text-yellow-800 font-medium mb-2">Test Form (Debug)</h3>
        <form method="post" class="space-y-2">
            <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="create" value="1">
            <input type="text" name="title" placeholder="Test Task Title" required class="w-full border rounded px-2 py-1">
            <input type="datetime-local" name="due_at" required class="w-full border rounded px-2 py-1">
            <button type="submit" class="bg-yellow-600 text-white px-3 py-1 rounded text-sm">
                Create Test Task
            </button>
        </form>
        
        <!-- Direct Test Button -->
        <div class="mt-3 pt-3 border-t border-yellow-200">
            <form method="post" class="inline">
                <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="create" value="1">
                <input type="hidden" name="title" value="Direct Test Task">
                <input type="hidden" name="due_at" value="<?php echo date('Y-m-d\TH:i'); ?>">
                <input type="hidden" name="category" value="Other">
                <input type="hidden" name="description" value="This is a direct test task">
                <input type="hidden" name="estimated_minutes" value="30">
                <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded text-sm">
                    Create Direct Test Task (No Validation)
                </button>
            </form>
        </div>
    </div>

    <!-- Messages -->
    <?php if ($success_message): ?>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex">
                <svg class="w-5 h-5 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <p class="text-green-800"><?php echo h($success_message); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <?php foreach($errors as $error): ?>
                <p class="text-red-800"><?php echo h($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Filters and Search -->
    <div class="bg-gradient-to-br from-white to-gray-50 rounded-xl shadow-lg p-6 border border-gray-100">
        <div class="flex items-center mb-4">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center mr-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800">Filter & Search Tasks</h3>
        </div>
        <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white shadow-sm">
                    <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Tasks</option>
                    <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select name="category" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="all">All Categories</option>
                    <option value="Exam" <?php echo $filter_category === 'Exam' ? 'selected' : ''; ?>>Exam</option>
                    <option value="Assignment" <?php echo $filter_category === 'Assignment' ? 'selected' : ''; ?>>Assignment</option>
                    <option value="Lab" <?php echo $filter_category === 'Lab' ? 'selected' : ''; ?>>Lab</option>
                    <option value="Lecture" <?php echo $filter_category === 'Lecture' ? 'selected' : ''; ?>>Lecture</option>
                    <option value="Other" <?php echo $filter_category === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" value="<?php echo h($search); ?>" 
                       placeholder="Search tasks..." 
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Tasks List -->
    <div class="bg-white rounded-xl shadow">
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-xl font-semibold text-gray-800">
                Tasks (<?php echo count($tasks); ?>)
                <?php if ($filter_status !== 'all' || $filter_category !== 'all' || !empty($search)): ?>
                    <span class="text-sm font-normal text-gray-500">
                        - Filtered results
                    </span>
                <?php endif; ?>
            </h2>
        </div>
        
        <div class="p-6">
            <?php if (empty($tasks)): ?>
                <div class="text-center py-16">
                    <div class="w-24 h-24 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-600 mb-3">No tasks found</h3>
                    <p class="text-gray-500 mb-6">Start your productivity journey by creating your first task</p>
                    <button onclick="document.getElementById('createTaskModal').classList.remove('hidden')" 
                            class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all duration-300 font-medium shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Create Your First Task
                    </button>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach($tasks as $task): ?>
                        <div class="bg-gradient-to-r from-white to-gray-50 border border-gray-200 rounded-xl p-6 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 <?php echo $task['completed'] ? 'opacity-75' : ''; ?>">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="px-3 py-1 text-xs font-medium rounded-full border <?php echo get_category_color($task['category']); ?>">
                                            <?php echo h($task['category']); ?>
                                        </span>
                                        <?php if (!$task['completed']): ?>
                                            <?php $priority = priority_score($task); ?>
                                            <?php if ($priority > 0): ?>
                                                <span class="px-2 py-1 text-xs font-medium rounded <?php echo get_priority_color($priority); ?>">
                                                    Priority: <?php echo $priority; ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <h3 class="font-medium text-gray-900 mb-2 <?php echo $task['completed'] ? 'line-through text-gray-500' : ''; ?>">
                                        <?php echo h($task['title']); ?>
                                    </h3>
                                    
                                    <?php if ($task['description']): ?>
                                        <p class="text-sm text-gray-600 mb-2"><?php echo h($task['description']); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="flex items-center gap-4 text-sm text-gray-500">
                                        <span><?php echo format_due_date($task['due_at']); ?></span>
                                        <?php if ($task['estimated_minutes']): ?>
                                            <span>• <?php echo $task['estimated_minutes']; ?> min</span>
                                        <?php endif; ?>
                                        <span>• Created <?php echo date('M j, Y', strtotime($task['created_at'])); ?></span>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-3 ml-4">
                                    <?php if (!$task['completed']): ?>
                                        <form method="post" class="inline">
                                            <input type="hidden" name="complete_task_id" value="<?php echo $task['id']; ?>">
                                            <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                                            <button type="submit" class="bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-2 rounded-lg text-sm hover:from-green-600 hover:to-green-700 transition-all duration-300 font-medium shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                Mark Done
                                            </button>
                                        </form>
                                        
                                        <button onclick="editTask(<?php echo htmlspecialchars(json_encode($task)); ?>)" 
                                                class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:from-blue-600 hover:to-blue-700 transition-all duration-300 font-medium shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            Edit
                                        </button>
                                    <?php else: ?>
                                        <div class="flex items-center bg-gradient-to-r from-green-100 to-green-200 text-green-800 px-4 py-2 rounded-lg font-medium">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Completed
                                        </div>
                                    <?php endif; ?>
                                    
                                    <form method="post" class="inline" onsubmit="return confirm('Are you sure you want to delete this task?')">
                                        <input type="hidden" name="delete_task_id" value="<?php echo $task['id']; ?>">
                                        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                                        <button type="submit" class="bg-gradient-to-r from-red-500 to-red-600 text-white px-4 py-2 rounded-lg text-sm hover:from-red-600 hover:to-red-700 transition-all duration-300 font-medium shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Task Modal -->
<div id="createTaskModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Create New Task</h3>
                <button onclick="document.getElementById('createTaskModal').classList.add('hidden')" 
                        class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form method="post" class="space-y-4">
                <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Task Title *</label>
                    <input type="text" name="title" required 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Enter task title">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="Lecture">Lecture</option>
                        <option value="Lab">Lab</option>
                        <option value="Exam">Exam</option>
                        <option value="Assignment">Assignment</option>
                        <option value="Other" selected>Other</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3" 
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Task description (optional)"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Due Date & Time *</label>
                    <input type="datetime-local" name="due_at" required 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Minutes</label>
                    <input type="number" name="estimated_minutes" value="60" min="15" step="15"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="submit" name="create" value="1" 
                            class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        Create Task
                    </button>
                    <button type="button" onclick="document.getElementById('createTaskModal').classList.add('hidden')"
                            class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Task Modal -->
<div id="editTaskModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Edit Task</h3>
                <button onclick="document.getElementById('editTaskModal').classList.add('hidden')" 
                        class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form method="post" class="space-y-4">
                <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="task_id" id="edit_task_id">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Task Title *</label>
                    <input type="text" name="title" id="edit_title" required 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category" id="edit_category" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="Lecture">Lecture</option>
                        <option value="Lab">Lab</option>
                        <option value="Exam">Exam</option>
                        <option value="Assignment">Assignment</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="edit_description" rows="3" 
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Due Date & Time *</label>
                    <input type="datetime-local" name="due_at" id="edit_due_at" required 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Minutes</label>
                    <input type="number" name="estimated_minutes" id="edit_estimated_minutes" min="15" step="15"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="submit" name="edit_task" value="1" 
                            class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        Update Task
                    </button>
                    <button type="button" onclick="document.getElementById('editTaskModal').classList.add('hidden')"
                            class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editTask(task) {
    document.getElementById('edit_task_id').value = task.id;
    document.getElementById('edit_title').value = task.title;
    document.getElementById('edit_category').value = task.category;
    document.getElementById('edit_description').value = task.description;
    document.getElementById('edit_due_at').value = task.due_at.replace(' ', 'T');
    document.getElementById('edit_estimated_minutes').value = task.estimated_minutes;
    
    document.getElementById('editTaskModal').classList.remove('hidden');
}

// Close modals when clicking outside
window.onclick = function(event) {
    const createModal = document.getElementById('createTaskModal');
    const editModal = document.getElementById('editTaskModal');
    
    if (event.target === createModal) {
        createModal.classList.add('hidden');
    }
    if (event.target === editModal) {
        editModal.classList.add('hidden');
    }
}
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>