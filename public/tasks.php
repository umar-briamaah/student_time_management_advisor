<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// APP_URL should be defined in config.php

require_login();
$user = current_user();
$pdo = DB::conn();
$errors = [];
$success_message = '';

// Debug logging only in development mode
if (defined('DEBUG') && DEBUG) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        error_log("POST request received for user: " . $user['id']);
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
            'title' => trim($_POST['title'] ?? ''),
            'category' => $_POST['category'] ?? 'Other',
            'due_at' => $_POST['due_at'] ?? '',
            'description' => trim($_POST['description'] ?? ''),
            'estimated_minutes' => (int)($_POST['estimated_minutes'] ?? 60)
        ];
        
        // Validate datetime format
        if (!empty($task_data['due_at'])) {
            $due_datetime = DateTime::createFromFormat('Y-m-d\TH:i', $task_data['due_at']);
            if ($due_datetime === false) {
                $errors[] = 'Invalid date and time format. Please select a valid date and time.';
            } else {
                // Ensure the datetime is in the future
                if ($due_datetime <= new DateTime()) {
                    $errors[] = 'Due date and time must be in the future.';
                } else {
                    // Format the datetime properly for database
                    $task_data['due_at'] = $due_datetime->format('Y-m-d H:i:s');
                }
            }
        } else {
            $errors[] = 'Due date and time is required.';
        }
        
        // Additional validation
        if (empty($task_data['title'])) {
            $errors[] = 'Task title is required.';
        }
        
        if (empty($errors)) {
            try {
                // Insert task
                $stmt = $pdo->prepare("INSERT INTO tasks (user_id,title,description,category,due_at,estimated_minutes) VALUES (?,?,?,?,?,?)");
                $result = $stmt->execute([$user['id'], $task_data['title'], $task_data['description'], $task_data['category'], $task_data['due_at'], $task_data['estimated_minutes']]);
                
                if ($result) {
                    // Schedule reminders T-48h and T-12h
                    $tid = $pdo->lastInsertId();
                    
                    $due = new DateTime($task_data['due_at']);
                    foreach([48,12] as $h){
                        $send = (clone $due)->modify("-{$h} hours")->format('Y-m-d H:i:s');
                        if (strtotime($send) > time()) {
                            $pdo->prepare("INSERT INTO reminders (task_id,user_id,send_at) VALUES (?,?,?)")->execute([$tid,$user['id'],$send]);
                        }
                    }
                    
                    // Clear user cache since data has changed
                    clear_user_cache($user['id']);
                    
                    $success_message = 'Task created successfully!';
                } else {
                    $errors[] = 'Failed to create task. Please try again.';
                }
            } catch (Exception $e) {
                if (defined('DEBUG') && DEBUG) {
                    error_log("Exception creating task: " . $e->getMessage());
                }
                $errors[] = 'An error occurred while creating the task. Please try again.';
            }
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
            'title' => trim($_POST['title'] ?? ''),
            'category' => $_POST['category'] ?? 'Other',
            'due_at' => $_POST['due_at'] ?? '',
            'description' => trim($_POST['description'] ?? ''),
            'estimated_minutes' => (int)($_POST['estimated_minutes'] ?? 60)
        ];
        
        // Validate datetime format for editing
        if (!empty($task_data['due_at'])) {
            $due_datetime = DateTime::createFromFormat('Y-m-d\TH:i', $task_data['due_at']);
            if ($due_datetime === false) {
                $errors[] = 'Invalid date and time format. Please select a valid date and time.';
            } else {
                // Ensure the datetime is in the future
                if ($due_datetime <= new DateTime()) {
                    $errors[] = 'Due date and time must be in the future.';
                } else {
                    // Format the datetime properly for database
                    $task_data['due_at'] = $due_datetime->format('Y-m-d H:i:s');
                }
            }
        } else {
            $errors[] = 'Due date and time is required.';
        }
        
        // Additional validation
        if (empty($task_data['title'])) {
            $errors[] = 'Task title is required.';
        }
        
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

// User info for filtering
$user_id = $user['id'];

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

