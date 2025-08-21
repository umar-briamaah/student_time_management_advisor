# 🚀 Student Time Management Advisor - Deployment Guide

## 📋 Prerequisites

Before deploying, ensure you have:
- A web server (Apache/Nginx)
- PHP 8.0+ with required extensions
- MySQL/MariaDB 5.7+
- Composer (for dependencies)
- Git

## 🔧 Required PHP Extensions

```bash
# Install required PHP extensions
sudo apt-get install php8.0-mysql php8.0-pdo php8.0-mbstring php8.0-xml php8.0-curl php8.0-zip php8.0-gd
```

## 📁 Deployment Steps

### 1. **Prepare Your Environment**

```bash
# Clone the repository to your server
git clone <your-repository-url> /var/www/student-time-advisor
cd /var/www/student-time-advisor

# Set proper permissions
sudo chown -R www-data:www-data /var/www/student-time-advisor
sudo chmod -R 755 /var/www/student-time-advisor
sudo chmod -R 777 /var/www/student-time-advisor/logs  # If logs directory exists
```

### 2. **Install Dependencies**

```bash
# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Or if Composer is not available globally
php composer.phar install --no-dev --optimize-autoloader
```

### 3. **Database Setup**

```bash
# Create database
mysql -u root -p
CREATE DATABASE student_time_advisor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'student_advisor_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON student_time_advisor.* TO 'student_advisor_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import database schema
mysql -u student_advisor_user -p student_time_advisor < sql/database_complete.sql
```

### 4. **Environment Configuration**

```bash
# Copy environment template
cp env.example .env

# Edit .env file with your production values
nano .env
```

**Production .env Configuration:**
```env
# Database Configuration
DB_HOST=localhost
DB_NAME=student_time_advisor
DB_USER=student_advisor_user
DB_PASS=your_secure_password

# Application URL (Update with your domain)
APP_URL=https://yourdomain.com

# Email Configuration
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_ENCRYPTION=tls

# Security (Generate new secrets for production)
CSRF_SECRET=your-production-secret-key-here
SESSION_SECRET=your-production-session-secret-here

# Production Settings
DEBUG=false
LOG_LEVEL=error
TIMEZONE=UTC
```

### 5. **Web Server Configuration**

#### **Apache Configuration**

Create `/etc/apache2/sites-available/student-time-advisor.conf`:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/student-time-advisor/public
    
    <Directory /var/www/student-time-advisor/public>
        AllowOverride All
        Require all granted
        
        # Security headers
        Header always set X-Content-Type-Options nosniff
        Header always set X-Frame-Options DENY
        Header always set X-XSS-Protection "1; mode=block"
        Header always set Referrer-Policy "strict-origin-when-cross-origin"
    </Directory>
    
    # Logs
    ErrorLog ${APACHE_LOG_DIR}/student-time-advisor_error.log
    CustomLog ${APACHE_LOG_DIR}/student-time-advisor_access.log combined
    
    # Redirect to HTTPS (recommended)
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</VirtualHost>

<VirtualHost *:443>
    ServerName yourdomain.com
    DocumentRoot /var/www/student-time-advisor/public
    
    SSLEngine on
    SSLCertificateFile /path/to/your/certificate.crt
    SSLCertificateKeyFile /path/to/your/private.key
    SSLCertificateChainFile /path/to/your/chain.crt
    
    <Directory /var/www/student-time-advisor/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Security headers
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</VirtualHost>
```

#### **Nginx Configuration**

Create `/etc/nginx/sites-available/student-time-advisor`:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    
    root /var/www/student-time-advisor/public;
    index index.php index.html;
    
    # SSL Configuration
    ssl_certificate /path/to/your/certificate.crt;
    ssl_certificate_key /path/to/your/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    
    # Security headers
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains";
    
    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Static files
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Main location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # Security: Hide sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ /(includes|sql|vendor) {
        deny all;
    }
}
```

### 6. **Enable Site and Restart Services**

```bash
# For Apache
sudo a2ensite student-time-advisor.conf
sudo a2enmod rewrite headers ssl
sudo systemctl restart apache2

# For Nginx
sudo ln -s /etc/nginx/sites-available/student-time-advisor /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### 7. **SSL Certificate (Let's Encrypt)**

```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-apache
# OR for Nginx: sudo apt-get install certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com
# OR for Nginx: sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

### 8. **Cron Jobs Setup**

```bash
# Set up cron jobs for reminders and streaks
sudo crontab -e

# Add these lines:
# Send reminders every 15 minutes
*/15 * * * * /usr/bin/php /var/www/student-time-advisor/cron/send_reminders.php

# Calculate streaks daily at 12:10 AM
10 0 * * * /usr/bin/php /var/www/student-time-advisor/cron/calculate_streaks.php
```

### 9. **Final Security Checks**

```bash
# Set proper file permissions
sudo find /var/www/student-time-advisor -type f -exec chmod 644 {} \;
sudo find /var/www/student-time-advisor -type d -exec chmod 755 {} \;
sudo chmod 755 /var/www/student-time-advisor/public
sudo chmod 644 /var/www/student-time-advisor/.env

# Remove development files
sudo rm -rf /var/www/student-time-advisor/.git
sudo rm -rf /var/www/student-time-advisor/vendor/composer/installers
```

## 🔒 Security Checklist

- [ ] HTTPS enabled with valid SSL certificate
- [ ] Strong database passwords
- [ ] Environment variables properly set
- [ ] Sensitive directories protected
- [ ] Security headers configured
- [ ] File permissions restricted
- [ ] Development files removed
- [ ] Regular backups configured

## 📊 Performance Optimization

- [ ] Enable PHP OPcache
- [ ] Configure MySQL query cache
- [ ] Enable Gzip compression
- [ ] Set up CDN for static assets
- [ ] Configure browser caching

## 🚨 Troubleshooting

### Common Issues:

1. **500 Internal Server Error**
   - Check PHP error logs
   - Verify file permissions
   - Check .env configuration

2. **Database Connection Failed**
   - Verify database credentials
   - Check MySQL service status
   - Ensure database exists

3. **CSS/JS Not Loading**
   - Check file permissions
   - Verify web server configuration
   - Clear browser cache

### Log Locations:
- **Apache:** `/var/log/apache2/`
- **Nginx:** `/var/log/nginx/`
- **PHP:** `/var/log/php8.0-fpm.log`
- **Application:** Check your .env LOG_FILE setting

## 📞 Support

If you encounter issues during deployment:
1. Check the error logs
2. Verify all prerequisites are met
3. Ensure proper file permissions
4. Test database connectivity

## 🎉 Deployment Complete!

After completing all steps, your Student Time Management Advisor will be accessible at:
**https://yourdomain.com**

Remember to:
- Test all functionality
- Set up regular backups
- Monitor performance
- Keep dependencies updated
- Set up monitoring and alerts
