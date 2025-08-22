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

```bash
# Start MariaDB/MySQL service
sudo systemctl start mariadb

# Access as root
sudo mysql -u root -p

# Create database and user
CREATE DATABASE student_time_advisor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'dev_user'@'localhost' IDENTIFIED BY '';
GRANT ALL PRIVILEGES ON student_time_advisor.* TO 'dev_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import the complete schema
mysql -u root -p < sql/database_updated.sql
```

### 2. **Environment Configuration**

```bash
# Copy the example environment file
cp env.example.complete .env

# Generate secure secrets
php generate_secrets.php

# Edit with your database and mail settings
nano .env
```

**Required Environment Variables:**

```env
# Database
DB_HOST=localhost
DB_NAME=student_time_advisor
DB_USER=dev_user
DB_PASS=

# Application
APP_URL=http://localhost:8000
DEBUG=true
ENVIRONMENT=development

# Security (generate using generate_secrets.php)
CSRF_SECRET=your-generated-secret
SESSION_SECRET=your-generated-secret
API_KEY=your-generated-secret
JWT_SECRET=your-generated-secret
ENCRYPTION_KEY=your-generated-secret

# Mail (for reminders)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=your-email@gmail.com
MAIL_PASS=your-app-password
MAIL_FROM=your-email@gmail.com
MAIL_FROM_NAME=Student Time Advisor
MAIL_ENCRYPTION=tls
```

### 3. **Dependencies Installation**

```bash
# Install Node.js dependencies for Tailwind CSS
npm install

# Install PHPMailer via Composer
composer install
```

### 4. **CSS Build**

```bash
# Build production CSS
./build-css.sh

# Or for development (watch mode)
./dev-css.sh
```

### 5. **Start Development Server**

```bash
# Start from the public directory
cd public
php -S localhost:8000
```

## 🏗️ Architecture

### **Frontend**

- **Tailwind CSS**: Modern, utility-first CSS framework
- **Vanilla JavaScript**: No heavy frameworks, fast and lightweight
- **Responsive Design**: Mobile-first approach

### **Backend**

- **PHP 8.0+**: Modern PHP with type hints and features
- **PDO**: Secure database connections
- **PHPMailer**: Professional email handling
- **Session Management**: Secure user authentication

### **Database**

- **MySQL/MariaDB**: Reliable relational database
- **Optimized Schema**: Proper indexing and relationships
- **Data Integrity**: Foreign keys and constraints

## 📁 Project Structure

``` text
student_time_management_advisor/
├── public/                 # Web-accessible files
│   ├── assets/            # CSS, JS, images
│   ├── *.php             # Main application pages
│   └── .htaccess         # Apache configuration
├── includes/              # PHP includes
│   ├── config.php        # Configuration and environment
│   ├── db.php           # Database connection
│   ├── auth.php         # Authentication
│   ├── functions.php    # Helper functions
│   ├── mailer.php       # Email system
│   └── security.php     # Security features
├── cron/                 # Scheduled tasks
│   ├── send_reminders.php
│   └── calculate_streaks.php
├── sql/                  # Database schema
│   └── database_updated.sql
├── src/                  # Tailwind source CSS
├── vendor/               # Composer dependencies
└── node_modules/         # Node.js dependencies
```

## 🔧 Development

### **CSS Development**

```bash
# Watch for changes
npm run dev

# Build for production
npm run build
```

### **Database Changes**

```bash
# Export current schema
mysqldump -u dev_user student_time_advisor > sql/backup.sql

# Import updated schema
mysql -u dev_user student_time_advisor < sql/database_updated.sql
```

### **Testing**

```bash
# Test database connection
php -r "require_once 'includes/config.php'; require_once 'includes/db.php'; DB::conn(); echo 'Connection successful!';"

# Test email system
# Visit /email_test.php after login
```

## 🚀 Production Deployment

### **Environment Setup**

```bash
# Set production environment
ENVIRONMENT=production
DEBUG=false

# Use strong secrets
# Generate new secrets for production
php generate_secrets.php
```

### **Web Server**

- **Apache**: Use provided .htaccess
- **Nginx**: Configure for PHP-FPM
- **HTTPS**: Enable SSL/TLS

### **Cron Jobs**

```bash
# Add to crontab
# Send reminders every 15 minutes
*/15 * * * * php /path/to/cron/send_reminders.php

# Calculate streaks daily at 00:10
10 0 * * * php /path/to/cron/calculate_streaks.php
```

## 🐛 Troubleshooting

### **Common Issues**

1. **Database Connection Failed**
   - Check MariaDB service: `sudo systemctl status mariadb`
   - Verify credentials in `.env`
   - Test connection: `mysql -u dev_user -p`

2. **Navigation Not Working**
   - Ensure server is running from `public/` directory
   - Check `APP_URL` in `.env`
   - Clear browser cache

3. **CSS Not Loading**
   - Run `npm run build` to generate CSS
   - Check file permissions on `public/assets/css/`

4. **Email Not Sending**
   - Verify SMTP settings in `.env`
   - Check PHPMailer installation
   - Test with `/email_test.php`

### **Debug Mode**

```bash
# Enable debug mode in .env
DEBUG=true

# Check error logs
tail -f /var/log/apache2/error.log
```

## 📝 License

This project is open source and available under the MIT License.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📞 Support

For issues and questions:

- Check the troubleshooting section
- Review error logs
- Test with debug mode enabled
- Ensure all dependencies are installed

---

**Built with ❤️ for students who want to manage their time effectively!**
