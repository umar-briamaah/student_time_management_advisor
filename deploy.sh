#!/bin/bash

# 🚀 Student Time Management Advisor - Deployment Script
# This script automates the deployment process

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="student-time-advisor"
WEB_ROOT="/var/www"
PROJECT_PATH="$WEB_ROOT/$PROJECT_NAME"
DB_NAME="student_time_advisor"
DB_USER="student_advisor_user"

echo -e "${BLUE}🚀 Starting deployment of Student Time Management Advisor...${NC}"

# Check if running as root
if [[ $EUID -eq 0 ]]; then
   echo -e "${RED}❌ This script should not be run as root${NC}"
   exit 1
fi

# Check prerequisites
echo -e "${YELLOW}📋 Checking prerequisites...${NC}"

# Check if required commands exist
command -v php >/dev/null 2>&1 || { echo -e "${RED}❌ PHP is not installed${NC}"; exit 1; }
command -v composer >/dev/null 2>&1 || { echo -e "${RED}❌ Composer is not installed${NC}"; exit 1; }
command -v mysql >/dev/null 2>&1 || { echo -e "${RED}❌ MySQL client is not installed${NC}"; exit 1; }

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
PHP_MAJOR=$(echo $PHP_VERSION | cut -d. -f1)
PHP_MINOR=$(echo $PHP_VERSION | cut -d. -f2)

if [ "$PHP_MAJOR" -lt 8 ] || ([ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -lt 0 ]); then
    echo -e "${RED}❌ PHP 8.0+ is required. Current version: $PHP_VERSION${NC}"
    exit 1
fi

echo -e "${GREEN}✅ PHP $PHP_VERSION detected${NC}"

# Check if project directory exists
if [ ! -d "$PROJECT_PATH" ]; then
    echo -e "${YELLOW}📁 Creating project directory...${NC}"
    sudo mkdir -p "$PROJECT_PATH"
    sudo chown $USER:$USER "$PROJECT_PATH"
fi

# Copy project files
echo -e "${YELLOW}📁 Copying project files...${NC}"
cp -r . "$PROJECT_PATH/"
cd "$PROJECT_PATH"

# Set proper permissions
echo -e "${YELLOW}🔐 Setting file permissions...${NC}"
sudo chown -R www-data:www-data "$PROJECT_PATH"
sudo chmod -R 755 "$PROJECT_PATH"
sudo chmod -R 777 "$PROJECT_PATH/logs" 2>/dev/null || true

# Install dependencies
echo -e "${YELLOW}📦 Installing Composer dependencies...${NC}"
composer install --no-dev --optimize-autoloader

# Database setup
echo -e "${YELLOW}🗄️ Setting up database...${NC}"
read -p "Enter MySQL root password: " MYSQL_ROOT_PASS
read -p "Enter desired database password for $DB_USER: " DB_PASS

# Create database and user
mysql -u root -p"$MYSQL_ROOT_PASS" << EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF

# Import database schema
echo -e "${YELLOW}📊 Importing database schema...${NC}"
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < sql/database_complete.sql

# Environment configuration
echo -e "${YELLOW}⚙️ Setting up environment configuration...${NC}"
if [ ! -f .env ]; then
    cp env.example .env
fi

# Generate secure secrets
CSRF_SECRET=$(openssl rand -hex 32)
SESSION_SECRET=$(openssl rand -hex 32)

# Update .env file
sed -i "s/DB_USER=.*/DB_USER=$DB_USER/" .env
sed -i "s/DB_PASS=.*/DB_PASS=$DB_PASS/" .env
sed -i "s/CSRF_SECRET=.*/CSRF_SECRET=$CSRF_SECRET/" .env
sed -i "s/SESSION_SECRET=.*/SESSION_SECRET=$SESSION_SECRET/" .env
sed -i "s/DEBUG=.*/DEBUG=false/" .env

# Get domain from user
read -p "Enter your domain (e.g., yourdomain.com): " DOMAIN
sed -i "s|APP_URL=.*|APP_URL=https://$DOMAIN|" .env

# Web server configuration
echo -e "${YELLOW}🌐 Setting up web server configuration...${NC}"
read -p "Which web server are you using? (apache/nginx): " WEB_SERVER

if [ "$WEB_SERVER" = "apache" ]; then
    # Apache configuration
    APACHE_CONF="/etc/apache2/sites-available/$PROJECT_NAME.conf"
    
    sudo tee "$APACHE_CONF" > /dev/null << EOF
<VirtualHost *:80>
    ServerName $DOMAIN
    ServerAlias www.$DOMAIN
    DocumentRoot $PROJECT_PATH/public
    
    <Directory $PROJECT_PATH/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/${PROJECT_NAME}_error.log
    CustomLog \${APACHE_LOG_DIR}/${PROJECT_NAME}_access.log combined
</VirtualHost>
EOF

    # Enable site and modules
    sudo a2ensite "$PROJECT_NAME.conf"
    sudo a2enmod rewrite headers
    sudo systemctl reload apache2
    
elif [ "$WEB_SERVER" = "nginx" ]; then
    # Nginx configuration
    NGINX_CONF="/etc/nginx/sites-available/$PROJECT_NAME"
    
    sudo tee "$NGINX_CONF" > /dev/null << EOF
server {
    listen 80;
    server_name $DOMAIN www.$DOMAIN;
    
    root $PROJECT_PATH/public;
    index index.php index.html;
    
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /(includes|vendor|sql) {
        deny all;
    }
}
EOF

    # Enable site
    sudo ln -sf "$NGINX_CONF" "/etc/nginx/sites-enabled/"
    sudo nginx -t
    sudo systemctl reload nginx
fi

# Set up cron jobs
echo -e "${YELLOW}⏰ Setting up cron jobs...${NC}"
(crontab -l 2>/dev/null; echo "*/15 * * * * /usr/bin/php $PROJECT_PATH/cron/send_reminders.php") | crontab -
(crontab -l 2>/dev/null; echo "10 0 * * * /usr/bin/php $PROJECT_PATH/cron/calculate_streaks.php") | crontab -

# Final security setup
echo -e "${YELLOW}🔒 Final security setup...${NC}"
sudo chmod 644 "$PROJECT_PATH/.env"
sudo chmod 755 "$PROJECT_PATH/public"

# Remove development files
sudo rm -rf "$PROJECT_PATH/.git" 2>/dev/null || true
sudo rm -rf "$PROJECT_PATH/vendor/composer/installers" 2>/dev/null || true

echo -e "${GREEN}🎉 Deployment completed successfully!${NC}"
echo -e "${BLUE}📋 Next steps:${NC}"
echo -e "1. Set up SSL certificate with Let's Encrypt:"
echo -e "   sudo certbot --$WEB_SERVER -d $DOMAIN"
echo -e "2. Test your application at: https://$DOMAIN"
echo -e "3. Set up regular backups"
echo -e "4. Monitor logs and performance"
echo -e ""
echo -e "${GREEN}✅ Your Student Time Management Advisor is now live!${NC}"
