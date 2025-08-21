<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Set error reporting for cron jobs
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Log function for cron jobs
function cron_log($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Log to file if configured
    $log_file = $_ENV['LOG_FILE'] ?? '/var/log/sta_streaks.log';
    file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
    
    // Also output to stdout for cron logging
    echo $log_message;
}

try {
    cron_log("Starting streak calculation process");
    
    $pdo = DB::conn();
    
    // Get all users
    $users = $pdo->query("SELECT id, name FROM users")->fetchAll();
    cron_log("Processing " . count($users) . " users");
    
    $yesterday = (new DateTime('yesterday'))->format('Y-m-d');
    $processed_count = 0;
    $error_count = 0;
    
    foreach ($users as $user) {
        try {
            $user_id = $user['id'];
            $user_name = $user['name'];
            
            // Check if user completed any tasks yesterday
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as completed_count, 
                       SUM(estimated_minutes) as total_time
                FROM tasks 
                WHERE user_id = ? AND DATE(completed_at) = ?
            ");
            $stmt->execute([$user_id, $yesterday]);
            $result = $stmt->fetch();
            
            $completed_count = (int)$result['completed_count'];
            $total_time = (int)$result['total_time'];
            $was_active = $completed_count > 0;
            
            // Get current streak info
            $streak_stmt = $pdo->prepare("SELECT * FROM streaks WHERE user_id = ?");
            $streak_stmt->execute([$user_id]);
            $streak = $streak_stmt->fetch();
            
            if (!$streak) {
                // Create new streak record
                $pdo->prepare("
                    INSERT INTO streaks (user_id, current_streak, longest_streak, last_active_date) 
                    VALUES (?, ?, ?, ?)
                ")->execute([
                    $user_id, 
                    $was_active ? 1 : 0, 
                    $was_active ? 1 : 0, 
                    $was_active ? $yesterday : null
                ]);
                
                $current_streak = $was_active ? 1 : 0;
                $longest_streak = $was_active ? 1 : 0;
                $last_active = $was_active ? $yesterday : null;
                
                cron_log("Created new streak record for user '$user_name': " . ($was_active ? 'Active' : 'Inactive'));
            } else {
                $current_streak = (int)$streak['current_streak'];
                $longest_streak = (int)$streak['longest_streak'];
                $last_active = $streak['last_active_date'];
                
                if ($was_active) {
                    // Check if this continues the streak
                    if ($last_active === (new DateTime('yesterday -1 day'))->format('Y-m-d')) {
                        // Consecutive day - continue streak
                        $current_streak++;
                        $longest_streak = max($longest_streak, $current_streak);
                        cron_log("User '$user_name' continued streak: $current_streak days");
                    } else {
                        // Break in streak - start new one
                        $current_streak = 1;
                        cron_log("User '$user_name' started new streak after break");
                    }
                    $last_active = $yesterday;
                } else {
                    // No activity yesterday - streak remains but doesn't increase
                    cron_log("User '$user_name' had no activity yesterday, streak remains: $current_streak days");
                }
                
                // Update streak record
                $pdo->prepare("
                    UPDATE streaks 
                    SET current_streak = ?, longest_streak = ?, last_active_date = ? 
                    WHERE user_id = ?
                ")->execute([$current_streak, $longest_streak, $last_active, $user_id]);
            }
            
            // Award badges based on streaks and activity
            if ($was_active) {
                $badges_awarded = [];
                
                // First task completion badge
                $first_task_stmt = $pdo->prepare("
                    SELECT COUNT(*) as total_completed 
                    FROM tasks 
                    WHERE user_id = ? AND completed = 1
                ");
                $first_task_stmt->execute([$user_id]);
                $total_completed = (int)$first_task_stmt->fetch()['total_completed'];
                
                if ($total_completed === 1) {
                    $pdo->prepare("
                        INSERT IGNORE INTO user_badges (user_id, badge_id)
                        SELECT ?, id FROM badges WHERE code = 'FIRST_TASK'
                    ")->execute([$user_id]);
                    $badges_awarded[] = 'FIRST_TASK';
                }
                
                // Streak-based badges
                if ($current_streak === 3) {
                    $pdo->prepare("
                        INSERT IGNORE INTO user_badges (user_id, badge_id)
                        SELECT ?, id FROM badges WHERE code = 'THREE_DAY_STREAK'
                    ")->execute([$user_id]);
                    $badges_awarded[] = 'THREE_DAY_STREAK';
                }
                
                if ($current_streak === 7) {
                    $pdo->prepare("
                        INSERT IGNORE INTO user_badges (user_id, badge_id)
                        SELECT ?, id FROM badges WHERE code = 'SEVEN_DAY_STREAK'
                    ")->execute([$user_id]);
                    $badges_awarded[] = 'SEVEN_DAY_STREAK';
                }
                
                if ($current_streak === 14) {
                    $pdo->prepare("
                        INSERT IGNORE INTO user_badges (user_id, badge_id)
                        SELECT ?, id FROM badges WHERE code = 'FOURTEEN_DAY_STREAK'
                    ")->execute([$user_id]);
                    $badges_awarded[] = 'FOURTEEN_DAY_STREAK';
                }
                
                if ($current_streak === 21) {
                    $pdo->prepare("
                        INSERT IGNORE INTO user_badges (user_id, badge_id)
                        SELECT ?, id FROM badges WHERE code = 'TWENTY_ONE_DAY_STREAK'
                    ")->execute([$user_id]);
                    $badges_awarded[] = 'TWENTY_ONE_DAY_STREAK';
                }
                
                if ($current_streak === 30) {
                    $pdo->prepare("
                        INSERT IGNORE INTO user_badges (user_id, badge_id)
                        SELECT ?, id FROM badges WHERE code = 'THIRTY_DAY_STREAK'
                    ")->execute([$user_id]);
                    $badges_awarded[] = 'THIRTY_DAY_STREAK';
                }
                
                // Deep focus badge (120+ minutes in a day)
                if ($total_time >= 120) {
                    $pdo->prepare("
                        INSERT IGNORE INTO user_badges (user_id, badge_id)
                        SELECT ?, id FROM badges WHERE code = 'DEEP_FOCUS_120'
                    ")->execute([$user_id]);
                    $badges_awarded[] = 'DEEP_FOCUS_120';
                }
                
                // On-time submission badge (completed before due)
                $on_time_stmt = $pdo->prepare("
                    SELECT COUNT(*) as on_time_count
                    FROM tasks 
                    WHERE user_id = ? AND completed = 1 AND completed_at <= due_at
                ");
                $on_time_stmt->execute([$user_id]);
                $on_time_count = (int)$on_time_stmt->fetch()['on_time_count'];
                
                if ($on_time_count === 1) {
                    $pdo->prepare("
                        INSERT IGNORE INTO user_badges (user_id, badge_id)
                        SELECT ?, id FROM badges WHERE code = 'ON_TIME_SUBMIT'
                    ")->execute([$user_id]);
                    $badges_awarded[] = 'ON_TIME_SUBMIT';
                }
                
                if (!empty($badges_awarded)) {
                    cron_log("User '$user_name' awarded badges: " . implode(', ', $badges_awarded));
                }
            }
            
            $processed_count++;
            
        } catch (Exception $e) {
            cron_log("Error processing user ID {$user['id']}: " . $e->getMessage(), 'ERROR');
            $error_count++;
        }
    }
    
    cron_log("Streak calculation completed. Processed: $processed_count, Errors: $error_count");
    
    // Add missing streak records for new users
    $missing_streaks = $pdo->prepare("
        SELECT u.id, u.name 
        FROM users u 
        LEFT JOIN streaks s ON u.id = s.user_id 
        WHERE s.user_id IS NULL
    ");
    $missing_streaks->execute();
    $new_users = $missing_streaks->fetchAll();
    
    if (!empty($new_users)) {
        foreach ($new_users as $new_user) {
            $pdo->prepare("
                INSERT INTO streaks (user_id, current_streak, longest_streak, last_active_date) 
                VALUES (?, 0, 0, NULL)
            ")->execute([$new_user['id']]);
            cron_log("Created streak record for new user: {$new_user['name']}");
        }
    }
    
    // Clean up old data (optional)
    $cleanup_stmt = $pdo->prepare("
        DELETE FROM user_badges 
        WHERE awarded_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)
    ");
    $cleanup_stmt->execute();
    $deleted_badges = $cleanup_stmt->rowCount();
    
    if ($deleted_badges > 0) {
        cron_log("Cleaned up $deleted_badges old badge records");
    }
    
} catch (Exception $e) {
    cron_log("Critical error in streak calculation: " . $e->getMessage(), 'ERROR');
    exit(1);
}