-- Simple seed data for Student Time Management Advisor

-- Sample user (password: password123)
INSERT IGNORE INTO users (name, email, password_hash, preferred_study_hour) VALUES
('Demo Student', 'demo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 18);

-- Sample tasks for the demo user
INSERT IGNORE INTO tasks (user_id, title, description, category, due_at, estimated_minutes, completed, completed_at) VALUES
(1, 'Complete Math Assignment 3', 'Finish the calculus problems from Chapter 5', 'Assignment', DATE_ADD(NOW(), INTERVAL 2 DAY), 120, 0, NULL),
(1, 'Study for Physics Exam', 'Review chapters 8-12, practice problems', 'Exam', DATE_ADD(NOW(), INTERVAL 5 DAY), 180, 0, NULL),
(1, 'Lab Report - Chemistry', 'Write up the titration experiment results', 'Lab', DATE_ADD(NOW(), INTERVAL 1 DAY), 90, 0, NULL);

-- Sample completed tasks
INSERT IGNORE INTO tasks (user_id, title, description, category, due_at, estimated_minutes, completed, completed_at) VALUES
(1, 'Complete Math Quiz', 'Online quiz on derivatives', 'Exam', DATE_SUB(NOW(), INTERVAL 1 DAY), 45, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 'Write Essay Outline', 'Create outline for research paper', 'Assignment', DATE_SUB(NOW(), INTERVAL 2 DAY), 60, 1, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Sample streak data
INSERT IGNORE INTO streaks (user_id, current_streak, longest_streak, last_active_date) VALUES
(1, 5, 5, DATE_SUB(NOW(), INTERVAL 1 DAY));
