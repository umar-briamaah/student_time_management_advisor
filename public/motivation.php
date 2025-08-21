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

// Get user data
$streak_info = get_streak_info($user['id']);
$user_badges = get_user_badges($user['id']);
$progress = get_task_progress($user['id']);
$milestone = calculate_streak_milestone($streak_info['current_streak']);

// Motivational quotes
$quotes = [
    "The only way to do great work is to love what you do. - Steve Jobs",
    "Success is not final, failure is not fatal: it is the courage to continue that counts. - Winston Churchill",
    "The future depends on what you do today. - Mahatma Gandhi",
    "Don't watch the clock; do what it does. Keep going. - Sam Levenson",
    "The only limit to our realization of tomorrow is our doubts of today. - Franklin D. Roosevelt",
    "It always seems impossible until it's done. - Nelson Mandela",
    "The way to get started is to quit talking and begin doing. - Walt Disney",
    "Success is walking from failure to failure with no loss of enthusiasm. - Winston Churchill"
];

$daily_quote = $quotes[array_rand($quotes)];

include __DIR__ . '/../includes/layout/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 rounded-xl p-8 text-white text-center relative overflow-hidden">
        <!-- Background decorative elements -->
        <div class="absolute inset-0">
            <div class="absolute top-0 left-1/4 w-32 h-32 bg-white bg-opacity-10 rounded-full -ml-16 -mt-16"></div>
            <div class="absolute bottom-0 right-1/4 w-24 h-24 bg-white bg-opacity-10 rounded-full -mr-12 -mb-12"></div>
        </div>
        
        <div class="relative z-10">
            <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
            </div>
            <h1 class="text-4xl font-bold mb-4">Motivation Hub</h1>
            <p class="text-xl text-purple-100">Stay inspired and track your achievements</p>
        </div>
    </div>

    <!-- Daily Quote -->
    <div class="bg-gradient-to-br from-white to-purple-50 rounded-xl shadow-lg p-8 border border-purple-100">
        <div class="text-center">
            <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
            </div>
            <blockquote class="text-2xl text-gray-800 italic mb-4 leading-relaxed">"<?php echo h($daily_quote); ?>"</blockquote>
            <div class="inline-flex items-center bg-gradient-to-r from-purple-100 to-pink-100 text-purple-800 px-4 py-2 rounded-full text-sm font-medium">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Today's motivation
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Streak & Progress -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Current Streak -->
            <div class="bg-white rounded-xl shadow">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-xl font-semibold text-gray-800">Your Streak</h2>
                    <p class="text-sm text-gray-600 mt-1">Keep the momentum going!</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div class="text-center">
                            <div class="text-4xl font-bold text-blue-600 mb-2"><?php echo $streak_info['current_streak']; ?></div>
                            <div class="text-sm text-gray-600">Current Streak</div>
                            <div class="text-xs text-gray-500 mt-1">days</div>
                        </div>
                        <div class="text-center">
                            <div class="text-4xl font-bold text-purple-600 mb-2"><?php echo $streak_info['longest_streak']; ?></div>
                            <div class="text-sm text-gray-600">Longest Streak</div>
                            <div class="text-xs text-gray-500 mt-1">days</div>
                        </div>
                    </div>
                    
                    <!-- Streak Progress -->
                    <div class="mt-6">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600">Progress to next milestone</span>
                            <span class="font-medium text-gray-900">
                                <?php 
                                $next_milestone = 0;
                                if ($streak_info['current_streak'] < 3) $next_milestone = 3;
                                elseif ($streak_info['current_streak'] < 7) $next_milestone = 7;
                                elseif ($streak_info['current_streak'] < 14) $next_milestone = 14;
                                elseif ($streak_info['current_streak'] < 21) $next_milestone = 21;
                                elseif ($streak_info['current_streak'] < 30) $next_milestone = 30;
                                else $next_milestone = $streak_info['current_streak'];
                                
                                if ($next_milestone > $streak_info['current_streak']) {
                                    echo $streak_info['current_streak'] . '/' . $next_milestone;
                                } else {
                                    echo "Maximum achieved!";
                                }
                                ?>
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <?php 
                            $progress_percent = 0;
                            if ($next_milestone > $streak_info['current_streak']) {
                                $progress_percent = ($streak_info['current_streak'] / $next_milestone) * 100;
                            } else {
                                $progress_percent = 100;
                            }
                            ?>
                            <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-3 rounded-full transition-all duration-300" 
                                 style="width: <?php echo $progress_percent; ?>%"></div>
                        </div>
                    </div>

                    <?php if ($milestone['achieved']): ?>
                        <div class="mt-4 bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-green-800 font-medium">
                                    🎉 Congratulations! You've achieved a <?php echo $milestone['value']; ?>-<?php echo $milestone['type']; ?> streak!
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Task Progress Overview -->
            <div class="bg-white rounded-xl shadow">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-xl font-semibold text-gray-800">Task Progress</h2>
                    <p class="text-sm text-gray-600 mt-1">Your overall completion rate</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600"><?php echo $progress['total']; ?></div>
                            <div class="text-sm text-gray-600">Total Tasks</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600"><?php echo $progress['completed']; ?></div>
                            <div class="text-sm text-gray-600">Completed</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-yellow-600"><?php echo $progress['pending']; ?></div>
                            <div class="text-sm text-gray-600">Pending</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-red-600"><?php echo $progress['overdue']; ?></div>
                            <div class="text-sm text-gray-600">Overdue</div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600">Completion Rate</span>
                            <span class="font-medium text-gray-900"><?php echo $progress['completion_rate']; ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-gradient-to-r from-green-500 to-blue-500 h-3 rounded-full transition-all duration-300" 
                                 style="width: <?php echo $progress['completion_rate']; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Badges Section -->
        <div class="space-y-6">
            <!-- Badges Overview -->
            <div class="bg-white rounded-xl shadow">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-800">Badges Earned</h3>
                    <p class="text-sm text-gray-600 mt-1"><?php echo count($user_badges); ?> of 5 badges</p>
                </div>
                <div class="p-6">
                    <?php if (empty($user_badges)): ?>
                        <div class="text-center py-6">
                            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                            </svg>
                            <p class="text-gray-500 mb-2">No badges yet</p>
                            <p class="text-sm text-gray-400">Complete tasks to earn your first badge!</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach($user_badges as $badge): ?>
                                <div class="flex items-center gap-3 p-3 bg-gradient-to-r from-yellow-50 to-orange-50 rounded-lg border border-yellow-200">
                                    <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900"><?php echo h($badge['label']); ?></div>
                                        <div class="text-xs text-gray-500">
                                            Earned <?php echo date('M j, Y', strtotime($badge['awarded_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Available Badges -->
            <div class="bg-white rounded-xl shadow">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-800">Available Badges</h3>
                    <p class="text-sm text-gray-600 mt-1">Keep working to unlock these</p>
                </div>
                <div class="p-6">
                    <?php
                    $all_badges = [
                        ['code' => 'FIRST_TASK', 'label' => 'First Task Completed', 'description' => 'Complete your first task'],
                        ['code' => 'THREE_DAY_STREAK', 'label' => '3-Day Streak', 'description' => 'Maintain a 3-day streak'],
                        ['code' => 'SEVEN_DAY_STREAK', 'label' => '7-Day Streak', 'description' => 'Maintain a 7-day streak'],
                        ['code' => 'ON_TIME_SUBMIT', 'label' => 'On-Time Submission', 'description' => 'Complete a task before deadline'],
                        ['code' => 'DEEP_FOCUS_120', 'label' => 'Deep Focus 120', 'description' => 'Complete a 120+ minute task']
                    ];
                    
                    foreach($all_badges as $badge):
                        $earned = false;
                        foreach($user_badges as $user_badge) {
                            if ($user_badge['code'] === $badge['code']) {
                                $earned = true;
                                break;
                            }
                        }
                    ?>
                        <div class="flex items-center gap-3 p-3 mb-3 rounded-lg <?php echo $earned ? 'bg-green-50 border border-green-200' : 'bg-gray-50 border border-gray-200'; ?>">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center <?php echo $earned ? 'bg-green-100' : 'bg-gray-200'; ?>">
                                <?php if ($earned): ?>
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                <?php else: ?>
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <div class="font-medium text-gray-900 <?php echo $earned ? '' : 'text-gray-600'; ?>">
                                    <?php echo h($badge['label']); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?php echo h($badge['description']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-800">Quick Actions</h3>
                </div>
                <div class="p-6 space-y-3">
                    <a href="<?php echo APP_URL; ?>/tasks.php" class="block w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-center font-medium">
                        + Add New Task
                    </a>
                    <a href="<?php echo APP_URL; ?>/dashboard.php" class="block w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors text-center font-medium">
                        View Dashboard
                    </a>
                    <a href="<?php echo APP_URL; ?>/reports.php" class="block w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors text-center font-medium">
                        View Reports
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Motivation Tips -->
    <div class="bg-white rounded-xl shadow">
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-xl font-semibold text-gray-800">Motivation Tips</h2>
            <p class="text-sm text-gray-600 mt-1">Ways to stay motivated and productive</p>
        </div>
        <div class="p-6">
            <div class="grid md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-blue-600 font-bold text-sm">1</span>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 mb-1">Break Down Large Tasks</h4>
                            <p class="text-sm text-gray-600">Divide big assignments into smaller, manageable chunks to avoid feeling overwhelmed.</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-green-600 font-bold text-sm">2</span>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 mb-1">Use the Pomodoro Technique</h4>
                            <p class="text-sm text-gray-600">Work for 25 minutes, then take a 5-minute break to maintain focus and energy.</p>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-purple-600 font-bold text-sm">3</span>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 mb-1">Celebrate Small Wins</h4>
                            <p class="text-sm text-gray-600">Acknowledge your progress, no matter how small. Every completed task is a step forward.</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-orange-600 font-bold text-sm">4</span>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 mb-1">Maintain Consistency</h4>
                            <p class="text-sm text-gray-600">Try to work on tasks daily, even if just for a short time. Consistency builds momentum.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>