-- Add student program fields to users table
ALTER TABLE users 
ADD COLUMN student_id VARCHAR(20) AFTER id,
ADD COLUMN program VARCHAR(100) AFTER student_id,
ADD COLUMN major VARCHAR(100) AFTER program,
ADD COLUMN academic_year ENUM('1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year', 'Graduate', 'Other') AFTER major,
ADD COLUMN institution VARCHAR(150) AFTER academic_year,
ADD COLUMN advisor_name VARCHAR(100) AFTER institution,
ADD COLUMN advisor_email VARCHAR(150) AFTER advisor_name;

-- Add indexes for better performance
ALTER TABLE users 
ADD INDEX idx_student_id (student_id),
ADD INDEX idx_program (program),
ADD INDEX idx_academic_year (academic_year);

-- Update existing users with sample data (you can customize this)
UPDATE users SET 
    student_id = CONCAT('STU', LPAD(id, 6, '0')),
    program = 'Computer Science',
    major = 'Software Engineering',
    academic_year = '3rd Year',
    institution = 'University of Technology',
    advisor_name = 'Dr. Sarah Johnson',
    advisor_email = 'sarah.johnson@university.edu'
WHERE id = 6;

-- Add a comment to document the new fields
ALTER TABLE users 
MODIFY COLUMN student_id VARCHAR(20) COMMENT 'Unique student identification number',
MODIFY COLUMN program VARCHAR(100) COMMENT 'Academic program or degree program',
MODIFY COLUMN major VARCHAR(100) COMMENT 'Specific major or specialization',
MODIFY COLUMN academic_year ENUM('1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year', 'Graduate', 'Other') COMMENT 'Current academic year level',
MODIFY COLUMN institution VARCHAR(150) COMMENT 'Educational institution name',
MODIFY COLUMN advisor_name VARCHAR(100) COMMENT 'Academic advisor name',
MODIFY COLUMN advisor_email VARCHAR(150) COMMENT 'Academic advisor email address';
