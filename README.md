# Student Time Management Advisor (Enhanced)

A modern, feature-rich web application for students to manage their academic tasks, track progress, and maintain motivation through gamification elements.

## ✨ Features

### 🎯 **Core Task Management**

- **Smart Task Creation**: Create tasks with categories, due dates, and time estimates
- **Priority Scoring**: AI-powered task prioritization based on urgency and importance
- **Edit & Delete**: Full CRUD operations for task management
- **Status Tracking**: Mark tasks as complete with timestamps

### 📊 **Advanced Dashboard**

- **Progress Overview**: Visual statistics and completion rates
- **Priority Queue**: Top 5 most important tasks ranked by algorithm
- **Streak Tracking**: Current and longest completion streaks
- **Quick Actions**: Fast access to common functions

### 📅 **Interactive Calendar**

- **FullCalendar Integration**: Monthly, weekly, and daily views
- **Visual Task Management**: Color-coded by category
- **Click to Create**: Select time slots to create new tasks
- **Task Details**: Click events to view and manage tasks

### 🏆 **Motivation System**

- **Achievement Badges**: 8 different badges for various accomplishments
- **Streak Milestones**: Track progress toward streak goals
- **Daily Quotes**: Motivational content to keep you inspired
- **Progress Visualization**: Charts and progress bars

### 📈 **Analytics & Reports**

- **Monthly Statistics**: Track task creation and completion trends
- **Category Breakdown**: Performance analysis by task type
- **Weekly Insights**: Detailed weekly progress tracking
- **Productivity Scoring**: Overall performance metrics

### 🔔 **Smart Reminders**

- **Automated Scheduling**: T-48h and T-12h reminders
- **Beautiful Email Templates**: Professional HTML emails
- **Cron-based Delivery**: Reliable reminder system
- **Overdue Tracking**: Monitor late submissions

## 🚀 Quick Start

### 1. **Database Setup**

```sql
CREATE DATABASE IF NOT EXISTS student_time_advisor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE student_time_advisor;
SOURCE sql/schema.sql;
SOURCE sql/seed.sql; -- Optional demo data
```

### 2. **Environment Configuration**

```bash
# Copy the example environment file
cp .env.example .env

# Edit with your database and mail settings
nano .env
```

**Required Environment Variables:**

```env
# Database
DB_HOST=localhost
DB_NAME=student_time_advisor
DB_USER=root
DB_PASS=your_password

# Application
APP_URL=http://localhost/student-time-advisor-php/public

# Mail (for reminders)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=your-email@gmail.com
MAIL_PASS=your-app-password
MAIL_FROM=your-email@gmail.com
MAIL_FROM_NAME=Student Time Advisor
```

### 3. **Dependencies Installation**

```bash
# Install PHPMailer via Composer
composer require phpmailer/phpmailer

# Or manually download and place in vendor/ directory
```

### 4. **Web Server Configuration**

Point your web server's document root to the `public/` directory.

**Apache Example:**

