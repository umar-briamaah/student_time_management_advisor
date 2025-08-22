<?php
/**
 * Cron job to calculate and update user streaks
 * Run daily at 00:10: 10 0 * * * php /path/to/cron/calculate_streaks.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Set timezone from environment
date_default_timezone_set(TIMEZONE);

// Log start of streak calculation process
log_message('info', 'Starting streak calculation process', [
    'cron_job' => 'calculate_streaks',
    'time' => date('Y-m-d H:i:s'),
    'streak_time' => CRON_STREAK_TIME
]);

try {
    $pdo = DB::conn();
    
    // Get all users
    $stmt = $pdo->query("SELECT id, username, email FROM users WHERE active = 1");
    $users = $stmt->fetchAll();
    
    log_message('info', 'Processing streaks for users', [
        'total_users' => count($users)
    ]);
    
    $updated_count = 0;
    $error_count = 0;
    
    foreach ($users as $user) {
        try {
            // Calculate current streak
            $streak_stmt = $pdo->prepare("
                SELECT COUNT(*) as streak_days
                FROM (
                    SELECT DATE(completed_at) as completion_date
                FROM tasks 
                    WHERE user_id = ? 
                    AND status = 'completed' 
                    AND completed_at IS NOT NULL
                    GROUP BY DATE(completed_at)
                    ORDER BY completion_date DESC
                ) as daily_completions
                WHERE completion_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ");
            
            $streak_stmt->execute([$user['id']]);
            $streak_result = $streak_stmt->fetch();
            $current_streak = $streak_result['streak_days'] ?? 0;
            
            // Get longest streak
            $longest_stmt = $pdo->prepare("
                SELECT MAX(streak_days) as longest_streak
                FROM user_streaks 
                WHERE user_id = ?
            ");
            
            $longest_stmt->execute([$user['id']]);
            $longest_result = $longest_stmt->fetch();
            $longest_streak = $longest_result['longest_streak'] ?? 0;
            
            // Update or insert streak record
            $upsert_stmt = $pdo->prepare("
                INSERT INTO user_streaks (user_id, current_streak, longest_streak, last_updated) 
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                current_streak = VALUES(current_streak),
                longest_streak = GREATEST(longest_streak, VALUES(current_streak)),
                last_updated = NOW()
            ");
            
            $upsert_stmt->execute([$user['id'], $current_streak, $longest_streak]);
            $updated_count++;
            
            log_message('info', 'Streak calculated for user', [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'current_streak' => $current_streak,
                'longest_streak' => $longest_streak
            ]);
            
        } catch (Exception $e) {
            $error_count++;
            log_message('error', 'Failed to calculate streak for user', [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'error' => $e->getMessage()
            ]);
        }
    }
    
    log_message('info', 'Streak calculation process completed', [
        'total_users' => count($users),
        'users_updated' => $updated_count,
        'errors' => $error_count
    ]);
    
} catch (Exception $e) {
    log_message('error', 'Critical error in streak calculation process', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // Log the error for admin review
    log_message('critical', 'Streak calculation cron job failed - manual intervention required', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}