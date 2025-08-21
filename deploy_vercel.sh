#!/bin/bash

# 🚀 Vercel + Supabase Deployment Script
# This script automates the Vercel deployment process

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 Starting Vercel + Supabase Deployment...${NC}"

# Check prerequisites
echo -e "${YELLOW}📋 Checking prerequisites...${NC}"

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo -e "${RED}❌ Node.js is not installed${NC}"
    echo -e "${YELLOW}Please install Node.js from https://nodejs.org/${NC}"
    exit 1
fi

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    echo -e "${RED}❌ npm is not installed${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Node.js $(node --version) detected${NC}"
echo -e "${GREEN}✅ npm $(npm --version) detected${NC}"

# Check if Vercel CLI is installed
if ! command -v vercel &> /dev/null; then
    echo -e "${YELLOW}📦 Installing Vercel CLI...${NC}"
    npm install -g vercel
else
    echo -e "${GREEN}✅ Vercel CLI detected${NC}"
fi

# Check if vercel.json exists
if [ ! -f "vercel.json" ]; then
    echo -e "${RED}❌ vercel.json not found${NC}"
    echo -e "${YELLOW}Please ensure vercel.json exists in your project root${NC}"
    exit 1
fi

echo -e "${GREEN}✅ vercel.json found${NC}"

# Check if PostgreSQL schema exists
if [ ! -f "sql/database_postgresql.sql" ]; then
    echo -e "${RED}❌ PostgreSQL schema not found${NC}"
    echo -e "${YELLOW}Please ensure sql/database_postgresql.sql exists${NC}"
    exit 1
fi

echo -e "${GREEN}✅ PostgreSQL schema found${NC}"

# Check if database connection file exists
if [ ! -f "includes/db_postgresql.php" ]; then
    echo -e "${RED}❌ PostgreSQL database connection file not found${NC}"
    echo -e "${YELLOW}Please ensure includes/db_postgresql.php exists${NC}"
    exit 1
fi

echo -e "${GREEN}✅ PostgreSQL database connection file found${NC}"

# Setup instructions
echo -e "${BLUE}📋 Setup Instructions:${NC}"
echo -e "${YELLOW}1. Create Supabase project at https://supabase.com${NC}"
echo -e "${YELLOW}2. Run the PostgreSQL schema in Supabase SQL Editor${NC}"
echo -e "${YELLOW}3. Get your connection details from Supabase Settings → API${NC}"
echo -e "${YELLOW}4. Create .env file with your Supabase credentials${NC}"

# Ask if user is ready
read -p "Have you completed the Supabase setup? (y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}Please complete Supabase setup first, then run this script again${NC}"
    exit 1
fi

# Check if .env file exists
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}📝 Creating .env template...${NC}"
    cat > .env << 'EOF'
# Supabase Configuration
SUPABASE_URL=your_project_url_here
SUPABASE_ANON_KEY=your_anon_key_here
SUPABASE_SERVICE_KEY=your_service_key_here

# Database Configuration (PostgreSQL)
DB_HOST=db.your_project_ref.supabase.co
DB_NAME=postgres
DB_USER=postgres
DB_PASS=your_database_password_here
DB_PORT=5432

# Application URL (will be set by Vercel)
APP_URL=https://your-vercel-domain.vercel.app

# Security (generate these with: openssl rand -hex 32)
CSRF_SECRET=your_generated_secret_here
SESSION_SECRET=your_generated_secret_here

# Production Settings
DEBUG=false
LOG_LEVEL=error
TIMEZONE=UTC
EOF

    echo -e "${YELLOW}⚠️  Please edit .env file with your actual Supabase credentials${NC}"
    echo -e "${YELLOW}Press Enter when you're ready to continue...${NC}"
    read
fi

# Check if .env has been configured
if grep -q "your_project_url_here" .env; then
    echo -e "${RED}❌ Please configure your .env file with actual Supabase credentials${NC}"
    echo -e "${YELLOW}Edit .env file and run this script again${NC}"
    exit 1
fi

echo -e "${GREEN}✅ .env file configured${NC}"

# Update database connection
echo -e "${YELLOW}🔄 Updating database connection...${NC}"
if [ -f "includes/db.php" ]; then
    echo -e "${YELLOW}Backing up existing db.php...${NC}"
    cp includes/db.php includes/db_mysql_backup.php
fi

cp includes/db_postgresql.php includes/db.php
echo -e "${GREEN}✅ Database connection updated to PostgreSQL${NC}"

# Deploy to Vercel
echo -e "${YELLOW}🚀 Deploying to Vercel...${NC}"
echo -e "${BLUE}Follow the prompts below:${NC}"

# Deploy
vercel

# Check deployment status
if [ $? -eq 0 ]; then
    echo -e "${GREEN}🎉 Deployment successful!${NC}"
    
    # Get project URL
    PROJECT_URL=$(vercel ls --json | grep -o '"url":"[^"]*"' | head -1 | cut -d'"' -f4)
    
    if [ ! -z "$PROJECT_URL" ]; then
        echo -e "${GREEN}🌐 Your app is live at: ${PROJECT_URL}${NC}"
    fi
    
    echo -e "${BLUE}📋 Next steps:${NC}"
    echo -e "${YELLOW}1. Test your application at the URL above${NC}"
    echo -e "${YELLOW}2. Set up environment variables in Vercel dashboard${NC}"
    echo -e "${YELLOW}3. Configure custom domain (optional)${NC}"
    echo -e "${YELLOW}4. Set up monitoring and analytics${NC}"
    
else
    echo -e "${RED}❌ Deployment failed${NC}"
    echo -e "${YELLOW}Check the error messages above and try again${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Vercel deployment script completed!${NC}"
