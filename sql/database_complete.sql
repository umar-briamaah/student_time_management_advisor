-- =====================================================
-- COMPLETE DATABASE SCHEMA FOR STUDENT TIME MANAGEMENT ADVISOR
-- =====================================================
-- This file contains the complete database structure and sample data
-- Use this single file for the entire project

-- Drop existing database if it exists and create new one
DROP DATABASE IF EXISTS student_time_advisor;
CREATE DATABASE student_time_advisor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE student_time_advisor;

-- =====================================================
-- TABLE STRUCTURES
-- =====================================================

-- Users table - Store student information
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  preferred_study_hour TINYINT DEFAULT 18, -- 6pm default
  timezone VARCHAR(50) DEFAULT 'UTC',
  last_login TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX idx_email (email),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Tasks table - Core task management
CREATE TABLE tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  category ENUM('Lecture','Lab','Exam','Assignment','Other') DEFAULT 'Other',
  priority ENUM('Low','Medium','High','Urgent') DEFAULT 'Medium',
  due_at DATETIME NOT NULL,
  estimated_minutes INT DEFAULT 60,
  actual_minutes INT NULL, -- Time actually spent
  completed TINYINT(1) DEFAULT 0,
  completed_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_due (user_id, due_at),
  INDEX idx_user_completed (user_id, completed),
  INDEX idx_user_category (user_id, category),
  INDEX idx_user_priority (user_id, priority),
  INDEX idx_due_at (due_at),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Reminders table - Task notifications
CREATE TABLE reminders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  user_id INT NOT NULL,
  send_at DATETIME NOT NULL,
  sent TINYINT(1) DEFAULT 0,
  reminder_type ENUM('email','push','sms') DEFAULT 'email',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_reminders_send_sent (send_at, sent),
  INDEX idx_reminders_user_sent (user_id, sent),
  INDEX idx_reminders_task (task_id)
) ENGINE=InnoDB;

-- Study sessions table - Track study time
CREATE TABLE study_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  task_id INT NULL, -- Can be NULL for general study
  start_time DATETIME NOT NULL,
  end_time DATETIME NULL,
  duration_minutes INT NULL,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL,
  INDEX idx_user_start (user_id, start_time),
  INDEX idx_task_sessions (task_id),
  INDEX idx_start_time (start_time)
) ENGINE=InnoDB;

-- Streaks table - Track completion streaks
CREATE TABLE streaks (
  user_id INT PRIMARY KEY,
  current_streak INT DEFAULT 0,
  longest_streak INT DEFAULT 0,
  last_active_date DATE DEFAULT NULL,
  total_completed_tasks INT DEFAULT 0,
  total_study_minutes INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Badges table - Achievement system
CREATE TABLE badges (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) UNIQUE NOT NULL,
  label VARCHAR(100) NOT NULL,
  description TEXT,
  icon VARCHAR(100) DEFAULT 'star',
  points INT DEFAULT 10,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  INDEX idx_code (code)
) ENGINE=InnoDB;

-- User badges table - Track earned badges
CREATE TABLE user_badges (
  user_id INT NOT NULL,
  badge_id INT NOT NULL,
  awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (user_id, badge_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE,
  INDEX idx_user_badges_user (user_id),
  INDEX idx_user_badges_awarded (awarded_at)
) ENGINE=InnoDB;

-- Password resets table - Store password reset tokens
CREATE TABLE password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_token (token),
  INDEX idx_user_expires (user_id, expires_at)
) ENGINE=InnoDB;

-- Categories table - Customizable task categories
CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  color VARCHAR(7) DEFAULT '#3B82F6', -- Hex color
  icon VARCHAR(50) DEFAULT '📋',
  is_default TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_user_category (user_id, name),
  INDEX idx_user_categories (user_id)
) ENGINE=InnoDB;

-- Study goals table - Academic objectives
CREATE TABLE study_goals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  target_hours_per_week DECIMAL(5,2) DEFAULT 0,
  current_hours_this_week DECIMAL(5,2) DEFAULT 0,
  deadline DATE NULL,
  completed TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_goals (user_id),
  INDEX idx_deadline (deadline)
) ENGINE=InnoDB;

-- Email logs table - Track all email activities
CREATE TABLE email_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  to_email VARCHAR(150) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  status ENUM('sent','failed','pending') DEFAULT 'pending',
  error_message TEXT,
  sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  INDEX idx_email_status (status),
  INDEX idx_sent_at (sent_at),
  INDEX idx_to_email (to_email)
) ENGINE=InnoDB;