// Fetch tasks with filters
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE $where_clause ORDER BY due_at ASC");
$stmt->execute($params);
$tasks = $stmt->fetchAll();

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
                <button onclick="document.getElementById('createTaskModal').classList.remove('hidden')" 
                        class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-8 py-4 rounded-xl hover:from-blue-700 hover:to-purple-700 transition-all duration-300 font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-1 text-lg">
                    <svg class="w-6 h-6 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    + Create New Task
                </button>
            </div>
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
                <div class="text-center py-20">
                    <div class="w-24 h-24 bg-gradient-to-br from-blue-100 to-purple-100 rounded-full flex items-center justify-center mx-auto mb-8 shadow-lg">
                        <svg class="w-12 h-12 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-800 mb-4">Ready to Get Organized? 🚀</h3>
                    <p class="text-lg text-gray-600 mb-8 max-w-md mx-auto">Create your first task and start building your academic success story. Track deadlines, manage priorities, and stay on top of your studies.</p>
                    <button onclick="document.getElementById('createTaskModal').classList.remove('hidden')" 
                            class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-10 py-4 rounded-xl hover:from-blue-700 hover:to-purple-700 transition-all duration-300 font-bold shadow-xl hover:shadow-2xl transform hover:-translate-y-2 text-xl">
                        <svg class="w-7 h-7 inline mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        ✨ Create Your First Task
                    </button>
                    <p class="text-sm text-gray-500 mt-4">It only takes a few seconds to get started!</p>
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
    <div class="relative top-10 mx-auto p-8 border w-full max-w-md shadow-2xl rounded-2xl bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900">Create New Task</h3>
                </div>
                <button onclick="document.getElementById('createTaskModal').classList.add('hidden')" 
                        class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form method="post" name="createTaskForm" class="space-y-4">
                <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Task Title *</label>
                    <input type="text" name="title" required 
                           class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-lg"
                           placeholder="e.g., Complete Math Assignment">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Category</label>
                    <select name="category" class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-lg bg-white">
                        <option value="Assignment" selected>📚 Assignment</option>
                        <option value="Exam">📝 Exam</option>
                        <option value="Lab">🔬 Lab</option>
                        <option value="Lecture">🎓 Lecture</option>
                        <option value="Other">📋 Other</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3" 
                              class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 resize-none"
                              placeholder="Add details about your task (optional)"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Due Date & Time *</label>
                    <input type="datetime-local" name="due_at" required 
                           value="<?php echo date('Y-m-d\TH:i', strtotime('+1 day')); ?>"
                           min="<?php echo date('Y-m-d\TH:i'); ?>"
                           class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-lg">
                    <p class="text-xs text-blue-600 mt-2 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Select a future date and time
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Estimated Time</label>
                    <input type="number" name="estimated_minutes" value="60" min="15" step="15"
                           class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-lg"
                           placeholder="60">
                    <p class="text-xs text-gray-500 mt-1">How long will this task take? (in minutes)</p>
                </div>
                
                <div class="flex gap-4 pt-6">
                    <button type="submit" name="create" value="1" 
                            class="flex-1 bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-4 rounded-xl hover:from-blue-700 hover:to-purple-700 transition-all duration-300 font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-1 text-lg">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Create Task
                    </button>
                    <button type="button" onclick="document.getElementById('createTaskModal').classList.add('hidden')"
                            class="flex-1 bg-gray-100 text-gray-700 px-6 py-4 rounded-xl hover:bg-gray-200 transition-all duration-300 font-semibold border-2 border-gray-200 hover:border-gray-300">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Task Modal -->
<div id="editTaskModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-10 mx-auto p-8 border w-full max-w-md shadow-2xl rounded-2xl bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-blue-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900">Edit Task</h3>
                </div>
                <button onclick="document.getElementById('editTaskModal').classList.add('hidden')" 
                        class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form method="post" name="editTaskForm" class="space-y-4">
                <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="task_id" id="edit_task_id">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Task Title *</label>
                    <input type="text" name="title" id="edit_title" required 
                           class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-lg">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Category</label>
                    <select name="category" id="edit_category" class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-lg bg-white">
                        <option value="Assignment">📚 Assignment</option>
                        <option value="Exam">📝 Exam</option>
                        <option value="Lab">🔬 Lab</option>
                        <option value="Lecture">🎓 Lecture</option>
                        <option value="Other">📋 Other</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="edit_description" rows="3" 
                              class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 resize-none"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Due Date & Time *</label>
                    <input type="datetime-local" name="due_at" id="edit_due_at" required 
                           min="<?php echo date('Y-m-d\TH:i'); ?>"
                           class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-lg">
                    <p class="text-xs text-blue-600 mt-2 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Select a future date and time
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Estimated Time</label>
                    <input type="number" name="estimated_minutes" id="edit_estimated_minutes" min="15" step="15"
                           class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-lg">
                    <p class="text-xs text-gray-500 mt-1">How long will this task take? (in minutes)</p>
                </div>
                
                <div class="flex gap-4 pt-6">
                    <button type="submit" name="edit_task" value="1" 
                            class="flex-1 bg-gradient-to-r from-green-600 to-blue-600 text-white px-6 py-4 rounded-xl hover:from-green-700 hover:to-blue-700 transition-all duration-300 font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-1 text-lg">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Update Task
                    </button>
                    <button type="button" onclick="document.getElementById('editTaskModal').classList.add('hidden')"
                            class="flex-1 bg-gray-100 text-gray-700 px-6 py-4 rounded-xl hover:bg-gray-200 transition-all duration-300 font-semibold border-2 border-gray-200 hover:border-gray-300">
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
    
    // Format the datetime properly for datetime-local input
    if (task.due_at) {
        const dueDate = new Date(task.due_at);
        const year = dueDate.getFullYear();
        const month = String(dueDate.getMonth() + 1).padStart(2, '0');
        const day = String(dueDate.getDate()).padStart(2, '0');
        const hours = String(dueDate.getHours()).padStart(2, '0');
        const minutes = String(dueDate.getMinutes()).padStart(2, '0');
        document.getElementById('edit_due_at').value = `${year}-${month}-${day}T${hours}:${minutes}`;
    }
    
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

