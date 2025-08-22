-- =====================================================
-- UPDATED DATABASE SCHEMA FOR STUDENT TIME MANAGEMENT ADVISOR
-- =====================================================
-- This file contains the updated database structure that matches our code
-- Use this file for the complete project

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
  username VARCHAR(100) NOT NULL UNIQUE,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  active TINYINT(1) DEFAULT 1,
  email_notifications TINYINT(1) DEFAULT 1,
  preferred_study_hour TINYINT DEFAULT 18, -- 6pm default
  timezone VARCHAR(50) DEFAULT 'UTC',
  last_login TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX idx_email (email),
  INDEX idx_username (username),
  INDEX idx_active (active),
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
  due_date DATETIME NOT NULL,
  estimated_minutes INT DEFAULT 60,
  actual_minutes INT NULL, -- Time actually spent
  status ENUM('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  completed_at DATETIME NULL,
  reminder_sent TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_due (user_id, due_date),
  INDEX idx_user_status (user_id, status),
  INDEX idx_user_category (user_id, category),
  INDEX idx_user_priority (user_id, priority),
  INDEX idx_due_date (due_date),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- User streaks table - Track completion streaks
CREATE TABLE user_streaks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  current_streak INT DEFAULT 0,
  longest_streak INT DEFAULT 0,
  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_user (user_id)
) ENGINE=InnoDB;

-- Remember tokens table - For "remember me" functionality
CREATE TABLE remember_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_token (token),
  INDEX idx_expires (expires_at)
) ENGINE=InnoDB;

-- Password reset tokens table
CREATE TABLE password_reset_tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_token (token),
  INDEX idx_expires (expires_at)
) ENGINE=InnoDB;

-- Email configuration table - For dynamic mail settings
CREATE TABLE email_config (
  id INT AUTO_INCREMENT PRIMARY KEY,
  smtp_host VARCHAR(255) NOT NULL,
  smtp_port INT NOT NULL,
  smtp_username VARCHAR(255) NOT NULL,
  smtp_password VARCHAR(255) NOT NULL,
  smtp_encryption VARCHAR(10) DEFAULT 'tls',
  from_email VARCHAR(255) NOT NULL,
  from_name VARCHAR(255) NOT NULL,
  reply_to VARCHAR(255),
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
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

-- Badges table - Achievement system
CREATE TABLE badges (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) UNIQUE NOT NULL,
  label VARCHAR(100) NOT NULL,
  description TEXT,
  icon VARCHAR(100) DEFAULT 'star',
  points INT DEFAULT 10,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- User badges table - Track user achievements
CREATE TABLE user_badges (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  badge_id INT NOT NULL,
  awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE,
  UNIQUE KEY unique_user_badge (user_id, badge_id)
) ENGINE=InnoDB;

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Insert default email configuration
INSERT INTO email_config (
  smtp_host, smtp_port, smtp_username, smtp_password,
  smtp_encryption, from_email, from_name, reply_to
) VALUES (
  'smtp.gmail.com', 587, 'your-email@gmail.com', 'your-app-password',
  'tls', 'noreply@studenttimeadvisor.com', 'Student Time Advisor', 'support@studenttimeadvisor.com'
);

-- Insert sample user (password: password123)
INSERT INTO users (username, first_name, last_name, email, password, active, email_notifications) VALUES 
('demo_user', 'Demo', 'User', 'demo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1);

-- Insert sample tasks
INSERT INTO tasks (user_id, title, description, category, priority, due_date, estimated_minutes, status) VALUES 
(1, 'Complete Project Report', 'Write the final project report for CS101', 'Assignment', 'High', DATE_ADD(NOW(), INTERVAL 3 DAY), 120, 'pending'),
(1, 'Study for Math Exam', 'Review chapters 5-8 for the upcoming exam', 'Exam', 'Urgent', DATE_ADD(NOW(), INTERVAL 1 DAY), 180, 'pending'),
(1, 'Lab Assignment 3', 'Complete the programming lab assignment', 'Lab', 'Medium', DATE_ADD(NOW(), INTERVAL 2 DAY), 90, 'pending');

-- Insert sample user streak
INSERT INTO user_streaks (user_id, current_streak, longest_streak) VALUES (1, 0, 0);

-- Insert sample badges
INSERT INTO badges (code, label, description, points) VALUES 
('FIRST_TASK', 'First Task', 'Complete your first task', 10),
('THREE_DAY_STREAK', 'Three Day Streak', 'Maintain a 3-day completion streak', 25),
('SEVEN_DAY_STREAK', 'Week Warrior', 'Maintain a 7-day completion streak', 50),
('DEEP_FOCUS_120', 'Deep Focus', 'Study for 2+ hours in a single day', 30);

-- =====================================================
-- VIEWS FOR COMMON QUERIES
-- =====================================================

-- View for user dashboard data
CREATE VIEW user_dashboard AS
SELECT 
  u.id,
  u.username,
  u.first_name,
  u.last_name,
  u.email,
  COUNT(t.id) as total_tasks,
  COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks,
  COUNT(CASE WHEN t.status = 'pending' THEN 1 END) as pending_tasks,
  us.current_streak,
  us.longest_streak
FROM users u
LEFT JOIN tasks t ON u.id = t.user_id
LEFT JOIN user_streaks us ON u.id = us.user_id
WHERE u.active = 1
GROUP BY u.id;

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

-- Additional indexes for better performance
CREATE INDEX idx_tasks_user_status_due ON tasks(user_id, status, due_date);
CREATE INDEX idx_tasks_reminder ON tasks(user_id, reminder_sent, due_date);
CREATE INDEX idx_users_email_active ON users(email, active);
CREATE INDEX idx_remember_tokens_user_expires ON remember_tokens(user_id, expires_at);
CREATE INDEX idx_password_reset_tokens_user_expires ON password_reset_tokens(user_id, expires_at);

-- =====================================================
-- COMMENTS
-- =====================================================

-- This schema includes all tables and columns needed by the application
-- Key features:
-- 1. User management with authentication
-- 2. Task management with status tracking
-- 3. Streak tracking for motivation
-- 4. Remember me functionality
-- 5. Password reset capability
-- 6. Email configuration
-- 7. Badge system for gamification
-- 8. Study session tracking
-- 9. Proper indexing for performance
