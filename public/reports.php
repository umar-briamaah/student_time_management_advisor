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

// Get filter parameters
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');

// Validate year and month
if ($year < 2020 || $year > 2030) $year = date('Y');
if ($month < 1 || $month > 12) $month = date('n');

// Get statistics
$progress = get_task_progress($user['id']);
$streak_info = get_streak_info($user['id']);
$monthly_stats = get_monthly_stats($user['id'], $year, $month);

// Category breakdown
$stmt = $pdo->prepare("
    SELECT category, 
           COUNT(*) as total,
           SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed,
           AVG(CASE WHEN completed = 1 THEN estimated_minutes ELSE NULL END) as avg_time
    FROM tasks 
    WHERE user_id = ? AND YEAR(created_at) = ? AND MONTH(created_at) = ?
    GROUP BY category
    ORDER BY total DESC
");
$stmt->execute([$user['id'], $year, $month]);
$category_stats = $stmt->fetchAll();

// Weekly breakdown for the month
$stmt = $pdo->prepare("
    SELECT 
        YEARWEEK(created_at) as week,
        COUNT(*) as created,
        SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed
    FROM tasks 
    WHERE user_id = ? AND YEAR(created_at) = ? AND MONTH(created_at) = ?
    GROUP BY YEARWEEK(created_at)
    ORDER BY week
");
$stmt->execute([$user['id'], $year, $month]);
$weekly_stats = $stmt->fetchAll();

// Overdue analysis
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_overdue,
        AVG(DATEDIFF(NOW(), due_at)) as avg_days_overdue,
        MAX(DATEDIFF(NOW(), due_at)) as max_days_overdue
    FROM tasks 
    WHERE user_id = ? AND completed = 0 AND due_at < NOW()
");
$stmt->execute([$user['id']]);
$overdue_stats = $stmt->fetch();

// Productivity score calculation
$total_tasks = $progress['total'];
$completed_tasks = $progress['completed'];
$overdue_tasks = $progress['overdue'];
$on_time_tasks = $completed_tasks - $overdue_tasks;

$productivity_score = 0;
if ($total_tasks > 0) {
    $completion_rate = $completed_tasks / $total_tasks;
    $timeliness_rate = $on_time_tasks / max(1, $completed_tasks);
    $productivity_score = round(($completion_rate * 0.6 + $timeliness_rate * 0.4) * 100);
}

include __DIR__ . '/../includes/layout/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-emerald-50 via-teal-50 to-cyan-50 rounded-xl p-6 mb-6 border border-emerald-100">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Analytics & Reports</h1>
                    <p class="text-gray-600 mt-1">Track your progress and performance</p>
                </div>
            </div>
            
            <!-- Date Filter -->
            <div class="mt-4 sm:mt-0 flex gap-2">
                <form method="get" class="flex gap-2">
                    <select name="month" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white shadow-sm">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo $m == $month ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                            </option>
                    <?php endfor; ?>
                </select>
                <select name="year" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    Filter
                </button>
            </form>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="grid md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl shadow border-l-4 border-blue-500">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">Productivity Score</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $productivity_score; ?>%</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow border-l-4 border-green-500">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">Completion Rate</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $progress['completion_rate']; ?>%</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow border-l-4 border-purple-500">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">Current Streak</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $streak_info['current_streak']; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow border-l-4 border-red-500">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">Overdue Tasks</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $progress['overdue']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Monthly Overview -->
        <div class="bg-white rounded-xl shadow">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-semibold text-gray-800">Monthly Overview</h2>
                <p class="text-sm text-gray-600 mt-1"><?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></p>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Tasks Created</span>
                        <span class="font-medium text-gray-900">
                            <?php echo array_sum(array_column($monthly_stats, 'created')); ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Tasks Completed</span>
                        <span class="font-medium text-gray-900">
                            <?php echo array_sum(array_column($monthly_stats, 'completed')); ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Completion Rate</span>
                        <span class="font-medium text-gray-900">
                            <?php 
                            $monthly_created = array_sum(array_column($monthly_stats, 'created'));
                            $monthly_completed = array_sum(array_column($monthly_stats, 'completed'));
                            echo $monthly_created > 0 ? round(($monthly_completed / $monthly_created) * 100) : 0;
                            ?>%
                        </span>
                    </div>
                </div>

                <!-- Simple Chart -->
                <?php if (!empty($monthly_stats)): ?>
                    <div class="mt-6">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Daily Activity</h4>
                        <div class="flex items-end gap-1 h-32">
                            <?php foreach($monthly_stats as $stat): ?>
                                <?php 
                                $max_created = max(array_column($monthly_stats, 'created'));
                                $height = $max_created > 0 ? ($stat['created'] / $max_created) * 100 : 0;
                                ?>
                                <div class="flex-1 bg-blue-100 rounded-t" style="height: <?php echo $height; ?>%">
                                    <div class="bg-blue-500 rounded-t" style="height: <?php echo $max_created > 0 ? ($stat['completed'] / $max_created) * 100 : 0; ?>%"></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 mt-2">
                            <span>Created</span>
                            <span>Completed</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Category Breakdown -->
        <div class="bg-white rounded-xl shadow">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-semibold text-gray-800">Category Breakdown</h2>
                <p class="text-sm text-gray-600 mt-1">Performance by task type</p>
            </div>
            <div class="p-6">
                <?php if (empty($category_stats)): ?>
                    <div class="text-center py-8 text-gray-500">
                        No data for this period
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach($category_stats as $cat): ?>
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="font-medium text-gray-900"><?php echo h($cat['category']); ?></span>
                                    <span class="text-sm text-gray-600">
                                        <?php echo $cat['completed']; ?>/<?php echo $cat['total']; ?>
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <?php 
                                    $percentage = $cat['total'] > 0 ? ($cat['completed'] / $cat['total']) * 100 : 0;
                                    ?>
                                    <div class="bg-blue-500 h-2 rounded-full transition-all duration-300" 
                                         style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500">
                                    <span><?php echo round($percentage); ?>% complete</span>
                                    <?php if ($cat['avg_time']): ?>
                                        <span>Avg: <?php echo round($cat['avg_time']); ?> min</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Weekly Breakdown -->
    <div class="bg-white rounded-xl shadow">
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-xl font-semibold text-gray-800">Weekly Breakdown</h2>
            <p class="text-sm text-gray-600 mt-1"><?php echo date('F Y', mktime(0, 0, 0, $month, 1, $year)); ?></p>
        </div>
        <div class="p-6">
            <?php if (empty($weekly_stats)): ?>
                <div class="text-center py-8 text-gray-500">
                    No weekly data for this period
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-2 text-sm font-medium text-gray-700">Week</th>
                                <th class="text-left py-2 text-sm font-medium text-gray-700">Tasks Created</th>
                                <th class="text-left py-2 text-sm font-medium text-gray-700">Tasks Completed</th>
                                <th class="text-left py-2 text-sm font-medium text-gray-700">Completion Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($weekly_stats as $week): ?>
                                <tr class="border-b border-gray-100">
                                    <td class="py-3 text-sm text-gray-900">
                                        <?php 
                                        $week_start = date('M j', strtotime(substr($week['week'], 0, 4) . 'W' . substr($week['week'], 4, 2) . '1'));
                                        $week_end = date('M j', strtotime(substr($week['week'], 0, 4) . 'W' . substr($week['week'], 4, 2) . '7'));
                                        echo $week_start . ' - ' . $week_end;
                                        ?>
                                    </td>
                                    <td class="py-3 text-sm text-gray-600"><?php echo $week['created']; ?></td>
                                    <td class="py-3 text-sm text-gray-600"><?php echo $week['completed']; ?></td>
                                    <td class="py-3 text-sm text-gray-600">
                                        <?php 
                                        echo $week['created'] > 0 ? round(($week['completed'] / $week['created']) * 100) : 0;
                                        ?>%
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Performance Insights -->
    <div class="bg-white rounded-xl shadow">
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-xl font-semibold text-gray-800">Performance Insights</h2>
            <p class="text-sm text-gray-600 mt-1">Key metrics and recommendations</p>
        </div>
        <div class="p-6">
            <div class="grid md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <h3 class="font-medium text-gray-900">Timeliness Analysis</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">On-time completions</span>
                            <span class="font-medium"><?php echo max(0, $on_time_tasks); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Overdue completions</span>
                            <span class="font-medium"><?php echo $overdue_tasks; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Timeliness rate</span>
                            <span class="font-medium">
                                <?php echo $completed_tasks > 0 ? round(($on_time_tasks / $completed_tasks) * 100) : 0; ?>%
                            </span>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="font-medium text-gray-900">Recommendations</h3>
                    <div class="space-y-3">
                        <?php if ($progress['overdue'] > 0): ?>
                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-yellow-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <span class="text-sm text-gray-700">Focus on completing overdue tasks to improve your timeliness score.</span>
                            </div>
                        <?php endif; ?>

                        <?php if ($progress['completion_rate'] < 70): ?>
                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-sm text-gray-700">Consider breaking down larger tasks into smaller, more manageable pieces.</span>
                            </div>
                        <?php endif; ?>

                        <?php if ($streak_info['current_streak'] < 3): ?>
                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <span class="text-sm text-gray-700">Try to maintain daily consistency to build momentum and improve your streak.</span>
                            </div>
                        <?php endif; ?>

                        <?php if ($progress['completion_rate'] >= 80 && $progress['overdue'] == 0): ?>
                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-sm text-gray-700">Excellent work! You're maintaining high productivity and timeliness.</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="bg-white rounded-xl shadow">
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-xl font-semibold text-gray-800">Export & Share</h2>
            <p class="text-sm text-gray-600 mt-1">Download your reports and share progress</p>
        </div>
        <div class="p-6">
            <div class="flex flex-wrap gap-3">
                <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export PDF
                </button>
                <button class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Export CSV
                </button>
                <button class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path>
                    </svg>
                    Share Report
                </button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>