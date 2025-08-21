<?php
require_once __DIR__ . '/db.php';

// Simple in-memory cache for session-based data
function get_cache_key($user_id, $type) {
    return "user_{$user_id}_{$type}";
}

function cache_get($key) {
    return $_SESSION['cache'][$key] ?? null;
}

function cache_set($key, $value, $ttl = 300) { // 5 minutes default
    $_SESSION['cache'][$key] = [
        'value' => $value,
        'expires' => time() + $ttl
    ];
}

function cache_is_valid($key) {
    if (!isset($_SESSION['cache'][$key])) return false;
    return $_SESSION['cache'][$key]['expires'] > time();
}

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function category_weight($cat){
    switch($cat){
        case 'Exam': return 1.0;
        case 'Assignment': return 0.8;
        case 'Lab': return 0.7;
        case 'Lecture': return 0.5;
        default: return 0.3;
    }
}

function priority_score($task){
    $now = new DateTime();
    $due = new DateTime($task['due_at']);
    $days_left = max(0, (int)$now->diff($due)->format('%r%a'));
    $base = ($days_left <= 2) ? 100 : (($days_left <= 7) ? 60 : 30);
    $weight = category_weight($task['category']);
    $progress_factor = $task['completed'] ? 0 : 1;
    return $base * $weight * $progress_factor;
}

function get_priority_color($priority_score) {
    if ($priority_score >= 80) return 'text-red-600 bg-red-100';
    if ($priority_score >= 50) return 'text-orange-600 bg-orange-100';
    if ($priority_score >= 20) return 'text-yellow-600 bg-yellow-100';
    return 'text-gray-600 bg-gray-100';
}

function get_category_color($category) {
    switch($category) {
        case 'Exam': return 'bg-red-100 text-red-700 border-red-200';
        case 'Assignment': return 'bg-blue-100 text-blue-700 border-blue-200';
        case 'Lab': return 'bg-green-100 text-green-700 border-green-200';
        case 'Lecture': return 'bg-purple-100 text-purple-700 border-purple-200';
        default: return 'bg-gray-100 text-gray-700 border-gray-200';
    }
}

function format_due_date($due_at) {
    $now = new DateTime();
    $due = new DateTime($due_at);
    $diff = $now->diff($due);
    
    if ($due < $now) {
        return '<span class="text-red-600 font-medium">Overdue</span>';
    }
    
    if ($diff->days == 0) {
        if ($diff->h == 0) {
            return '<span class="text-orange-600 font-medium">Due in ' . $diff->i . ' minutes</span>';
        }
        return '<span class="text-orange-600 font-medium">Due in ' . $diff->h . ' hours</span>';
    }
    
    if ($diff->days == 1) {
        return '<span class="text-yellow-600 font-medium">Due tomorrow</span>';
    }
    
    return '<span class="text-gray-600">Due in ' . $diff->days . ' days</span>';
}

// Optimized function to get all user statistics in one query
function get_user_statistics($user_id) {
    $cache_key = get_cache_key($user_id, 'statistics');
    
    if (cache_is_valid($cache_key)) {
        return cache_get($cache_key)['value'];
    }
    
    try {
        $pdo = DB::conn();
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(completed) as completed,
                SUM(CASE WHEN completed = 0 AND due_at < NOW() THEN 1 ELSE 0 END) as overdue,
                SUM(CASE WHEN completed = 0 AND due_at >= NOW() THEN 1 ELSE 0 END) as pending,
                AVG(CASE WHEN completed = 1 THEN estimated_minutes ELSE NULL END) as avg_completion_time,
                MAX(created_at) as last_task_created,
                MAX(CASE WHEN completed = 1 THEN completed_at ELSE NULL END) as last_task_completed
            FROM tasks 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        $stats = [
            'total' => (int)$result['total'],
            'completed' => (int)$result['completed'],
            'overdue' => (int)$result['overdue'],
            'pending' => (int)$result['pending'],
            'completion_rate' => $result['total'] > 0 ? round(($result['completed'] / $result['total']) * 100) : 0,
            'avg_completion_time' => round($result['avg_completion_time'] ?? 0),
            'last_task_created' => $result['last_task_created'],
            'last_task_completed' => $result['last_task_completed']
        ];
        
        cache_set($cache_key, $stats, 300); // Cache for 5 minutes
        return $stats;
        
    } catch (Exception $e) {
        error_log("Error getting user statistics: " . $e->getMessage());
        return [
            'total' => 0,
            'completed' => 0,
            'overdue' => 0,
            'pending' => 0,
            'completion_rate' => 0,
            'avg_completion_time' => 0,
            'last_task_created' => null,
            'last_task_completed' => null
        ];
    }
}

