<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/mailer.php';

// Set error reporting for cron jobs
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Log function for cron jobs
function cron_log($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Log to file if configured
    $log_file = $_ENV['LOG_FILE'] ?? '/var/log/sta_reminders.log';
    file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
    
    // Also output to stdout for cron logging
    echo $log_message;
}

try {
    cron_log("Starting reminder sending process");
    
    $pdo = DB::conn();
    
    // Get pending reminders that are due
    $stmt = $pdo->prepare("
        SELECT 
            r.id AS reminder_id, 
            r.send_at,
            t.id AS task_id,
            t.title, 
            t.description,
            t.due_at, 
            t.category,
            t.estimated_minutes,
            u.email,
            u.name
        FROM reminders r
        JOIN tasks t ON t.id = r.task_id
        JOIN users u ON u.id = r.user_id
        WHERE r.sent = 0 
        AND r.send_at <= NOW() 
        AND t.completed = 0
        ORDER BY r.send_at ASC
        LIMIT 200
    ");
    
    $stmt->execute();
    $reminders = $stmt->fetchAll();
    
    cron_log("Found " . count($reminders) . " reminders to send");
    
    $sent_count = 0;
    $error_count = 0;
    
    foreach ($reminders as $reminder) {
        try {
            // Calculate time until due
            $due_time = new DateTime($reminder['due_at']);
            $now = new DateTime();
            $time_diff = $now->diff($due_time);
            
            $time_until_due = '';
            if ($due_time < $now) {
                $time_until_due = 'OVERDUE';
            } elseif ($time_diff->days > 0) {
                $time_until_due = $time_diff->days . ' day' . ($time_diff->days > 1 ? 's' : '') . ' remaining';
            } elseif ($time_diff->h > 0) {
                $time_until_due = $time_diff->h . ' hour' . ($time_diff->h > 1 ? 's' : '') . ' remaining';
            } else {
                $time_until_due = $time_diff->i . ' minute' . ($time_diff->i > 1 ? 's' : '') . ' remaining';
            }
            
            // Create email content
            $subject = 'Reminder: ' . $reminder['title'];
            
            $body = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Task Reminder</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #3b82f6; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
                    .content { background: #f9fafb; padding: 20px; border-radius: 0 0 8px 8px; }
                    .task-info { background: white; padding: 15px; border-radius: 6px; margin: 15px 0; border-left: 4px solid #3b82f6; }
                    .urgent { border-left-color: #dc2626; }
                    .category { display: inline-block; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
                    .exam { background: #fee2e2; color: #dc2626; }
                    .assignment { background: #dbeafe; color: #2563eb; }
                    .lab { background: #dcfce7; color: #16a34a; }
                    .lecture { background: #f3e8ff; color: #9333ea; }
                    .other { background: #f3f4f6; color: #6b7280; }
                    .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #6b7280; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1 style="margin: 0; font-size: 24px;">Task Reminder</h1>
                        <p style="margin: 5px 0 0 0; opacity: 0.9;">Hi ' . htmlspecialchars($reminder['name']) . ', this is a friendly reminder about your upcoming task.</p>
                    </div>
                    
                    <div class="content">
                        <div class="task-info' . ($due_time < $now ? ' urgent' : '') . '">
                            <h2 style="margin: 0 0 10px 0; color: #1f2937;">' . htmlspecialchars($reminder['title']) . '</h2>
                            
                            <span class="category ' . strtolower($reminder['category']) . '">' . htmlspecialchars($reminder['category']) . '</span>
                            
                            ' . ($reminder['description'] ? '<p style="margin: 15px 0; color: #4b5563;">' . htmlspecialchars($reminder['description']) . '</p>' : '') . '
                            
                            <div style="margin: 15px 0;">
                                <strong>Due:</strong> ' . date('l, F j, Y \a\t g:i A', strtotime($reminder['due_at'])) . '<br>
                                <strong>Time remaining:</strong> <span style="color: ' . ($due_time < $now ? '#dc2626' : '#059669') . '; font-weight: bold;">' . $time_until_due . '</span><br>
                                ' . ($reminder['estimated_minutes'] ? '<strong>Estimated time:</strong> ' . $reminder['estimated_minutes'] . ' minutes<br>' : '') . '
                            </div>
                        </div>
                        
                        <p style="margin: 20px 0; color: #4b5563;">
                            Please take some time to work on this task. Remember, consistent progress leads to better results!
                        </p>
                    </div>
                    
                    <div class="footer">
                        <p>This reminder was sent automatically by Student Time Advisor.</p>
                        <p>You can manage your tasks and reminders at any time.</p>
                    </div>
                </div>
            </body>
            </html>';
            
            // Send email
            if (send_email($reminder['email'], $subject, $body)) {
                // Mark reminder as sent
                $update_stmt = $pdo->prepare("UPDATE reminders SET sent = 1 WHERE id = ?");
                $update_stmt->execute([$reminder['reminder_id']]);
                
                cron_log("Sent reminder for task '{$reminder['title']}' to {$reminder['email']}");
                $sent_count++;
            } else {
                cron_log("Failed to send reminder for task '{$reminder['title']}' to {$reminder['email']}", 'ERROR');
                $error_count++;
            }
            
        } catch (Exception $e) {
            cron_log("Error processing reminder ID {$reminder['reminder_id']}: " . $e->getMessage(), 'ERROR');
            $error_count++;
        }
    }
    
    cron_log("Reminder process completed. Sent: $sent_count, Errors: $error_count");
    
    // Clean up old sent reminders (older than 30 days)
    $cleanup_stmt = $pdo->prepare("DELETE FROM reminders WHERE sent = 1 AND send_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $cleanup_stmt->execute();
    $deleted_count = $cleanup_stmt->rowCount();
    
    if ($deleted_count > 0) {
        cron_log("Cleaned up $deleted_count old sent reminders");
    }
    
} catch (Exception $e) {
    cron_log("Critical error in reminder process: " . $e->getMessage(), 'ERROR');
    exit(1);
}