```apache
<VirtualHost *:80>
    DocumentRoot /path/to/student-time-advisor-php/public
    ServerName student-time-advisor.local
    
    <Directory /path/to/student-time-advisor-php/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Nginx Example:**

```nginx
server {
    listen 80;
    server_name student-time-advisor.local;
    root /path/to/student-time-advisor-php/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 5. **Cron Job Setup**

```bash
# Edit crontab
crontab -e

# Add these lines:
# Send reminders every 15 minutes
*/15 * * * * php /path/to/student-time-advisor-php/cron/send_reminders.php >> /var/log/sta_reminders.log 2>&1

# Calculate streaks daily at 00:10
10 0 * * * php /path/to/student-time-advisor-php/cron/calculate_streaks.php >> /var/log/sta_streaks.log 2>&1
```

## 🧠 **Priority Algorithm**

The system uses a sophisticated algorithm to rank tasks by importance:

```php
function priority_score($task) {
    $days_left = (due_at - now) in days;
    
    // Base score based on urgency
    $base = ($days_left <= 2) ? 100 : (($days_left <= 7) ? 60 : 30);
    
    // Category weight multiplier
    $weight = category_weight($task['category']);
    // Exam: 1.0, Assignment: 0.8, Lab: 0.7, Lecture: 0.5, Other: 0.3
    
    // Progress factor (incomplete tasks rank higher)
    $progress_factor = $task['completed'] ? 0 : 1;
    
    return $base * $weight * $progress_factor;
}
```

## 🏆 **Badge System**

| Badge | Requirement | Description |
|-------|-------------|-------------|
| 🎯 First Task | Complete 1 task | Get started with your first completion |
| 🔥 3-Day Streak | 3 consecutive days | Build momentum with consistency |
| 🔥 7-Day Streak | 7 consecutive days | Weekly habit formation |
| ⚡ 14-Day Streak | 14 consecutive days | Bi-weekly excellence |
| ⚡ 21-Day Streak | 21 consecutive days | Three-week mastery |
| 👑 30-Day Streak | 30 consecutive days | Monthly achievement |
| ⏰ On-Time | Complete before due | Timeliness excellence |
| 🎯 Deep Focus | 120+ min in a day | Extended focus achievement |

## 📧 **Email Reminders**

The system automatically sends beautiful HTML emails:

- **T-48h**: Early warning reminder
- **T-12h**: Final reminder before due
- **Professional templates** with task details and time remaining
- **Category-based styling** for visual appeal

## 🔧 **Technical Details**

### **Security Features**

- PDO prepared statements (SQL injection protection)
- Password hashing with bcrypt/argon2
- CSRF token protection on all forms
- Input validation and output escaping
- Session-based authentication

### **Performance Optimizations**

- Database indexes on frequently queried columns
- Efficient queries with proper JOINs
- Caching of user statistics
- Optimized badge calculations

### **File Structure**

``` text
student_time_management_advisor/
├── cron/                    # Automated tasks
│   ├── calculate_streaks.php
│   └── send_reminders.php
├── includes/                # Core functionality
│   ├── auth.php            # Authentication
│   ├── config.php          # Configuration
│   ├── db.php             # Database connection
│   ├── functions.php       # Helper functions
│   ├── mailer.php         # Email functionality
│   └── layout/            # UI components
├── public/                 # Web-accessible files
│   ├── assets/            # CSS, JS, images
│   ├── dashboard.php      # Main dashboard
│   ├── tasks.php          # Task management
│   ├── calendar.php       # Calendar view
│   ├── motivation.php     # Badges and streaks
│   └── reports.php        # Analytics
├── sql/                   # Database files
│   ├── schema.sql         # Table structure
│   └── seed.sql           # Sample data
└── vendor/                # Composer dependencies
```

## 🌟 **Usage Tips**

### **Getting Started**

1. **Create your first task** - Start with something simple
2. **Set realistic due dates** - Don't overwhelm yourself
3. **Use categories** - Organize by academic type
4. **Check the dashboard** - See your priorities at a glance

### **Maintaining Streaks**

1. **Complete at least one task daily** - Even small wins count
2. **Plan ahead** - Create tasks for tomorrow today
3. **Use the calendar** - Visual planning helps consistency
4. **Check motivation page** - Track your achievements

### **Maximizing Productivity**

1. **Focus on high-priority tasks** - Use the priority algorithm
2. **Break down large tasks** - Smaller pieces are more manageable
3. **Set time estimates** - Helps with planning
4. **Review reports regularly** - Identify patterns and improve

## 🐛 **Troubleshooting**

### **Common Issues**

## **Database Connection Error**

- Verify database credentials in `.env`
- Ensure MySQL/MariaDB is running
- Check database exists and is accessible

## **Email Not Sending**

- Verify SMTP settings in `.env`
- Check firewall/port restrictions
- Use app passwords for Gmail

## **Cron Jobs Not Working**

- Verify cron service is running
- Check file permissions
- Review log files for errors

## **Page Not Loading**

- Ensure web server points to `public/` directory
- Check PHP version (7.4+ recommended)
- Verify `.htaccess` is present

### **Log Files**

- **Reminders**: `/var/log/sta_reminders.log`
- **Streaks**: `/var/log/sta_streaks.log`
- **Web errors**: Check web server error logs

## 🔄 **Updates & Maintenance**

### **Regular Maintenance**

- **Database backups**: Weekly automated backups recommended
- **Log rotation**: Implement log rotation for cron logs
- **Security updates**: Keep PHP and dependencies updated
- **Performance monitoring**: Monitor database query performance

### **Scaling Considerations**

- **Database optimization**: Add indexes for large datasets
- **Caching**: Implement Redis/Memcached for high traffic
- **Load balancing**: Multiple web servers for high availability
- **CDN**: Use CDN for static assets

## 📚 **API & Extensions**

The system is designed to be extensible:

- **REST API endpoints** can be added for mobile apps
- **Webhook support** for external integrations
- **Plugin system** for additional features
- **Custom themes** for branding

## 🤝 **Contributing**

Contributions are welcome! Areas for improvement:

- **Mobile app development**
- **Additional analytics**
- **Integration with LMS systems**
- **Advanced notification systems**
- **Performance optimizations**

## 📄 **License**

This project is open source and available under the MIT License.

## 🆘 **Support**

For support and questions:

- **Documentation**: Check this README first
- **Issues**: Report bugs via GitHub issues
- **Community**: Join our discussion forum
- **Email**: Contact the development team

---

**Built with ❤️ for students who want to excel in their academic journey.**