-- Email configuration table - Store email settings
CREATE TABLE email_config (
  id INT AUTO_INCREMENT PRIMARY KEY,
  smtp_host VARCHAR(100) NOT NULL DEFAULT 'smtp.gmail.com',
  smtp_port INT NOT NULL DEFAULT 587,
  smtp_username VARCHAR(150) NOT NULL,
  smtp_password VARCHAR(255) NOT NULL,
  smtp_encryption ENUM('tls','ssl') DEFAULT 'tls',
  from_email VARCHAR(150) NOT NULL,
  from_name VARCHAR(100) NOT NULL,
  reply_to VARCHAR(150),
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Insert default badges
INSERT INTO badges (code, label, description, icon, points) VALUES
('FIRST_TASK', 'First Task Completed', 'Complete your very first task to get started', 'check-circle', 10),
('THREE_DAY_STREAK', '3-Day Streak', 'Maintain a 3-day consecutive completion streak', 'flame', 25),
('SEVEN_DAY_STREAK', '7-Day Streak', 'Maintain a 7-day consecutive completion streak', 'fire', 50),
('FOURTEEN_DAY_STREAK', '14-Day Streak', 'Maintain a 14-day consecutive completion streak', 'zap', 100),
('TWENTY_ONE_DAY_STREAK', '21-Day Streak', 'Maintain a 21-day consecutive completion streak', 'bolt', 200),
('THIRTY_DAY_STREAK', '30-Day Streak', 'Maintain a 30-day consecutive completion streak', 'crown', 500),
('ON_TIME_SUBMIT', 'On-Time Submission', 'Complete a task before its due date', 'clock', 15),
('DEEP_FOCUS_120', 'Deep Focus 120', 'Complete tasks totaling 120+ minutes in a single day', 'target', 30),
('WEEKLY_GOAL', 'Weekly Goal Achiever', 'Meet your weekly study goal', 'trophy', 40),
('PERFECT_WEEK', 'Perfect Week', 'Complete all tasks for an entire week', 'star', 100);

-- Insert default categories for new users
INSERT INTO categories (user_id, name, color, icon, is_default) VALUES
(0, 'Assignment', '#EF4444', '📚', 1),
(0, 'Exam', '#F59E0B', '📝', 1),
(0, 'Lab', '#10B981', '🔬', 1),
(0, 'Lecture', '#3B82F6', '🎓', 1),
(0, 'Project', '#8B5CF6', '🚀', 1),
(0, 'Reading', '#06B6D4', '📖', 1),
(0, 'Other', '#6B7280', '📋', 1);

-- =====================================================
-- STORED PROCEDURES
-- =====================================================

DELIMITER //

-- Procedure to create default categories for new users
CREATE PROCEDURE CreateDefaultCategories(IN user_id INT)
BEGIN
    INSERT INTO categories (user_id, name, color, icon, is_default)
    SELECT user_id, name, color, icon, is_default
    FROM categories 
    WHERE categories.user_id = 0;
END //

-- Procedure to calculate user statistics
CREATE PROCEDURE GetUserStats(IN user_id INT)
BEGIN
    SELECT 
        COUNT(*) as total_tasks,
        COUNT(CASE WHEN completed = 1 THEN 1 END) as completed_tasks,
        COUNT(CASE WHEN completed = 0 THEN 1 END) as pending_tasks,
        COUNT(CASE WHEN completed = 0 AND due_at < NOW() THEN 1 END) as overdue_tasks,
        ROUND(COUNT(CASE WHEN completed = 1 THEN 1 END) * 100.0 / COUNT(*), 1) as completion_rate,
        AVG(CASE WHEN completed = 1 THEN actual_minutes END) as avg_completion_time,
        SUM(CASE WHEN completed = 1 THEN actual_minutes END) as total_study_time
    FROM tasks 
    WHERE tasks.user_id = user_id;
END //

-- Procedure to award badges based on achievements
CREATE PROCEDURE AwardBadges(IN user_id INT)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE badge_code VARCHAR(50);
    DECLARE badge_id INT;
    DECLARE current_streak INT;
    DECLARE total_completed INT;
    
    -- Get current user stats
    SELECT current_streak, total_completed_tasks INTO current_streak, total_completed
    FROM streaks WHERE user_id = user_id;
    
    -- Award first task badge
    IF total_completed = 1 THEN
        SELECT id INTO badge_id FROM badges WHERE code = 'FIRST_TASK';
        IF badge_id IS NOT NULL THEN
            INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES (user_id, badge_id);
        END IF;
    END IF;
    
    -- Award streak badges
    IF current_streak >= 3 THEN
        SELECT id INTO badge_id FROM badges WHERE code = 'THREE_DAY_STREAK';
        IF badge_id IS NOT NULL THEN
            INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES (user_id, badge_id);
        END IF;
    END IF;
    
    IF current_streak >= 7 THEN
        SELECT id INTO badge_id FROM badges WHERE code = 'SEVEN_DAY_STREAK';
        IF badge_id IS NOT NULL THEN
            INSERT IGNORE INTO user_badges (user_id, badge_id);
        END IF;
    END IF;
    
    IF current_streak >= 14 THEN
        SELECT id INTO badge_id FROM badges WHERE code = 'FOURTEEN_DAY_STREAK';
        IF badge_id IS NOT NULL THEN
            INSERT IGNORE INTO user_badges (user_id, badge_id);
        END IF;
    END IF;
    
    IF current_streak >= 21 THEN
        SELECT id INTO badge_id FROM badges WHERE code = 'TWENTY_ONE_DAY_STREAK';
        IF badge_id IS NOT NULL THEN
            INSERT IGNORE INTO user_badges (user_id, badge_id);
        END IF;
    END IF;
    
    IF current_streak >= 30 THEN
        SELECT id INTO badge_id FROM badges WHERE code = 'THIRTY_DAY_STREAK';
        IF badge_id IS NOT NULL THEN
            INSERT IGNORE INTO user_badges (user_id, badge_id);
        END IF;
    END IF;
END //

DELIMITER ;

-- =====================================================
-- TRIGGERS
-- =====================================================

-- Trigger to create default categories for new users
DELIMITER //
CREATE TRIGGER after_user_insert
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    CALL CreateDefaultCategories(NEW.id);
    
    -- Initialize streak record
    INSERT INTO streaks (user_id) VALUES (NEW.id);
END //
DELIMITER ;

-- Trigger to update streaks when tasks are completed
DELIMITER //
CREATE TRIGGER after_task_complete
AFTER UPDATE ON tasks
FOR EACH ROW
BEGIN
    IF NEW.completed = 1 AND OLD.completed = 0 THEN
        -- Update streaks table
        UPDATE streaks 
        SET 
            current_streak = CASE 
                WHEN DATE(NEW.completed_at) = DATE_ADD(last_active_date, INTERVAL 1 DAY) 
                THEN current_streak + 1
                WHEN DATE(NEW.completed_at) = last_active_date 
                THEN current_streak
                ELSE 1
            END,
            longest_streak = CASE 
                WHEN DATE(NEW.completed_at) = DATE_ADD(last_active_date, INTERVAL 1 DAY) 
                THEN GREATEST(longest_streak, current_streak + 1)
                ELSE longest_streak
            END,
            last_active_date = DATE(NEW.completed_at),
            total_completed_tasks = total_completed_tasks + 1
        WHERE user_id = NEW.user_id;
        
        -- Award badges
        CALL AwardBadges(NEW.user_id);
    END IF;
END //
DELIMITER ;

-- =====================================================
-- VIEWS
-- =====================================================

-- View for task overview with user info
CREATE VIEW task_overview AS
SELECT 
    t.id,
    t.user_id,
    u.name as user_name,
    t.title,
    t.description,
    t.category,
    t.priority,
    t.due_at,
    t.estimated_minutes,
    t.actual_minutes,
    t.completed,
    t.completed_at,
    t.created_at,
    DATEDIFF(t.due_at, NOW()) as days_until_due,
    CASE 
        WHEN t.completed = 1 THEN 'Completed'
        WHEN t.due_at < NOW() THEN 'Overdue'
        WHEN DATEDIFF(t.due_at, NOW()) <= 1 THEN 'Due Soon'
        ELSE 'Upcoming'
    END as status
FROM tasks t
JOIN users u ON t.user_id = u.id;

-- View for user dashboard statistics
CREATE VIEW user_dashboard_stats AS
SELECT 
    u.id as user_id,
    u.name,
    COUNT(t.id) as total_tasks,
    COUNT(CASE WHEN t.completed = 1 THEN 1 END) as completed_tasks,
    COUNT(CASE WHEN t.completed = 0 THEN 1 END) as pending_tasks,
    COUNT(CASE WHEN t.completed = 0 AND t.due_at < NOW() THEN 1 END) as overdue_tasks,
    ROUND(COUNT(CASE WHEN t.completed = 1 THEN 1 END) * 100.0 / COUNT(*), 1) as completion_rate,
    s.current_streak,
    s.longest_streak,
    s.total_study_minutes
FROM users u
LEFT JOIN tasks t ON u.id = t.user_id
LEFT JOIN streaks s ON u.id = s.user_id
GROUP BY u.id, u.name, s.current_streak, s.longest_streak, s.total_study_minutes;

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

-- Additional performance indexes
CREATE INDEX idx_tasks_user_created ON tasks(user_id, created_at);
CREATE INDEX idx_tasks_user_completed_at ON tasks(user_id, completed_at);
CREATE INDEX idx_tasks_user_priority ON tasks(user_id, priority);
CREATE INDEX idx_study_sessions_user_date ON study_sessions(user_id, start_time);
CREATE INDEX idx_reminders_task_user ON reminders(task_id, user_id);
CREATE INDEX idx_user_badges_user ON user_badges(user_id);
CREATE INDEX idx_categories_user_default ON categories(user_id, is_default);

-- =====================================================
-- SAMPLE DATA FOR TESTING
-- =====================================================

-- Sample data removed - users will create their own data when they register

-- =====================================================
-- FINAL NOTES
-- =====================================================
-- This database schema includes:
-- 1. Complete user management
-- 2. Task management with priorities and categories
-- 3. Study session tracking
-- 4. Achievement system with badges
-- 5. Streak tracking
-- 6. Study goals
-- 7. Reminder system
-- 8. Performance indexes
-- 9. Stored procedures for common operations
-- 10. Triggers for automatic updates
-- 11. Views for easy data access
-- 12. Sample data for testing

-- To use this database:
-- 1. Run this file to create the complete database
-- 2. Update your application's database connection settings
-- 3. The system will automatically create default categories for new users
-- 4. All features are now available and optimized
