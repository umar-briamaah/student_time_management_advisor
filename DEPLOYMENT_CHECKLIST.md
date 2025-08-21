# 🚀 Quick Deployment Checklist

## ✅ Pre-Deployment Checklist

- [ ] **Server Requirements Met**
  - [ ] PHP 8.0+ installed
  - [ ] MySQL/MariaDB 5.7+ installed
  - [ ] Composer installed
  - [ ] Web server (Apache/Nginx) configured
  - [ ] Domain pointing to server

- [ ] **Files Ready**
  - [ ] All code committed to git
  - [ ] Database schema ready (`sql/database_complete.sql`)
  - [ ] Environment template ready (`env.example`)
  - [ ] Dependencies installed (`composer install`)

## 🚀 Deployment Options

### Option 1: Automated Deployment (Recommended)
```bash
# Make script executable and run
chmod +x deploy.sh
./deploy.sh
```

### Option 2: Manual Deployment
Follow the detailed steps in `DEPLOYMENT.md`

## 🔧 Quick Commands

### Database Setup
```bash
mysql -u root -p
CREATE DATABASE student_time_advisor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'student_advisor_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON student_time_advisor.* TO 'student_advisor_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

mysql -u student_advisor_user -p student_time_advisor < sql/database_complete.sql
```

### Environment Setup
```bash
cp env.example .env
# Edit .env with your production values
nano .env
```

### Web Server (Apache)
```bash
sudo a2ensite student-time-advisor.conf
sudo a2enmod rewrite headers
sudo systemctl reload apache2
```

### SSL Certificate
```bash
sudo apt-get install certbot python3-certbot-apache
sudo certbot --apache -d yourdomain.com
```

## 🚨 Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| 500 Error | Check file permissions, .env configuration |
| Database Connection Failed | Verify credentials, MySQL service status |
| CSS/JS Not Loading | Check .htaccess, file permissions |
| Cron Jobs Not Working | Verify PHP path, file permissions |

## 📞 Emergency Contacts

- **Logs**: `/var/log/apache2/` or `/var/log/nginx/`
- **Application**: Check your .env LOG_FILE setting
- **Database**: `mysql -u root -p`

## 🎯 Post-Deployment Tasks

- [ ] Test all functionality
- [ ] Set up SSL certificate
- [ ] Configure backups
- [ ] Set up monitoring
- [ ] Test cron jobs
- [ ] Performance optimization

## 🔒 Security Checklist

- [ ] HTTPS enabled
- [ ] Strong database passwords
- [ ] Environment variables set
- [ ] Sensitive directories protected
- [ ] Security headers configured
- [ ] File permissions restricted

---

**Need Help?** Check `DEPLOYMENT.md` for detailed instructions!
