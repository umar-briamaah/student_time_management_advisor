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

// Fetch tasks and compute priorities (with limit for performance)
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id=? ORDER BY due_at ASC LIMIT 50");
$stmt->execute([$user['id']]);
$tasks = $stmt->fetchAll();

// Compute priorities
usort($tasks, function($a,$b){
    return priority_score($b) <=> priority_score($a);
});

// Get optimized statistics (now cached)
$stats = get_user_statistics($user['id']);
$upcoming = get_upcoming_deadlines($user['id'], 5);
$recent_completions = get_recent_completions($user['id'], 3);
$streak_info = get_streak_info($user['id']);
$milestone = calculate_streak_milestone($streak_info['current_streak']);

include __DIR__ . '/../includes/layout/header.php';
?>

<div class="space-y-4 sm:space-y-6">
    <!-- Welcome Section -->
    <div class="bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 rounded-xl p-4 sm:p-6 text-white relative overflow-hidden">
        <!-- Background decorative elements -->
        <div class="absolute inset-0">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white bg-opacity-10 rounded-full -mr-16 -mt-16"></div>
            <div class="absolute bottom-0 left-0 w-24 h-24 bg-white bg-opacity-10 rounded-full -ml-12 -mb-12"></div>
            <div class="absolute top-1/2 right-1/4 w-16 h-16 bg-white bg-opacity-5 rounded-full"></div>
        </div>
        
        <div class="relative z-10 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center backdrop-blur-sm">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold mb-2">Welcome back, <?php echo h($user['name']); ?>! 👋</h1>
                    <p class="text-blue-100 text-sm sm:text-base">Let's tackle your priorities today</p>
                </div>
            </div>
            <div class="text-right">
                <div class="text-4xl font-bold"><?php echo date('j'); ?></div>
                <div class="text-blue-100"><?php echo date('M Y'); ?></div>
            </div>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-xl shadow-lg border border-blue-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="p-3 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-md">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-600 font-medium">Total Tasks</p>
                        <p class="text-2xl font-bold text-blue-800"><?php echo $stats['total']; ?></p>
                    </div>
                </div>
                <div class="text-blue-400 opacity-20">
                    <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-xl shadow-lg border border-green-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="p-3 bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-md">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-600 font-medium">Completed</p>
                        <p class="text-2xl font-bold text-green-800"><?php echo $stats['completed']; ?></p>
                    </div>
                </div>
                <div class="text-green-400 opacity-20">
                    <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 p-4 rounded-xl shadow-lg border border-yellow-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="p-3 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-md">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-600 font-medium">Pending</p>
                        <p class="text-2xl font-bold text-yellow-800"><?php echo $stats['pending']; ?></p>
                    </div>
                </div>
                <div class="text-yellow-400 opacity-20">
                    <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-red-50 to-red-100 p-4 rounded-xl shadow-lg border border-red-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="p-3 bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-md">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-600 font-medium">Overdue</p>
                        <p class="text-2xl font-bold text-red-800"><?php echo $stats['overdue']; ?></p>
                    </div>
                </div>
                <div class="text-red-400 opacity-20">
                    <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.7.59L19 8l-9 9z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Bar -->
    <?php if ($stats['total'] > 0): ?>
    <div class="bg-white p-4 rounded-xl shadow">
        <div class="flex justify-between items-center mb-2">
            <h3 class="font-semibold text-gray-700">Overall Progress</h3>
            <span class="text-sm text-gray-500"><?php echo $stats['completion_rate']; ?>%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="bg-gradient-to-r from-blue-500 to-green-500 h-3 rounded-full transition-all duration-300" 
                 style="width: <?php echo $stats['completion_rate']; ?>%"></div>
        </div>
        <?php if ($stats['avg_completion_time'] > 0): ?>
        <p class="text-xs text-gray-500 mt-2">Average completion time: <?php echo $stats['avg_completion_time']; ?> minutes</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="grid lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- Top Priorities -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow">
            <div class="p-4 sm:p-6 border-b border-gray-100">
                <h2 class="text-lg sm:text-xl font-semibold text-gray-800">Top Priorities</h2>
                <p class="text-sm text-gray-600 mt-1">Tasks ranked by urgency and importance</p>
            </div>
            <div class="p-4 sm:p-6">
                <?php if (empty($tasks)): ?>
                    <div class="text-center py-8">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <p class="text-gray-500">No tasks yet. Create your first task to get started!</p>
                        <a href="<?php echo APP_URL; ?>/tasks.php" class="inline-block mt-3 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">Create Task</a>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach(array_slice($tasks, 0, 5) as $task): ?>
                            <?php $priority = priority_score($task); ?>
                            <div class="flex items-center justify-between p-3 sm:p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 sm:gap-3 mb-2 flex-wrap">
                                        <span class="px-2 sm:px-3 py-1 text-xs font-medium rounded-full border <?php echo get_category_color($task['category']); ?>">
                                            <?php echo h($task['category']); ?>
                                        </span>
                                        <?php if ($priority > 0): ?>
                                            <span class="px-2 py-1 text-xs font-medium rounded <?php echo get_priority_color($priority); ?>">
                                                Priority: <?php echo $priority; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <h4 class="font-medium text-gray-900 mb-1 text-sm sm:text-base truncate"><?php echo h($task['title']); ?></h4>
                                    <div class="text-xs sm:text-sm text-gray-600">
                                        <?php echo format_due_date($task['due_at']); ?>
                                        <?php if ($task['estimated_minutes']): ?>
                                            • <?php echo $task['estimated_minutes']; ?> min
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (!$task['completed']): ?>
                                    <form method="post" action="<?php echo APP_URL; ?>/tasks.php" class="ml-2 sm:ml-3 flex-shrink-0">
                                        <input type="hidden" name="complete_task_id" value="<?php echo $task['id']; ?>">
                                        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                                        <button class="text-xs sm:text-sm bg-green-600 text-white px-2 sm:px-3 py-1 sm:py-2 rounded hover:bg-green-700 transition-colors font-medium">
                                            Mark Done
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-green-600 font-medium text-xs sm:text-sm ml-2 sm:ml-3">✓ Completed</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($tasks) > 5): ?>
                        <div class="mt-4 text-center">
                            <a href="<?php echo APP_URL; ?>/tasks.php" class="text-blue-600 hover:text-blue-700 font-medium">
                                View all <?php echo count($tasks); ?> tasks →
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="space-y-4 sm:space-y-6">
            <!-- Streaks & Badges -->
            <div class="bg-white rounded-xl shadow">
                <div class="p-4 sm:p-6 border-b border-gray-100">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-800">Streaks & Badges</h3>
                </div>
                <div class="p-4 sm:p-6">
                    <div class="text-center mb-4">
                        <div class="text-2xl sm:text-3xl font-bold text-blue-600 mb-1"><?php echo $streak_info['current_streak']; ?></div>
                        <div class="text-xs sm:text-sm text-gray-600">Current Streak</div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="flex justify-between text-xs sm:text-sm mb-1">
                            <span>Longest Streak</span>
                            <span class="font-medium"><?php echo $streak_info['longest_streak']; ?> days</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full transition-all duration-300" 
                                 style="width: <?php echo min(100, ($streak_info['current_streak'] / max(1, $streak_info['longest_streak'])) * 100); ?>%"></div>
                        </div>
                    </div>

                    <?php if ($milestone['achieved']): ?>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-3">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-xs sm:text-sm text-green-800 font-medium">
                                    🎉 <?php echo $milestone['value']; ?>-<?php echo $milestone['type']; ?> streak achieved!
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <a href="<?php echo APP_URL; ?>/motivation.php" class="block w-full text-center bg-blue-50 text-blue-700 px-3 sm:px-4 py-2 rounded-lg hover:bg-blue-100 transition-colors text-xs sm:text-sm font-medium">
                        View All Badges
                    </a>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow">
                <div class="p-4 sm:p-6 border-b border-gray-100">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-800">Quick Actions</h3>
                </div>
                <div class="p-4 sm:p-6 space-y-2 sm:space-y-3">
                    <a href="<?php echo APP_URL; ?>/tasks.php" class="block w-full bg-blue-600 text-white px-3 sm:px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-center font-medium text-sm">
                        + Add New Task
                    </a>
                    <a href="<?php echo APP_URL; ?>/calendar.php" class="block w-full bg-gray-100 text-gray-700 px-3 sm:px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors text-center font-medium text-sm">
                        View Calendar
                    </a>
                    <a href="<?php echo APP_URL; ?>/reports.php" class="block w-full bg-gray-100 text-gray-700 px-3 sm:px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors text-center font-medium text-sm">
                        View Reports
                    </a>
                </div>
            </div>

            <!-- Recent Completions -->
            <?php if (!empty($recent_completions)): ?>
            <div class="bg-white rounded-xl shadow">
                <div class="p-4 sm:p-6 border-b border-gray-100">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-800">Recent Completions</h3>
                </div>
                <div class="p-4 sm:p-6">
                    <div class="space-y-2 sm:space-y-3">
                        <?php foreach($recent_completions as $task): ?>
                            <div class="flex items-center gap-2 sm:gap-3">
                                <div class="w-2 h-2 bg-green-500 rounded-full flex-shrink-0"></div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs sm:text-sm font-medium text-gray-900 truncate"><?php echo h($task['title']); ?></div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo date('M j', strtotime($task['completed_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>