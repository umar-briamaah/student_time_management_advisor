<?php
/**
 * Cron job to send reminder emails
 * Run every 15 minutes: 0,15,30,45 * * * * php /path/to/cron/send_reminders.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/mailer.php';

// Set timezone from environment
date_default_timezone_set(TIMEZONE);

// Log start of reminder process
log_message('info', 'Starting reminder email process', [
    'cron_job' => 'send_reminders',
    'time' => date('Y-m-d H:i:s')
]);

try {
    $pdo = DB::conn();
    
    // Get tasks due within the reminder interval
    $reminder_interval = CRON_REMINDER_INTERVAL;
    $current_time = date('Y-m-d H:i:s');
    $reminder_time = date('Y-m-d H:i:s', strtotime("+{$reminder_interval} minutes"));
    
    $stmt = $pdo->prepare("
        SELECT t.*, u.email, u.first_name, u.last_name 
        FROM tasks t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.due_date BETWEEN ? AND ? 
        AND t.status != 'completed' 
        AND t.reminder_sent = 0
        AND t.user_id IN (SELECT id FROM users WHERE email_notifications = 1)
    ");
    
    $stmt->execute([$current_time, $reminder_time]);
    $tasks = $stmt->fetchAll();
    
    log_message('info', 'Found tasks for reminders', [
        'count' => count($tasks),
        'reminder_interval' => $reminder_interval,
        'current_time' => $current_time,
        'reminder_time' => $reminder_time
    ]);
    
    $email_system = new EmailSystem();
    $sent_count = 0;
    $error_count = 0;
    
    foreach ($tasks as $task) {
        try {
            $email_sent = $email_system->sendTaskReminder($task['user_id'], $task['id']);
            
            if ($email_sent) {
                // Mark reminder as sent
                $update_stmt = $pdo->prepare("UPDATE tasks SET reminder_sent = 1 WHERE id = ?");
                $update_stmt->execute([$task['id']]);
                $sent_count++;
                
                log_message('info', 'Reminder email sent successfully', [
                    'task_id' => $task['id'],
                    'user_id' => $task['user_id'],
                    'user_email' => $task['email']
                ]);
            } else {
                $error_count++;
                log_message('error', 'Failed to send reminder email', [
                    'task_id' => $task['id'],
                    'user_id' => $task['user_id'],
                    'user_email' => $task['email']
                ]);
            }
            
        } catch (Exception $e) {
            $error_count++;
            log_message('error', 'Exception while sending reminder email', [
                'task_id' => $task['id'],
                'user_id' => $task['user_id'],
                'user_email' => $task['email'],
                'error' => $e->getMessage()
            ]);
        }
    }
    
    log_message('info', 'Reminder email process completed', [
        'total_tasks' => count($tasks),
        'emails_sent' => $sent_count,
        'errors' => $error_count
    ]);
    
} catch (Exception $e) {
    log_message('error', 'Critical error in reminder email process', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // Log the error for admin review
    log_message('critical', 'Reminder cron job failed - manual intervention required', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}