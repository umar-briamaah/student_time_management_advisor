# 🏠 Local Development Guide

## 🎯 **Perfect for Temporary Projects!**

This guide helps you run your Student Time Management Advisor locally without any deployment complexity.

## 🚀 **Quick Start (3 Steps):**

### **Step 1: Start Development Server**

```bash
# Navigate to your project directory
cd /path/to/your/project

# Start PHP development server
php -S localhost:8000 -t public
```

### **Step 2: Open Your Browser**

```
http://localhost:8000
```

### **Step 3: Start Developing!**

- ✅ **No deployment needed**
- ✅ **Instant code changes**
- ✅ **Easy debugging**
- ✅ **Perfect for learning**

## 🔧 **Local Environment Setup:**

### **Create Local .env File:**

```bash
# Create .env file for local development
cat > .env << 'EOF'
# Local Development Configuration
APP_URL=http://localhost:8000

# Database (use local MySQL/PostgreSQL)
DB_HOST=localhost
DB_NAME=student_time_advisor
DB_USER=root
DB_PASS=your_password

# Development Settings
DEBUG=true
LOG_LEVEL=debug
TIMEZONE=UTC
EOF
```

### **Database Options:**

#### **Option A: Local MySQL (Recommended for now)**

```bash
# Install MySQL if you don't have it
sudo apt install mysql-server

# Create database
mysql -u root -p
CREATE DATABASE student_time_advisor;
USE student_time_advisor;
SOURCE sql/database_complete.sql;
```

#### **Option B: Local PostgreSQL**

```bash
# Install PostgreSQL
sudo apt install postgresql postgresql-contrib

# Create database
sudo -u postgres psql
CREATE DATABASE student_time_advisor;
\q

# Import schema
psql -U postgres -d student_time_advisor -f sql/database_postgresql.sql
```

## 📁 **Project Structure:**

```
student_time_management_advisor/
├── public/                    # Web root (accessible via browser)
│   ├── index.php             # Main entry point
│   ├── login.php             # Login page
│   ├── dashboard.php         # Dashboard
│   ├── tasks.php             # Tasks management
│   └── assets/               # CSS, JS, images
├── includes/                  # PHP includes
│   ├── config.php            # Configuration
│   ├── db.php                # Database connection
│   ├── auth.php              # Authentication
│   └── layout/               # Header/footer
├── sql/                      # Database schemas
├── .env                      # Local environment variables
└── README.md                 # This file
```

## 🚀 **Development Commands:**

### **Start Server:**

```bash
# Start development server
php -S localhost:8000 -t public

# Or specify a different port
php -S localhost:3000 -t public
```

### **Stop Server:**

```bash
# Press Ctrl+C in the terminal where server is running
```

### **Restart Server:**

```bash
# Stop with Ctrl+C, then start again
php -S localhost:8000 -t public
```

## 🔍 **Troubleshooting:**

### **Port Already in Use:**

```bash
# Check what's using port 8000
lsof -i :8000

# Kill the process
kill -9 <PID>

# Or use a different port
php -S localhost:3000 -t public
```

### **Database Connection Issues:**

```bash
# Check if MySQL is running
sudo systemctl status mysql

# Start MySQL if stopped
sudo systemctl start mysql

# Check database exists
mysql -u root -p -e "SHOW DATABASES;"
```

### **File Permission Issues:**

```bash
# Fix permissions
chmod 755 public/
chmod 644 public/*.php
chmod 644 .env
```

## 📱 **Access Your App:**

### **Main Pages:**

- **Home:** <http://localhost:8000>
- **Login:** <http://localhost:8000/login.php>
- **Register:** <http://localhost:8000/register.php>
- **Dashboard:** <http://localhost:8000/dashboard.php>
- **Tasks:** <http://localhost:8000/tasks.php>
- **Calendar:** <http://localhost:8000/calendar.php>
- **Motivation:** <http://localhost:8000/motivation.php>
- **Reports:** <http://localhost:8000/reports.php>

### **Assets:**

- **CSS:** <http://localhost:8000/assets/css/styles.css>
- **JavaScript:** <http://localhost:8000/assets/js/app.js>
- **Images:** <http://localhost:8000/assets/images/>

## 🎨 **Development Workflow:**

### **1. Make Code Changes:**

```bash
# Edit your PHP files
nano public/dashboard.php
# or use your favorite editor
```

### **2. Refresh Browser:**

- Just refresh the page to see changes
- No need to restart the server

### **3. Check for Errors:**

- Look at the terminal where server is running
- Check browser console (F12)
- Check browser network tab

## 🔧 **Useful Development Tools:**

### **Browser Extensions:**

- **Live Server** - Auto-refresh on file changes
- **PHP Console** - Debug PHP in browser
- **JSON Formatter** - Format API responses

### **Code Editors:**

- **VS Code** - Great PHP support
- **PHPStorm** - Professional PHP IDE
- **Sublime Text** - Lightweight editor

## 📊 **Local Database Management:**

### **View Data:**

```bash
# Connect to database
mysql -u root -p student_time_advisor

# View tables
SHOW TABLES;

# View users
SELECT * FROM users;

# View tasks
SELECT * FROM tasks;
```

### **Reset Database:**

```bash
# Drop and recreate
mysql -u root -p
DROP DATABASE student_time_advisor;
CREATE DATABASE student_time_advisor;
USE student_time_advisor;
SOURCE sql/database_complete.sql;
```

## 🎯 **Benefits of Local Development:**

- ✅ **Fast development** - No deployment delays
- ✅ **Easy debugging** - See errors immediately
- ✅ **No internet required** - Work offline
- ✅ **Full control** - Modify anything easily
- ✅ **Learning friendly** - Understand how everything works
- ✅ **No costs** - Everything runs on your machine

## 🚀 **Next Steps:**

1. **Start developing** - Make changes to your app
2. **Test features** - Try all functionality locally
3. **Debug issues** - Fix any problems you find
4. **Add features** - Enhance your application
5. **When ready** - Deploy to production later

## 🎉 **You're All Set!**

Your Student Time Management Advisor is now running locally at:
**<http://localhost:8000>**

**Happy coding! 🚀**

---

**Remember:** This is perfect for a temporary project. You can always deploy to production later when you're ready!
