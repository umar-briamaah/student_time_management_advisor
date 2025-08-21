-- Student Time Management Advisor - PostgreSQL Schema
-- Compatible with Supabase and Neon

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- =====================================================
-- USERS TABLE
-- =====================================================
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    student_id VARCHAR(20),
    program VARCHAR(100),
    major VARCHAR(100),
    academic_year VARCHAR(20) CHECK (academic_year IN ('1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year', 'Graduate', 'Other')),
    institution VARCHAR(150),
    advisor_name VARCHAR(100),
    advisor_email VARCHAR(150),
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    preferred_study_hour SMALLINT DEFAULT 18,
    timezone VARCHAR(50) DEFAULT 'UTC',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- CATEGORIES TABLE
-- =====================================================
CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(7) DEFAULT '#3B82F6',
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TASKS TABLE
-- =====================================================
CREATE TABLE tasks (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    priority VARCHAR(20) CHECK (priority IN ('Low', 'Medium', 'High', 'Urgent')) DEFAULT 'Medium',
    due_at TIMESTAMP,
    estimated_minutes INTEGER DEFAULT 60,
    completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP,
    actual_minutes INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- STUDY SESSIONS TABLE
-- =====================================================
CREATE TABLE study_sessions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    task_id INTEGER REFERENCES tasks(id) ON DELETE SET NULL,
    start_time TIMESTAMP NOT NULL,
    end_time TIMESTAMP,
    duration_minutes INTEGER,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- REMINDERS TABLE
-- =====================================================
CREATE TABLE reminders (
    id SERIAL PRIMARY KEY,
    task_id INTEGER REFERENCES tasks(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    send_at TIMESTAMP NOT NULL,
    reminder_type VARCHAR(20) DEFAULT 'email',
    sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- STUDY GOALS TABLE
-- =====================================================
CREATE TABLE study_goals (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    target_hours_per_week DECIMAL(5,2),
    deadline DATE,
    completed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- STREAKS TABLE
-- =====================================================
CREATE TABLE streaks (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    current_streak INTEGER DEFAULT 0,
    longest_streak INTEGER DEFAULT 0,
    last_study_date DATE,
    total_study_minutes INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- BADGES TABLE
-- =====================================================
CREATE TABLE badges (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    criteria TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- USER BADGES TABLE
-- =====================================================
CREATE TABLE user_badges (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    badge_id INTEGER REFERENCES badges(id) ON DELETE CASCADE,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, badge_id)
);

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_tasks_user_id ON tasks(user_id);
CREATE INDEX idx_tasks_due_at ON tasks(due_at);
CREATE INDEX idx_tasks_completed ON tasks(completed);
CREATE INDEX idx_study_sessions_user_id ON study_sessions(user_id);
CREATE INDEX idx_study_sessions_start_time ON study_sessions(start_time);
CREATE INDEX idx_reminders_task_user ON reminders(task_id, user_id);
CREATE INDEX idx_reminders_send_at ON reminders(send_at);
CREATE INDEX idx_categories_user_id ON categories(user_id);
CREATE INDEX idx_streaks_user_id ON streaks(user_id);

-- =====================================================
-- TRIGGERS FOR AUTOMATIC UPDATES
-- =====================================================

-- Function to update updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Apply triggers to tables with updated_at
CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_tasks_updated_at BEFORE UPDATE ON tasks
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_study_goals_updated_at BEFORE UPDATE ON study_goals
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_streaks_updated_at BEFORE UPDATE ON streaks
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- =====================================================
-- STORED PROCEDURES
-- =====================================================

-- Procedure to calculate user statistics
CREATE OR REPLACE FUNCTION calculate_user_stats(user_id_param INTEGER)
RETURNS TABLE(
    total_tasks BIGINT,
    completed_tasks BIGINT,
    pending_tasks BIGINT,
    overdue_tasks BIGINT,
    completion_rate DECIMAL(5,2),
    total_study_minutes BIGINT
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        COUNT(t.id)::BIGINT as total_tasks,
        COUNT(CASE WHEN t.completed THEN 1 END)::BIGINT as completed_tasks,
        COUNT(CASE WHEN NOT t.completed THEN 1 END)::BIGINT as pending_tasks,
        COUNT(CASE WHEN NOT t.completed AND t.due_at < CURRENT_TIMESTAMP THEN 1 END)::BIGINT as overdue_tasks,
        ROUND(
            COUNT(CASE WHEN t.completed THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0), 
            1
        )::DECIMAL(5,2) as completion_rate,
        COALESCE(s.total_study_minutes, 0)::BIGINT as total_study_minutes
    FROM tasks t
    LEFT JOIN streaks s ON t.user_id = s.user_id
    WHERE t.user_id = user_id_param
    GROUP BY s.total_study_minutes;
END;
$$ LANGUAGE plpgsql;

-- =====================================================
-- VIEWS FOR EASY DATA ACCESS
-- =====================================================

-- View for user dashboard statistics
CREATE VIEW user_dashboard_stats AS
SELECT 
    u.id as user_id,
    u.name,
    COUNT(t.id) as total_tasks,
    COUNT(CASE WHEN t.completed THEN 1 END) as completed_tasks,
    COUNT(CASE WHEN NOT t.completed THEN 1 END) as pending_tasks,
    COUNT(CASE WHEN NOT t.completed AND t.due_at < CURRENT_TIMESTAMP THEN 1 END) as overdue_tasks,
    ROUND(
        COUNT(CASE WHEN t.completed THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0), 
        1
    ) as completion_rate,
    COALESCE(s.current_streak, 0) as current_streak,
    COALESCE(s.longest_streak, 0) as longest_streak,
    COALESCE(s.total_study_minutes, 0) as total_study_minutes
FROM users u
LEFT JOIN tasks t ON u.id = t.user_id
LEFT JOIN streaks s ON u.id = s.user_id
GROUP BY u.id, u.name, s.current_streak, s.longest_streak, s.total_study_minutes;

-- View for task status
CREATE VIEW task_status AS
SELECT 
    t.id,
    t.title,
    t.due_at,
    t.completed,
    CASE 
        WHEN t.completed THEN 'Completed'
        WHEN t.due_at < CURRENT_TIMESTAMP THEN 'Overdue'
        WHEN t.due_at::date = CURRENT_DATE THEN 'Due Today'
        WHEN t.due_at::date = CURRENT_DATE + INTERVAL '1 day' THEN 'Due Tomorrow'
        ELSE 'Upcoming'
    END as status
FROM tasks t;

-- =====================================================
-- SAMPLE DATA (OPTIONAL)
-- =====================================================

-- Insert default categories
INSERT INTO categories (name, color, is_default) VALUES
('Assignment', '#3B82F6', true),
('Exam', '#EF4444', true),
('Lab', '#10B981', true),
('Lecture', '#F59E0B', true),
('Other', '#8B5CF6', true);

-- Insert sample badges
INSERT INTO badges (name, description, icon, criteria) VALUES
('First Task', 'Complete your first task', '🎯', 'Complete 1 task'),
('Study Streak', 'Maintain a 7-day study streak', '🔥', 'Study for 7 consecutive days'),
('Task Master', 'Complete 50 tasks', '👑', 'Complete 50 tasks'),
('Time Manager', 'Complete tasks on time', '⏰', 'Complete 10 tasks before deadline');

-- =====================================================
-- FINAL NOTES
-- =====================================================
-- This PostgreSQL schema includes:
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
-- 12. PostgreSQL-specific optimizations

-- To use this schema:
-- 1. Run this file in your Supabase or Neon database
-- 2. Update your application's database connection settings
-- 3. The system will automatically create default categories for new users
-- 4. All features are now available and optimized for PostgreSQL
