-- Schema for Student Time Management Advisor

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  preferred_study_hour TINYINT DEFAULT 18, -- 6pm
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  category ENUM('Lecture','Lab','Exam','Assignment','Other') DEFAULT 'Other',
  due_at DATETIME NOT NULL,
  estimated_minutes INT DEFAULT 60,
  completed TINYINT(1) DEFAULT 0,
  completed_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_due (user_id, due_at),
  INDEX idx_user_completed (user_id, completed),
  INDEX idx_user_category (user_id, category)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reminders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  user_id INT NOT NULL,
  send_at DATETIME NOT NULL,
  sent TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_reminders_send_sent (send_at, sent),
  INDEX idx_reminders_user_sent (user_id, sent)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS streaks (
  user_id INT PRIMARY KEY,
  current_streak INT DEFAULT 0,
  longest_streak INT DEFAULT 0,
  last_active_date DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS badges (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) UNIQUE NOT NULL,
  label VARCHAR(100) NOT NULL,
  description TEXT,
  icon VARCHAR(100) DEFAULT 'star',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_badges (
  user_id INT NOT NULL,
  badge_id INT NOT NULL,
  awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, badge_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed badges with enhanced descriptions
INSERT IGNORE INTO badges (code, label, description, icon) VALUES
('FIRST_TASK','First Task Completed','Complete your very first task to get started','check-circle'),
('THREE_DAY_STREAK','3-Day Streak','Maintain a 3-day consecutive completion streak','flame'),
('SEVEN_DAY_STREAK','7-Day Streak','Maintain a 7-day consecutive completion streak','fire'),
('FOURTEEN_DAY_STREAK','14-Day Streak','Maintain a 14-day consecutive completion streak','zap'),
('TWENTY_ONE_DAY_STREAK','21-Day Streak','Maintain a 21-day consecutive completion streak','bolt'),
('THIRTY_DAY_STREAK','30-Day Streak','Maintain a 30-day consecutive completion streak','crown'),
('ON_TIME_SUBMIT','On-Time Submission','Complete a task before its due date','clock'),
('DEEP_FOCUS_120','Deep Focus 120','Complete tasks totaling 120+ minutes in a single day','target');

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_tasks_user_created ON tasks(user_id, created_at);
CREATE INDEX IF NOT EXISTS idx_tasks_user_completed_at ON tasks(user_id, completed_at);
CREATE INDEX IF NOT EXISTS idx_user_badges_user ON user_badges(user_id);
CREATE INDEX IF NOT EXISTS idx_user_badges_awarded ON user_badges(awarded_at);