// Backward compatibility
function get_task_progress($user_id) {
    $stats = get_user_statistics($user_id);
    return [
        'total' => $stats['total'],
        'completed' => $stats['completed'],
        'overdue' => $stats['overdue'],
        'pending' => $stats['pending'],
        'completion_rate' => $stats['completion_rate']
    ];
}

function get_upcoming_deadlines($user_id, $limit = 5) {
    $cache_key = get_cache_key($user_id, "upcoming_{$limit}");
    
    if (cache_is_valid($cache_key)) {
        return cache_get($cache_key)['value'];
    }
    
    try {
        $pdo = DB::conn();
        $stmt = $pdo->prepare("
            SELECT * FROM tasks 
            WHERE user_id = ? AND completed = 0 
            ORDER BY due_at ASC 
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        $tasks = $stmt->fetchAll();
        
        cache_set($cache_key, $tasks, 180); // Cache for 3 minutes
        return $tasks;
        
    } catch (Exception $e) {
        error_log("Error getting upcoming deadlines: " . $e->getMessage());
        return [];
    }
}

function get_recent_completions($user_id, $limit = 5) {
    $cache_key = get_cache_key($user_id, "recent_{$limit}");
    
    if (cache_is_valid($cache_key)) {
        return cache_get($cache_key)['value'];
    }
    
    try {
        $pdo = DB::conn();
        $stmt = $pdo->prepare("
            SELECT * FROM tasks 
            WHERE user_id = ? AND completed = 1 
            ORDER BY completed_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        $tasks = $stmt->fetchAll();
        
        cache_set($cache_key, $tasks, 300); // Cache for 5 minutes
        return $tasks;
        
    } catch (Exception $e) {
        error_log("Error getting recent completions: " . $e->getMessage());
        return [];
    }
}

function award_badge_if_needed($user_id, $code){
    try {
        $pdo = DB::conn();
        $stmt = $pdo->prepare("SELECT id FROM badges WHERE code=?");
        $stmt->execute([$code]);
        $badge = $stmt->fetch();
        if(!$badge) return;
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES (?,?)");
        $stmt->execute([$user_id, $badge['id']]);
        
        // Clear cache when badges change
        $cache_key = get_cache_key($user_id, 'badges');
        unset($_SESSION['cache'][$cache_key]);
        
    } catch (Exception $e) {
        error_log("Error awarding badge: " . $e->getMessage());
    }
}

function get_user_badges($user_id) {
    $cache_key = get_cache_key($user_id, 'badges');
    
    if (cache_is_valid($cache_key)) {
        return cache_get($cache_key)['value'];
    }
    
    try {
        $pdo = DB::conn();
        $stmt = $pdo->prepare("
            SELECT b.*, ub.awarded_at 
            FROM badges b 
            JOIN user_badges ub ON b.id = ub.badge_id 
            WHERE ub.user_id = ? 
            ORDER BY ub.awarded_at DESC
        ");
        $stmt->execute([$user_id]);
        $badges = $stmt->fetchAll();
        
        cache_set($cache_key, $badges, 600); // Cache for 10 minutes
        return $badges;
        
    } catch (Exception $e) {
        error_log("Error getting user badges: " . $e->getMessage());
        return [];
    }
}

function get_streak_info($user_id) {
    $cache_key = get_cache_key($user_id, 'streak');
    
    if (cache_is_valid($cache_key)) {
        return cache_get($cache_key)['value'];
    }
    
    try {
        $pdo = DB::conn();
        $stmt = $pdo->prepare("SELECT * FROM streaks WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $streak = $stmt->fetch();
        
        if (!$streak) {
            $result = [
                'current_streak' => 0,
                'longest_streak' => 0,
                'last_active_date' => null
            ];
        } else {
            $result = $streak;
        }
        
        cache_set($cache_key, $result, 300); // Cache for 5 minutes
        return $result;
        
    } catch (Exception $e) {
        error_log("Error getting streak info: " . $e->getMessage());
        return [
            'current_streak' => 0,
            'longest_streak' => 0,
            'last_active_date' => null
        ];
    }
}

function calculate_streak_milestone($current_streak) {
    if ($current_streak >= 30) return ['type' => 'month', 'value' => 30, 'achieved' => true];
    if ($current_streak >= 21) return ['type' => 'week', 'value' => 21, 'achieved' => false];
    if ($current_streak >= 14) return ['type' => 'week', 'value' => 14, 'achieved' => true];
    if ($current_streak >= 7) return ['type' => 'week', 'value' => 7, 'achieved' => true];
    if ($current_streak >= 3) return ['type' => 'day', 'value' => 3, 'achieved' => true];
    return ['type' => 'day', 'value' => 1, 'achieved' => false];
}

function get_monthly_stats($user_id, $year = null, $month = null) {
    if (!$year) $year = date('Y');
    if (!$month) $month = date('n');
    
    $cache_key = get_cache_key($user_id, "monthly_{$year}_{$month}");
    
    if (cache_is_valid($cache_key)) {
        return cache_get($cache_key)['value'];
    }
    
    try {
        $pdo = DB::conn();
        $stmt = $pdo->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as created,
                SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed
            FROM tasks 
            WHERE user_id = ? AND YEAR(created_at) = ? AND MONTH(created_at) = ?
            GROUP BY DATE(created_at)
            ORDER BY date
        ");
        $stmt->execute([$user_id, $year, $month]);
        $stats = $stmt->fetchAll();
        
        cache_set($cache_key, $stats, 600); // Cache for 10 minutes
        return $stats;
        
    } catch (Exception $e) {
        error_log("Error getting monthly stats: " . $e->getMessage());
        return [];
    }
}

function validate_task_data($data) {
    $errors = [];
    
    if (empty(trim($data['title'] ?? ''))) {
        $errors[] = 'Task title is required';
    }
    
    if (empty($data['due_at'] ?? '')) {
        $errors[] = 'Due date is required';
    } elseif (strtotime($data['due_at']) === false) {
        $errors[] = 'Invalid due date format';
    }
    
    if (!empty($data['estimated_minutes'] ?? '') && (!is_numeric($data['estimated_minutes']) || $data['estimated_minutes'] < 1)) {
        $errors[] = 'Estimated minutes must be a positive number';
    }
    
    $valid_categories = ['Lecture', 'Lab', 'Exam', 'Assignment', 'Other'];
    if (!empty($data['category'] ?? '') && !in_array($data['category'], $valid_categories)) {
        $errors[] = 'Invalid category selected';
    }
    
    return $errors;
}

// Clear all cache for a user (useful when data changes)
function clear_user_cache($user_id) {
    if (isset($_SESSION['cache'])) {
        foreach (array_keys($_SESSION['cache']) as $key) {
            if (strpos($key, "user_{$user_id}_") === 0) {
                unset($_SESSION['cache'][$key]);
            }
        }
    }
}

// Get task count by category for charts
function get_task_count_by_category($user_id) {
    $cache_key = get_cache_key($user_id, 'category_counts');
    
    if (cache_is_valid($cache_key)) {
        return cache_get($cache_key)['value'];
    }
    
    try {
        $pdo = DB::conn();
        $stmt = $pdo->prepare("
            SELECT 
                category,
                COUNT(*) as total,
                SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed
            FROM tasks 
            WHERE user_id = ?
            GROUP BY category
            ORDER BY total DESC
        ");
        $stmt->execute([$user_id]);
        $counts = $stmt->fetchAll();
        
        cache_set($cache_key, $counts, 300); // Cache for 5 minutes
        return $counts;
        
    } catch (Exception $e) {
        error_log("Error getting category counts: " . $e->getMessage());
        return [];
    }
}