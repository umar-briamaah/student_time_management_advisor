-- Seed data for Student Time Management Advisor
-- This file contains sample data to help you get started

-- Sample user (password: password123)
INSERT IGNORE INTO users (name, email, password_hash, preferred_study_hour) VALUES
('Demo Student', 'demo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 18);

-- Sample tasks for the demo user
INSERT IGNORE INTO tasks (user_id, title, description, category, due_at, estimated_minutes, completed, completed_at) VALUES
(1, 'Complete Math Assignment 3', 'Finish the calculus problems from Chapter 5', 'Assignment', DATE_ADD(NOW(), INTERVAL 2 DAY), 120, 0, NULL),
(1, 'Study for Physics Exam', 'Review chapters 8-12, practice problems', 'Exam', DATE_ADD(NOW(), INTERVAL 5 DAY), 180, 0, NULL),
(1, 'Lab Report - Chemistry', 'Write up the titration experiment results', 'Lab', DATE_ADD(NOW(), INTERVAL 1 DAY), 90, 0, NULL),
(1, 'Read History Chapter 7', 'Read about the Industrial Revolution', 'Lecture', DATE_ADD(NOW(), INTERVAL 3 DAY), 60, 0, NULL),
(1, 'Group Project Meeting', 'Meet with team to discuss final presentation', 'Other', DATE_ADD(NOW(), INTERVAL 4 DAY), 60, 0, NULL),
(1, 'Submit Essay Draft', 'Turn in first draft of research paper', 'Assignment', DATE_ADD(NOW(), INTERVAL 1 DAY), 45, 0, NULL),
(1, 'Practice Programming', 'Work on Python exercises', 'Lab', DATE_ADD(NOW(), INTERVAL 2 DAY), 75, 0, NULL),
(1, 'Review Notes', 'Go over lecture notes from this week', 'Lecture', DATE_ADD(NOW(), INTERVAL 1 DAY), 30, 0, NULL);

-- Sample completed tasks (for streak demonstration)
INSERT IGNORE INTO tasks (user_id, title, description, category, due_at, estimated_minutes, completed, completed_at) VALUES
(1, 'Complete Math Quiz', 'Online quiz on derivatives', 'Exam', DATE_SUB(NOW(), INTERVAL 1 DAY), 45, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 'Write Essay Outline', 'Create outline for research paper', 'Assignment', DATE_SUB(NOW(), INTERVAL 2 DAY), 60, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, 'Lab Safety Quiz', 'Complete safety certification', 'Lab', DATE_SUB(NOW(), INTERVAL 3 DAY), 30, 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(1, 'Read Chapter 6', 'Complete assigned reading', 'Lecture', DATE_SUB(NOW(), INTERVAL 4 DAY), 45, 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(1, 'Submit Homework 2', 'Turn in completed problems', 'Assignment', DATE_SUB(NOW(), INTERVAL 5 DAY), 90, 1, DATE_SUB(NOW(), INTERVAL 5 DAY));

-- Sample reminders for upcoming tasks
INSERT IGNORE INTO reminders (task_id, user_id, send_at) VALUES
(1, 1, DATE_ADD(NOW(), INTERVAL 1 DAY)),
(1, 1, DATE_ADD(NOW(), INTERVAL 36 HOUR)),
(2, 1, DATE_ADD(NOW(), INTERVAL 3 DAY)),
(2, 1, DATE_ADD(NOW(), INTERVAL 4 DAY + INTERVAL 12 HOUR)),
(3, 1, NOW()),
(3, 1, DATE_ADD(NOW(), INTERVAL 12 HOUR));

-- Sample streak data
INSERT IGNORE INTO streaks (user_id, current_streak, longest_streak, last_active_date) VALUES
(1, 5, 5, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Sample badges (user will have earned some through completed tasks)
INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES
(1, (SELECT id FROM badges WHERE code = 'FIRST_TASK')),
(1, (SELECT id FROM badges WHERE code = 'THREE_DAY_STREAK')),
(1, (SELECT id FROM badges WHERE code = 'SEVEN_DAY_STREAK')),
(1, (SELECT id FROM badges WHERE code = 'ON_TIME_SUBMIT'));

-- Note: The actual badge awarding will happen when the cron job runs
-- This is just to show what the system looks like with some data