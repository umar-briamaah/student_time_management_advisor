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
            <div class="absolute top-0 right-0 w-20 h-20 bg-white bg-opacity-10 rounded-full -mr-10 -mt-10"></div>
            <div class="absolute bottom-0 left-0 w-16 h-16 bg-white bg-opacity-10 rounded-full -ml-8 -mb-8"></div>
            <div class="absolute top-1/2 right-1/4 w-12 h-12 bg-white bg-opacity-5 rounded-full"></div>
        </div>
        
        <div class="relative z-10 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center backdrop-blur-sm">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

    <!-- Motivation of the Day -->
    <div class="bg-gradient-to-r from-orange-500 via-pink-500 to-purple-600 rounded-xl p-6 text-white relative overflow-hidden shadow-xl">
        <!-- Background decorative elements -->
        <div class="absolute inset-0">
            <div class="absolute top-0 right-0 w-24 h-24 bg-white bg-opacity-10 rounded-full -mr-12 -mt-12"></div>
            <div class="absolute bottom-0 left-0 w-20 h-20 bg-white bg-opacity-10 rounded-full -ml-10 -mb-10"></div>
            <div class="absolute top-1/2 right-1/3 w-16 h-16 bg-white bg-opacity-5 rounded-full"></div>
        </div>
        
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center backdrop-blur-sm">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl sm:text-2xl font-bold">Motivation of the Day</h2>
                        <p class="text-orange-100 text-sm"><?php echo date('l, F j'); ?></p>
                    </div>
                </div>
                <div class="hidden sm:block">
                    <div class="text-4xl">🌟</div>
                </div>
            </div>
            
            <?php
            // Array of motivational quotes for students
            $motivational_quotes = [
                "The only way to do great work is to love what you do. - Steve Jobs",
                "Success is not final, failure is not fatal: it is the courage to continue that counts. - Winston Churchill",
                "Education is the most powerful weapon which you can use to change the world. - Nelson Mandela",
                "The future belongs to those who believe in the beauty of their dreams. - Eleanor Roosevelt",
                "Don't watch the clock; do what it does. Keep going. - Sam Levenson",
                "The only limit to our realization of tomorrow is our doubts of today. - Franklin D. Roosevelt",
                "Believe you can and you're halfway there. - Theodore Roosevelt",
                "Your time is limited, don't waste it living someone else's life. - Steve Jobs",
                "The journey of a thousand miles begins with one step. - Lao Tzu",
                "What you get by achieving your goals is not as important as what you become by achieving your goals. - Zig Ziglar",
                "Success is walking from failure to failure with no loss of enthusiasm. - Winston Churchill",
                "The mind is not a vessel to be filled, but a fire to be kindled. - Plutarch",
                "Learning is not attained by chance, it must be sought for with ardor and attended to with diligence. - Abigail Adams",
                "The beautiful thing about learning is that nobody can take it away from you. - B.B. King",
                "Education is not preparation for life; education is life itself. - John Dewey"
            ];
            
            // Use date to select a quote (changes daily)
            $quote_index = (int)date('z') % count($motivational_quotes);
            $todays_quote = $motivational_quotes[$quote_index];
            ?>
            
            <div class="mb-4">
                <blockquote class="text-lg sm:text-xl font-medium italic leading-relaxed">
                    "<?php echo $todays_quote; ?>"
                </blockquote>
            </div>
            
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <span class="text-sm text-orange-100 font-medium">Daily Inspiration</span>
                </div>
                
                <a href="<?php echo APP_URL; ?>/motivation.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg transition-all duration-300 font-medium text-sm backdrop-blur-sm border border-white border-opacity-30">
                    More Motivation →
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="stats-card bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-xl shadow-lg border border-blue-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex items-center icon-foreground">
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
                <div class="icon-background text-blue-400">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stats-card bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-xl shadow-lg border border-green-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex items-center icon-foreground">
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
                <div class="icon-background text-green-400">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stats-card bg-gradient-to-br from-yellow-50 to-yellow-100 p-4 rounded-xl shadow-lg border border-yellow-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex items-center icon-foreground">
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
                <div class="icon-background text-yellow-400">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="stats-card bg-gradient-to-br from-red-50 to-red-100 p-4 rounded-xl shadow-lg border border-red-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex items-center icon-foreground">
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
                <div class="icon-background text-red-400">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
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
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            <!-- Daily Motivation Tip -->
            <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl shadow border border-indigo-100">
                <div class="p-4 sm:p-6 border-b border-indigo-100">
                    <h3 class="text-base sm:text-lg font-semibold text-indigo-800 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                        Today's Tip
                    </h3>
                </div>
                <div class="p-4 sm:p-6">
                    <?php
                    // Array of daily study tips
                    $study_tips = [
                        "Break large tasks into smaller, manageable chunks. It's easier to tackle 30-minute sessions than 3-hour marathons.",
                        "Use the Pomodoro Technique: 25 minutes of focused work, then a 5-minute break. Repeat!",
                        "Review your notes within 24 hours of learning. This helps retain information much better.",
                        "Create a dedicated study space. Your brain associates specific places with specific activities.",
                        "Practice active recall instead of passive reading. Test yourself on the material.",
                        "Connect new information to what you already know. This creates stronger neural pathways.",
                        "Get enough sleep! Your brain consolidates learning while you rest.",
                        "Stay hydrated and take regular breaks. Your brain works better when you're healthy.",
                        "Use spaced repetition for memorization. Review material at increasing intervals.",
                        "Teach someone else what you've learned. This is one of the best ways to master a topic."
                    ];
                    
                    // Use date to select a tip (changes daily)
                    $tip_index = (int)date('z') % count($study_tips);
                    $todays_tip = $study_tips[$tip_index];
                    ?>
                    
                    <div class="text-sm text-indigo-700 leading-relaxed mb-3">
                        <?php echo $todays_tip; ?>
                    </div>
                    
                    <div class="text-xs text-indigo-500 text-center">
                        💡 Tip #<?php echo ($tip_index + 1); ?> of <?php echo count($study_tips); ?>
                    </div>
                </div>
            </div>

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