// Add form validation and dynamic task creation
document.addEventListener('DOMContentLoaded', function() {
    const createForm = document.querySelector('form[name="createTaskForm"]');
    const editForm = document.querySelector('form[name="editTaskForm"]');
    
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            
            const dueAt = this.querySelector('input[name="due_at"]').value;
            const title = this.querySelector('input[name="title"]').value.trim();
            const category = this.querySelector('select[name="category"]').value;
            const description = this.querySelector('textarea[name="description"]').value.trim();
            const estimatedMinutes = this.querySelector('input[name="estimated_minutes"]').value;
            const csrf = this.querySelector('input[name="csrf"]').value;
            
            if (!title) {
                showNotification('Please enter a task title.', 'error');
                return;
            }
            
            if (!dueAt) {
                showNotification('Please select a due date and time.', 'error');
                return;
            }
            
            const selectedDate = new Date(dueAt);
            const now = new Date();
            
            if (selectedDate <= now) {
                showNotification('Due date and time must be in the future.', 'error');
                return;
            }
            
            // Create task data
            const taskData = {
                title: title,
                category: category,
                description: description,
                due_at: dueAt,
                estimated_minutes: estimatedMinutes,
                csrf: csrf
            };
            
            // Submit task creation
            createTask(taskData);
        });
    }
    
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            const dueAt = this.querySelector('input[name="due_at"]').value;
            const title = this.querySelector('input[name="title"]').value.trim();
            
            if (!title) {
                e.preventDefault();
                showNotification('Please enter a task title.', 'error');
                return;
            }
            
            if (!dueAt) {
                e.preventDefault();
                showNotification('Please select a due date and time.', 'error');
                return;
            }
            
            const selectedDate = new Date(dueAt);
            const now = new Date();
            
            if (selectedDate <= now) {
                e.preventDefault();
                showNotification('Due date and time must be in the future.', 'error');
                return;
            }
        });
    }
});

// Function to create task via AJAX
async function createTask(taskData) {
    try {
        const formData = new FormData();
        formData.append('create', '1');
        formData.append('csrf', taskData.csrf);
        formData.append('title', taskData.title);
        formData.append('category', taskData.category);
        formData.append('description', taskData.description);
        formData.append('due_at', taskData.due_at);
        formData.append('estimated_minutes', taskData.estimated_minutes);
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            const result = await response.text();
            
            // Check if task was created successfully
            if (result.includes('Task created successfully')) {
                showNotification('Task created successfully! 🎉', 'success');
                
                // Close the modal
                document.getElementById('createTaskModal').classList.add('hidden');
                
                // Reset the form
                document.querySelector('form[name="createTaskForm"]').reset();
                
                // Reload the page to show the new task
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showNotification('Failed to create task. Please try again.', 'error');
            }
        } else {
            showNotification('Network error. Please try again.', 'error');
        }
    } catch (error) {
        console.error('Error creating task:', error);
        showNotification('An error occurred. Please try again.', 'error');
    }
}

// Function to show notifications
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 max-w-sm ${
        type === 'success' ? 'bg-green-500 text-white' :
        type === 'error' ? 'bg-red-500 text-white' :
        'bg-blue-500 text-white'
    }`;
    
    notification.innerHTML = `
        <div class="flex items-center">
            <span class="mr-2">${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</span>
            <span>${message}</span>
        </div>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        notification.style.transition = 'opacity 0.5s ease-out';
        notification.style.opacity = '0';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 500);
    }, 5000);
}

// Auto-close success messages after 5 seconds
setTimeout(function() {
    const successMessages = document.querySelectorAll('.bg-green-50');
    successMessages.forEach(function(msg) {
        msg.style.transition = 'opacity 0.5s ease-out';
        msg.style.opacity = '0';
        setTimeout(function() {
            msg.remove();
        }, 500);
    });
}, 5000);
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>