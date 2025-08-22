<?php
/**
 * Email System using PHPMailer
 * Handles all email functionality for the Student Time Management Advisor
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Check if PHPMailer is available
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    // Try to include PHPMailer if it's installed via Composer
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    } else {
        // Fallback: try to include PHPMailer manually
        $phpmailer_paths = [
            __DIR__ . '/../PHPMailer/src/PHPMailer.php',
            __DIR__ . '/../PHPMailer/PHPMailer.php',
            __DIR__ . '/../phpmailer/src/PHPMailer.php'
        ];
        
        $phpmailer_loaded = false;
        foreach ($phpmailer_paths as $path) {
            if (file_exists($path)) {
                require_once $path;
                require_once dirname($path) . '/SMTP.php';
                require_once dirname($path) . '/Exception.php';
                $phpmailer_loaded = true;
                break;
            }
        }
        
        if (!$phpmailer_loaded) {
            error_log('PHPMailer not found. Please install it via Composer or download manually.');
            return;
        }
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailSystem {
    private $mailer;
    private $pdo;
    private $config;
    
    public function __construct() {
        $this->pdo = DB::conn();
        $this->config = $this->getEmailConfig();
        $this->initializeMailer();
    }
    
    /**
     * Get email configuration from environment variables or database
     */
    private function getEmailConfig() {
        // Try to get from database first (for dynamic configuration)
        try {
            $stmt = $this->pdo->query("SELECT * FROM email_config LIMIT 1");
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($config) {
                return $config;
            }
        } catch (Exception $e) {
            // Table doesn't exist, use environment variables
        }
        
        // Use environment variables with fallbacks
        return [
            'smtp_host' => MAIL_HOST,
            'smtp_port' => MAIL_PORT,
            'smtp_username' => MAIL_USER,
            'smtp_password' => MAIL_PASS,
            'smtp_encryption' => MAIL_ENCRYPTION,
            'from_email' => MAIL_FROM,
            'from_name' => MAIL_FROM_NAME,
            'reply_to' => MAIL_REPLY_TO
        ];
    }
    
    /**
     * Initialize PHPMailer with configuration
     */
    private function initializeMailer() {
        try {
            $this->mailer = new PHPMailer(true);
            
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['smtp_host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->config['smtp_username'];
            $this->mailer->Password = $this->config['smtp_password'];
            $this->mailer->SMTPSecure = $this->config['smtp_encryption'];
            $this->mailer->Port = $this->config['smtp_port'];
            
            // Default settings
            $this->mailer->setFrom($this->config['from_email'], $this->config['from_name']);
            $this->mailer->addReplyTo($this->config['reply_to'], 'Student Time Advisor Support');
            
            // Debug settings (disable in production)
            $this->mailer->SMTPDebug = 0;
            $this->mailer->Debugoutput = 'error_log';
            
        } catch (Exception $e) {
            error_log('Failed to initialize PHPMailer: ' . $e->getMessage());
        }
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($user_id, $reset_link) {
        try {
            $user = $this->getUserById($user_id);
            if (!$user) {
                throw new Exception('User not found');
            }
            
            $subject = 'Password Reset Request - Student Time Advisor';
            $body = $this->getPasswordResetEmailTemplate($user['name'], $reset_link);
            
            return $this->sendEmail($user['email'], $subject, $body);
        } catch (Exception $e) {
            error_log('Failed to send password reset email: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send welcome email to new users
     */
    public function sendWelcomeEmail($user_id) {
        try {
            $user = $this->getUserById($user_id);
            if (!$user) return false;
            
            $subject = 'Welcome to Student Time Advisor! 🎓';
            
            $html_body = $this->getWelcomeEmailTemplate($user);
            $text_body = $this->getWelcomeEmailTextTemplate($user);
            
            return $this->sendEmail($user['email'], $subject, $html_body, $text_body);
            
        } catch (Exception $e) {
            error_log('Failed to send welcome email: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send task reminder email
     */
    public function sendTaskReminder($user_id, $task_id) {
        try {
            $user = $this->getUserById($user_id);
            $task = $this->getTaskById($task_id);
            
            if (!$user || !$task) return false;
            
            $subject = "Reminder: '{$task['title']}' is due soon! ⏰";
            
            $html_body = $this->getTaskReminderTemplate($user, $task);
            $text_body = $this->getTaskReminderTextTemplate($user, $task);
            
            return $this->sendEmail($user['email'], $subject, $html_body, $text_body);
            
        } catch (Exception $e) {
            error_log('Failed to send task reminder: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordReset($user_id, $reset_token) {
        try {
            $user = $this->getUserById($user_id);
            if (!$user) return false;
            
            $subject = 'Password Reset Request - Student Time Advisor 🔐';
            
            $reset_url = APP_URL . '/reset_password.php?token=' . $reset_token;
            
            $html_body = $this->getPasswordResetTemplate($user, $reset_url);
            $text_body = $this->getPasswordResetTextTemplate($user, $reset_url);
            
            return $this->sendEmail($user['email'], $subject, $html_body, $text_body);
            
        } catch (Exception $e) {
            error_log('Failed to send password reset email: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send weekly progress report
     */
    public function sendWeeklyReport($user_id) {
        try {
            $user = $this->getUserById($user_id);
            if (!$user) return false;
            
            $stats = $this->getWeeklyStats($user_id);
            
            $subject = "Your Weekly Progress Report - {$stats['week_range']} 📊";
            
            $html_body = $this->getWeeklyReportTemplate($user, $stats);
            $text_body = $this->getWeeklyReportTextTemplate($user, $stats);
            
            return $this->sendEmail($user['email'], $subject, $html_body, $text_body);
            
        } catch (Exception $e) {
            error_log('Failed to send weekly report: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send achievement notification
     */
    public function sendAchievementNotification($user_id, $badge_code) {
        try {
            $user = $this->getUserById($user_id);
            $badge = $this->getBadgeByCode($badge_code);
            
            if (!$user || !$badge) return false;
            
            $subject = "🎉 Congratulations! You've earned the '{$badge['label']}' badge!";
            
            $html_body = $this->getAchievementTemplate($user, $badge);
            $text_body = $this->getAchievementTextTemplate($user, $badge);
            
            return $this->sendEmail($user['email'], $subject, $html_body, $text_body);
            
        } catch (Exception $e) {
            error_log('Failed to send achievement notification: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send daily motivation email
     */
    public function sendDailyMotivation($user_id) {
        try {
            $user = $this->getUserById($user_id);
            if (!$user) return false;
            
            $motivation = $this->getDailyMotivation();
            
            $subject = "Your Daily Motivation - " . date('l, F j') . " 🌟";
            
            $html_body = $this->getDailyMotivationTemplate($user, $motivation);
            $text_body = $this->getDailyMotivationTextTemplate($user, $motivation);
            
            return $this->sendEmail($user['email'], $subject, $html_body, $text_body);
            
        } catch (Exception $e) {
            error_log('Failed to send daily motivation: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Core email sending function
     */
    private function sendEmail($to_email, $subject, $html_body, $text_body = '') {
        try {
            if (!$this->mailer) {
                throw new Exception('PHPMailer not initialized');
            }
            
            // Clear previous recipients
            $this->mailer->clearAddresses();
            
            // Add recipient
            $this->mailer->addAddress($to_email);
            
            // Set content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $html_body;
            $this->mailer->AltBody = $text_body;
            
            // Send email
            $result = $this->mailer->send();
            
            // Log successful email
            $this->logEmail($to_email, $subject, 'sent');
            
            return $result;
            
        } catch (Exception $e) {
            error_log('Email sending failed: ' . $e->getMessage());
            
            // Log failed email
            $this->logEmail($to_email, $subject, 'failed', $e->getMessage());
            
            return false;
        }
    }
    
    /**
     * Get user by ID with student information
     */
    private function getUserById($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT 
                u.*,
                COALESCE(u.student_id, CONCAT('STU', LPAD(u.id, 6, '0'))) as display_student_id,
                COALESCE(u.program, 'Undecided') as display_program,
                COALESCE(u.major, 'General Studies') as display_major,
                COALESCE(u.academic_year, '1st Year') as display_academic_year,
                COALESCE(u.institution, 'University') as display_institution,
                COALESCE(u.advisor_name, 'Academic Advisor') as display_advisor_name
            FROM users u 
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get task by ID
     */
    private function getTaskById($task_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->execute([$task_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get badge by code
     */
    private function getBadgeByCode($badge_code) {
        $stmt = $this->pdo->prepare("SELECT * FROM badges WHERE code = ?");
        $stmt->execute([$badge_code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get weekly statistics
     */
    private function getWeeklyStats($user_id) {
        $week_start = date('Y-m-d', strtotime('monday this week'));
        $week_end = date('Y-m-d', strtotime('sunday this week'));
        
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_tasks,
                COUNT(CASE WHEN completed = 1 THEN 1 END) as completed_tasks,
                SUM(CASE WHEN completed = 1 THEN actual_minutes ELSE 0 END) as study_minutes
            FROM tasks 
            WHERE user_id = ? AND DATE(created_at) BETWEEN ? AND ?
        ");
        $stmt->execute([$user_id, $week_start, $week_end]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stats['week_range'] = date('M j', strtotime($week_start)) . ' - ' . date('M j', strtotime($week_end));
        $stats['completion_rate'] = $stats['total_tasks'] > 0 ? round(($stats['completed_tasks'] / $stats['total_tasks']) * 100, 1) : 0;
        
        return $stats;
    }
    
    /**
     * Get daily motivation
     */
    private function getDailyMotivation() {
        $motivations = [
            "The only way to do great work is to love what you do. - Steve Jobs",
            "Success is not final, failure is not fatal: it is the courage to continue that counts. - Winston Churchill",
            "Education is the most powerful weapon which you can use to change the world. - Nelson Mandela",
            "The future belongs to those who believe in the beauty of their dreams. - Eleanor Roosevelt",
            "Don't watch the clock; do what it does. Keep going. - Sam Levenson"
        ];
        
        $index = (int)date('z') % count($motivations);
        return $motivations[$index];
    }
    
    /**
     * Get program-specific study tips
     */
    private function getProgramSpecificTip($program) {
        $program_tips = [
            'Computer Science' => 'Practice coding daily, even if just for 30 minutes. Build small projects to reinforce concepts.',
            'Software Engineering' => 'Focus on both technical skills and soft skills. Communication is key in team projects.',
            'Engineering' => 'Apply theoretical concepts to real-world problems. Build prototypes and test your ideas.',
            'Business' => 'Network with professionals in your field. Join business clubs and attend industry events.',
            'Medicine' => 'Stay organized with your study materials. Use mnemonics and practice with case studies.',
            'Law' => 'Read extensively and practice legal writing. Join moot court or debate teams.',
            'Arts' => 'Create a portfolio of your best work. Collaborate with other artists to expand your skills.',
            'Psychology' => 'Practice active listening and observation. Volunteer for research studies when possible.',
            'Education' => 'Observe experienced teachers and practice lesson planning. Get involved in tutoring programs.',
            'Science' => 'Conduct experiments and document everything. Join research groups or science clubs.'
        ];
        
        // Return program-specific tip or default tip
        return $program_tips[$program] ?? 'Stay curious and ask questions. The best learners are always seeking to understand more.';
    }
    
    /**
     * Log email activity
     */
    private function logEmail($to_email, $subject, $status, $error_message = '') {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO email_logs (to_email, subject, status, error_message, sent_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$to_email, $subject, $status, $error_message]);
        } catch (Exception $e) {
            error_log('Failed to log email: ' . $e->getMessage());
        }
    }
    
    /**
     * Email Templates
     */
    
    private function getPasswordResetEmailTemplate($name, $reset_link) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Password Reset Request</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); padding: 30px; text-align: center; border-radius: 10px; margin-bottom: 30px;'>
                <h1 style='color: white; margin: 0; font-size: 28px;'>🔐 Password Reset Request</h1>
                <p style='color: white; margin: 10px 0 0 0; font-size: 16px;'>Secure your account with a new password</p>
            </div>
            
            <div style='background: #f8f9fa; padding: 25px; border-radius: 10px; margin-bottom: 25px;'>
                <h2 style='color: #2c3e50; margin-top: 0;'>Hello {$name}! 👋</h2>
                
                <p style='font-size: 16px; margin-bottom: 20px;'>
                    We received a request to reset your password for your Student Time Advisor account.
                </p>
                
                <div style='background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #ef4444; margin: 20px 0;'>
                    <h3 style='color: #2c3e50; margin-top: 0;'>⚠️ Important Security Notice:</h3>
                    <ul style='text-align: left; padding-left: 20px;'>
                        <li>This link will expire in 1 hour for security reasons</li>
                        <li>This link can only be used once</li>
                        <li>If you didn't request this reset, ignore this email</li>
                    </ul>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$reset_link}' style='background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block;'>Reset Password</a>
                </div>
                
                <p style='font-size: 14px; color: #666; text-align: center;'>
                    If the button doesn't work, copy and paste this link into your browser:<br>
                    <span style='word-break: break-all; color: #3b82f6;'>{$reset_link}</span>
                </p>
            </div>
            
            <div style='background: #e8f4fd; padding: 20px; border-radius: 8px; border: 1px solid #bee5eb;'>
                <h3 style='color: #0c5460; margin-top: 0;'>🔒 Security Tips:</h3>
                <p style='margin-bottom: 10px;'><strong>Strong Password:</strong> Use a combination of letters, numbers, and symbols.</p>
                <p style='margin-bottom: 10px;'><strong>Unique Password:</strong> Don't reuse passwords from other accounts.</p>
                <p style='margin-bottom: 0;'><strong>Regular Updates:</strong> Change your password periodically for better security.</p>
            </div>
            
            <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 14px;'>
                <p>If you have any questions or concerns, please contact our support team.</p>
                <p>Stay secure! 🔐✨</p>
            </div>
        </body>
        </html>";
    }
    
    private function getWelcomeEmailTemplate($user) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Welcome to Student Time Advisor</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px; margin-bottom: 30px;'>
                <h1 style='color: white; margin: 0; font-size: 28px;'>🎓 Welcome to Student Time Advisor!</h1>
                <p style='color: white; margin: 10px 0 0 0; font-size: 16px;'>Your journey to academic success starts now!</p>
            </div>
            
            <div style='background: #f8f9fa; padding: 25px; border-radius: 10px; margin-bottom: 25px;'>
                <h2 style='color: #2c3e50; margin-top: 0;'>Hello {$user['name']}! 👋</h2>
                
                <!-- Student Information Card -->
                <div style='background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea; margin: 20px 0;'>
                    <h3 style='color: #2c3e50; margin-top: 0; font-size: 18px;'>🎓 Student Profile</h3>
                    <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;'>
                        <div>
                            <p style='margin: 5px 0; font-size: 14px;'><strong style='color: #667eea;'>Student ID:</strong> {$user['display_student_id']}</p>
                            <p style='margin: 5px 0; font-size: 14px;'><strong style='color: #667eea;'>Program:</strong> {$user['display_program']}</p>
                            <p style='margin: 5px 0; font-size: 14px;'><strong style='color: #667eea;'>Major:</strong> {$user['display_major']}</p>
                        </div>
                        <div>
                            <p style='margin: 5px 0; font-size: 14px;'><strong style='color: #667eea;'>Year:</strong> {$user['display_academic_year']}</p>
                            <p style='margin: 5px 0; font-size: 14px;'><strong style='color: #667eea;'>Institution:</strong> {$user['display_institution']}</p>
                            <p style='margin: 5px 0; font-size: 14px;'><strong style='color: #667eea;'>Advisor:</strong> {$user['display_advisor_name']}</p>
                        </div>
                    </div>
                </div>
                
                <p style='font-size: 16px; margin-bottom: 20px;'>
                    Welcome to Student Time Advisor! We're excited to help you organize your academic life and achieve your goals in your {$user['display_program']} program.
                </p>
                
                <div style='background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #3498db; margin: 20px 0;'>
                    <h3 style='color: #2c3e50; margin-top: 0;'>🚀 What you can do now:</h3>
                    <ul style='text-align: left; padding-left: 20px;'>
                        <li>Create your first task</li>
                        <li>Set study goals and deadlines</li>
                        <li>Track your progress and streaks</li>
                        <li>Earn badges for achievements</li>
                        <li>Get daily motivation and tips</li>
                    </ul>
                </div>
            </div>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='" . APP_URL . "/dashboard.php' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block;'>Get Started Now</a>
            </div>
            
            <div style='background: #e8f4fd; padding: 20px; border-radius: 8px; border: 1px solid #bee5eb;'>
                <h3 style='color: #0c5460; margin-top: 0;'>💡 Quick Tips:</h3>
                <p style='margin-bottom: 10px;'><strong>Start Small:</strong> Begin with just 2-3 tasks to build momentum.</p>
                <p style='margin-bottom: 10px;'><strong>Set Realistic Deadlines:</strong> Give yourself enough time to complete tasks well.</p>
                <p style='margin-bottom: 0;'><strong>Track Your Progress:</strong> Celebrate small wins to stay motivated!</p>
            </div>
            
            <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 14px;'>
                <p>If you have any questions, feel free to reach out to our support team.</p>
                <p>Happy studying! 📚✨</p>
            </div>
        </body>
        </html>";
    }
    
    private function getWelcomeEmailTextTemplate($user) {
        return "
Welcome to Student Time Advisor!

Hello {$user['name']}!

Student Profile:
- Student ID: {$user['display_student_id']}
- Program: {$user['display_program']}
- Major: {$user['display_major']}
- Academic Year: {$user['display_academic_year']}
- Institution: {$user['display_institution']}
- Academic Advisor: {$user['display_advisor_name']}

Welcome to Student Time Advisor! We're excited to help you organize your academic life and achieve your goals in your {$user['display_program']} program.

What you can do now:
- Create your first task
- Set study goals and deadlines  
- Track your progress and streaks
- Earn badges for achievements
- Get daily motivation and tips

Quick Tips:
- Start Small: Begin with just 2-3 tasks to build momentum
- Set Realistic Deadlines: Give yourself enough time to complete tasks well
- Track Your Progress: Celebrate small wins to stay motivated!

Get started at: " . APP_URL . "/dashboard.php

Happy studying!

Student Time Advisor Team";
    }
    
    private function getTaskReminderTemplate($user, $task) {
        $due_date = date('l, F j, Y', strtotime($task['due_at']));
        $due_time = date('g:i A', strtotime($task['due_at']));
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Task Reminder</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); padding: 30px; text-align: center; border-radius: 10px; margin-bottom: 30px;'>
                <h1 style='color: white; margin: 0; font-size: 28px;'>⏰ Task Reminder</h1>
                <p style='color: white; margin: 10px 0 0 0; font-size: 16px;'>Don't forget about this important task!</p>
            </div>
            
            <div style='background: #fff5f5; padding: 25px; border-radius: 10px; margin-bottom: 25px; border: 1px solid #fed7d7;'>
                <h2 style='color: #c53030; margin-top: 0;'>Task: {$task['title']}</h2>
                
                <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <p style='margin: 10px 0;'><strong>Category:</strong> {$task['category']}</p>
                    <p style='margin: 10px 0;'><strong>Due Date:</strong> {$due_date}</p>
                    <p style='margin: 10px 0;'><strong>Due Time:</strong> {$due_time}</p>
                    " . ($task['estimated_minutes'] ? "<p style='margin: 10px 0;'><strong>Estimated Time:</strong> {$task['estimated_minutes']} minutes</p>" : "") . "
                    " . ($task['description'] ? "<p style='margin: 10px 0;'><strong>Description:</strong> {$task['description']}</p>" : "") . "
                </div>
                
                <div style='background: #e6fffa; padding: 15px; border-radius: 8px; border-left: 4px solid #38b2ac;'>
                    <p style='margin: 0; color: #234e52;'><strong>💡 Tip:</strong> Break this task into smaller chunks if it feels overwhelming!</p>
                </div>
            </div>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='" . APP_URL . "/tasks.php' style='background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block;'>View Task Details</a>
            </div>
            
            <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 14px;'>
                <p>You're doing great! Keep up the excellent work! 💪</p>
            </div>
        </body>
        </html>";
    }
    
    private function getTaskReminderTextTemplate($user, $task) {
        $due_date = date('l, F j, Y', strtotime($task['due_at']));
        $due_time = date('g:i A', strtotime($task['due_at']));
        
        return "
Task Reminder

Hello {$user['name']},

This is a reminder about your upcoming task:

Task: {$task['title']}
Category: {$task['category']}
Due Date: {$due_date}
Due Time: {$due_time}" . 
($task['estimated_minutes'] ? "\nEstimated Time: {$task['estimated_minutes']} minutes" : "") . 
($task['description'] ? "\nDescription: {$task['description']}" : "") . "

Tip: Break this task into smaller chunks if it feels overwhelming!

View task details at: " . APP_URL . "/tasks.php

You're doing great! Keep up the excellent work!

Student Time Advisor";
    }
    
    // Additional template methods would go here...
    private function getPasswordResetTemplate($user, $reset_url) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Password Reset</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px; margin-bottom: 30px;'>
                <h1 style='color: white; margin: 0; font-size: 28px;'>🔐 Password Reset</h1>
                <p style='color: white; margin: 10px 0 0 0; font-size: 16px;'>Reset your password securely</p>
            </div>
            
            <div style='background: #f8f9fa; padding: 25px; border-radius: 10px; margin-bottom: 25px;'>
                <h2 style='color: #2c3e50; margin-top: 0;'>Hello {$user['name']}!</h2>
                <p style='font-size: 16px; margin-bottom: 20px;'>
                    We received a request to reset your password. Click the button below to create a new password:
                </p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$reset_url}' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block;'>Reset Password</a>
                </div>
                
                <p style='font-size: 14px; color: #666;'>
                    If you didn't request this password reset, you can safely ignore this email. Your password will remain unchanged.
                </p>
            </div>
            
            <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 14px;'>
                <p>This link will expire in 1 hour for security reasons.</p>
            </div>
        </body>
        </html>";
    }
    
    private function getPasswordResetTextTemplate($user, $reset_url) {
        return "
Password Reset

Hello {$user['name']}!

We received a request to reset your password. Click the link below to create a new password:

{$reset_url}

If you didn't request this password reset, you can safely ignore this email. Your password will remain unchanged.

This link will expire in 1 hour for security reasons.

Student Time Advisor Team";
    }
    
    private function getWeeklyReportTemplate($user, $stats) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Weekly Progress Report</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: linear-gradient(135deg, #10ac84 0%, #1dd1a1 100%); padding: 30px; text-align: center; border-radius: 10px; margin-bottom: 30px;'>
                <h1 style='color: white; margin: 0; font-size: 28px;'>📊 Weekly Progress Report</h1>
                <p style='color: white; margin: 10px 0 0 0; font-size: 16px;'>{$stats['week_range']}</p>
            </div>
            
            <div style='background: #f8f9fa; padding: 25px; border-radius: 10px; margin-bottom: 25px;'>
                <h2 style='color: #2c3e50; margin-top: 0;'>Hello {$user['name']}! 👋</h2>
                <p style='font-size: 16px; margin-bottom: 20px;'>
                    Here's your progress summary for this week:
                </p>
                
                <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 20px 0;'>
                    <div style='background: white; padding: 20px; border-radius: 8px; text-align: center; border: 1px solid #e9ecef;'>
                        <div style='font-size: 24px; font-weight: bold; color: #28a745;'>{$stats['total_tasks']}</div>
                        <div style='color: #6c757d; font-size: 14px;'>Total Tasks</div>
                    </div>
                    <div style='background: white; padding: 20px; border-radius: 8px; text-align: center; border: 1px solid #e9ecef;'>
                        <div style='font-size: 24px; font-weight: bold; color: #007bff;'>{$stats['completed_tasks']}</div>
                        <div style='color: #6c757d; font-size: 14px;'>Completed</div>
                    </div>
                </div>
                
                <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #e9ecef;'>
                    <h3 style='color: #2c3e50; margin-top: 0;'>📈 Completion Rate</h3>
                    <div style='background: #e9ecef; border-radius: 10px; height: 20px; overflow: hidden; margin: 10px 0;'>
                        <div style='background: linear-gradient(135deg, #10ac84 0%, #1dd1a1 100%); height: 100%; width: {$stats['completion_rate']}%; border-radius: 10px;'></div>
                    </div>
                    <p style='text-align: center; font-weight: bold; color: #2c3e50;'>{$stats['completion_rate']}%</p>
                </div>
                
                " . ($stats['study_minutes'] > 0 ? "
                <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #e9ecef;'>
                    <h3 style='color: #2c3e50; margin-top: 0;'>⏱️ Study Time</h3>
                    <p style='font-size: 18px; text-align: center; color: #2c3e50;'><strong>" . round($stats['study_minutes'] / 60, 1) . " hours</strong></p>
                    <p style='text-align: center; color: #6c757d;'>Total study time this week</p>
                </div>" : "") . "
            </div>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='" . APP_URL . "/reports.php' style='background: linear-gradient(135deg, #10ac84 0%, #1dd1a1 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block;'>View Detailed Report</a>
            </div>
            
            <div style='background: #e8f4fd; padding: 20px; border-radius: 8px; border: 1px solid #bee5eb;'>
                <h3 style='color: #0c5460; margin-top: 0;'>💪 Keep Going!</h3>
                <p style='margin-bottom: 10px;'>You're making great progress! Remember:</p>
                <ul style='margin: 0; padding-left: 20px;'>
                    <li>Consistency is key to success</li>
                    <li>Every completed task brings you closer to your goals</li>
                    <li>Take breaks when needed to maintain focus</li>
                </ul>
            </div>
            
            <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 14px;'>
                <p>Have a productive week ahead! 🌟</p>
            </div>
        </body>
        </html>";
    }
    
    private function getWeeklyReportTextTemplate($user, $stats) {
        return "
Weekly Progress Report - {$stats['week_range']}

Hello {$user['name']}!

Here's your progress summary for this week:

Total Tasks: {$stats['total_tasks']}
Completed: {$stats['completed_tasks']}
Completion Rate: {$stats['completion_rate']}%" . 
($stats['study_minutes'] > 0 ? "\nStudy Time: " . round($stats['study_minutes'] / 60, 1) . " hours" : "") . "

Keep Going!
- Consistency is key to success
- Every completed task brings you closer to your goals  
- Take breaks when needed to maintain focus

View detailed report at: " . APP_URL . "/reports.php

Have a productive week ahead!

Student Time Advisor Team";
    }
    
    private function getAchievementTemplate($user, $badge) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Achievement Unlocked!</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); padding: 30px; text-align: center; border-radius: 10px; margin-bottom: 30px;'>
                <h1 style='color: white; margin: 0; font-size: 28px;'>🎉 Achievement Unlocked!</h1>
                <p style='color: white; margin: 10px 0 0 0; font-size: 16px;'>Congratulations on your success!</p>
            </div>
            
            <div style='background: #fff8e1; padding: 25px; border-radius: 10px; margin-bottom: 25px; border: 1px solid #ffecb3;'>
                <h2 style='color: #f57c00; margin-top: 0;'>🌟 {$badge['label']}</h2>
                
                <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #e9ecef;'>
                    <p style='margin: 10px 0; font-size: 16px;'><strong>Description:</strong> {$badge['description']}</p>
                    <p style='margin: 10px 0; font-size: 16px;'><strong>Points Earned:</strong> {$badge['points']} points</p>
                </div>
                
                <div style='background: #e8f5e8; padding: 15px; border-radius: 8px; border-left: 4px solid #4caf50;'>
                    <p style='margin: 0; color: #2e7d32;'><strong>🎯 What this means:</strong> You're making excellent progress in your academic journey!</p>
                </div>
            </div>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='" . APP_URL . "/motivation.php' style='background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block;'>View All Badges</a>
            </div>
            
            <div style='background: #e8f4fd; padding: 20px; border-radius: 8px; border: 1px solid #bee5eb;'>
                <h3 style='color: #0c5460; margin-top: 0;'>💪 Keep Up the Great Work!</h3>
                <p style='margin-bottom: 10px;'>Achievements like this show that you're:</p>
                <ul style='margin: 0; padding-left: 20px;'>
                    <li>Staying committed to your goals</li>
                    <li>Building positive study habits</li>
                    <li>Making steady progress</li>
                </ul>
            </div>
            
            <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 14px;'>
                <p>You're on fire! 🔥 Keep pushing forward!</p>
            </div>
        </body>
        </html>";
    }
    
    private function getAchievementTextTemplate($user, $badge) {
        return "
Achievement Unlocked!

Congratulations {$user['name']}!

You've earned the '{$badge['label']}' badge!

Description: {$badge['description']}
Points Earned: {$badge['points']} points

What this means: You're making excellent progress in your academic journey!

Keep Up the Great Work!
Achievements like this show that you're:
- Staying committed to your goals
- Building positive study habits
- Making steady progress

View all badges at: " . APP_URL . "/motivation.php

You're on fire! Keep pushing forward!

Student Time Advisor Team";
    }
    
    private function getDailyMotivationTemplate($user, $motivation) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Daily Motivation</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px; margin-bottom: 30px;'>
                <h1 style='color: white; margin: 0; font-size: 28px;'>🌟 Daily Motivation</h1>
                <p style='color: white; margin: 10px 0 0 0; font-size: 16px;'>" . date('l, F j, Y') . "</p>
            </div>
            
            <div style='background: #f8f9fa; padding: 25px; border-radius: 10px; margin-bottom: 25px;'>
                <h2 style='color: #2c3e50; margin-top: 0;'>Hello {$user['name']}! 👋</h2>
                <p style='font-size: 16px; margin-bottom: 20px;'>
                    Here's your daily dose of motivation to kickstart your day:
                </p>
                
                <div style='background: white; padding: 25px; border-radius: 8px; margin: 20px 0; border: 1px solid #e9ecef;'>
                    <blockquote style='font-style: italic; font-size: 18px; color: #2c3e50; margin: 0; text-align: center;'>
                        \"{$motivation}\"
                    </blockquote>
                </div>
                
                <div style='background: #e8f4fd; padding: 20px; border-radius: 8px; border: 1px solid #bee5eb;'>
                    <h3 style='color: #0c5460; margin-top: 0;'>💡 Today's Action Step</h3>
                    <p style='margin: 0; color: #234e52;'>Take one small step toward your biggest goal today. Every journey begins with a single step!</p>
                </div>
            </div>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='" . APP_URL . "/dashboard.php' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block;'>Start Your Day</a>
            </div>
            
            <div style='background: #fff8e1; padding: 20px; border-radius: 8px; border: 1px solid #ffecb3;'>
                <h3 style='color: #f57c00; margin-top: 0;'>🚀 Quick Tips for Today</h3>
                <ul style='margin: 0; padding-left: 20px; color: #e65100;'>
                    <li>Review your task list for today</li>
                    <li>Set your top 3 priorities</li>
                    <li>Take short breaks every 25 minutes</li>
                    <li>Celebrate small wins throughout the day</li>
                </ul>
                
                <!-- Program-Specific Tip -->
                <div style='background: #fff3cd; padding: 15px; border-radius: 6px; margin-top: 15px; border-left: 4px solid #ffc107;'>
                    <h4 style='color: #856404; margin-top: 0; font-size: 16px;'>💡 {$user['display_program']} Tip</h4>
                    <p style='color: #856404; margin: 0; font-size: 14px;'>
                        " . $this->getProgramSpecificTip($user['display_program']) . "
                    </p>
                </div>
            </div>
            
            <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 14px;'>
                <p>You've got this! Make today amazing! ✨</p>
            </div>
        </body>
        </html>";
    }
    
    private function getDailyMotivationTextTemplate($user, $motivation) {
        return "
Daily Motivation - " . date('l, F j, Y') . "

Hello {$user['name']}!

Here's your daily dose of motivation to kickstart your day:

\"{$motivation}\"

Today's Action Step:
Take one small step toward your biggest goal today. Every journey begins with a single step!

Quick Tips for Today:
- Review your task list for today
- Set your top 3 priorities  
- Take short breaks every 25 minutes
- Celebrate small wins throughout the day

{$user['display_program']} Tip:
" . $this->getProgramSpecificTip($user['display_program']) . "

Start your day at: " . APP_URL . "/dashboard.php

You've got this! Make today amazing!

Student Time Advisor Team";
    }
}

// Initialize email system
$emailSystem = new EmailSystem();
?>