-- Remove Demo Account and Related Data
-- Run this script to clean up any existing demo accounts

-- Delete demo user and all related data
DELETE FROM reminders WHERE user_id IN (SELECT id FROM users WHERE email = 'demo@example.com');
DELETE FROM study_sessions WHERE user_id IN (SELECT id FROM users WHERE email = 'demo@example.com');
DELETE FROM user_badges WHERE user_id IN (SELECT id FROM users WHERE email = 'demo@example.com');
DELETE FROM study_goals WHERE user_id IN (SELECT id FROM users WHERE email = 'demo@example.com');
DELETE FROM streaks WHERE user_id IN (SELECT id FROM users WHERE email = 'demo@example.com');
DELETE FROM tasks WHERE user_id IN (SELECT id FROM users WHERE email = 'demo@example.com');
DELETE FROM categories WHERE user_id IN (SELECT id FROM users WHERE email = 'demo@example.com');
DELETE FROM users WHERE email = 'demo@example.com';

-- Reset auto-increment counters if needed
-- Note: This may vary depending on your database system
-- For MySQL/MariaDB:
-- ALTER TABLE users AUTO_INCREMENT = 1;
-- ALTER TABLE tasks AUTO_INCREMENT = 1;
-- ALTER TABLE study_sessions AUTO_INCREMENT = 1;
-- ALTER TABLE reminders AUTO_INCREMENT = 1;
-- ALTER TABLE study_goals AUTO_INCREMENT = 1;
-- ALTER TABLE streaks AUTO_INCREMENT = 1;
-- ALTER TABLE user_badges AUTO_INCREMENT = 1;
-- ALTER TABLE categories AUTO_INCREMENT = 1;

-- For PostgreSQL:
-- ALTER SEQUENCE users_id_seq RESTART WITH 1;
-- ALTER SEQUENCE tasks_id_seq RESTART WITH 1;
-- ALTER SEQUENCE study_sessions_id_seq RESTART WITH 1;
-- ALTER SEQUENCE reminders_id_seq RESTART WITH 1;
-- ALTER SEQUENCE study_goals_id_seq RESTART WITH 1;
-- ALTER SEQUENCE streaks_id_seq RESTART WITH 1;
-- ALTER SEQUENCE user_badges_id_seq RESTART WITH 1;
-- ALTER SEQUENCE categories_id_seq RESTART WITH 1;

-- Verify cleanup
SELECT 'Demo account cleanup completed' as status;
SELECT COUNT(*) as remaining_users FROM users;
