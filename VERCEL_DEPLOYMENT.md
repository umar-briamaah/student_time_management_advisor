# 🚀 Vercel + Supabase Deployment Guide

## 🌟 **Why Vercel + Supabase?**

**Vercel:**
- ✅ **Free hosting** for PHP applications
- ✅ **Global CDN** for fast loading
- ✅ **Automatic deployments** from Git
- ✅ **Serverless PHP** runtime
- ✅ **Custom domains** support

**Supabase:**
- ✅ **Free PostgreSQL** database
- ✅ **Built-in authentication**
- ✅ **Real-time features**
- ✅ **Auto-scaling**

**Combined Benefits:**
- 🆓 **100% free** to start
- 🚀 **Zero server management**
- 🌍 **Global performance**
- 🔐 **Professional features**

## 📋 **Prerequisites:**

1. **GitHub account** with your code
2. **Vercel account** (free)
3. **Supabase account** (free)
4. **Domain name** (optional)

## 🚀 **Step-by-Step Deployment:**

### **Step 1: Prepare Your Code**

1. **Ensure your code is on GitHub**
2. **Verify file structure:**
   ```
   your-project/
   ├── public/           # Public files
   ├── includes/         # PHP includes
   ├── sql/             # Database schemas
   ├── vercel.json      # Vercel config
   └── .env.example     # Environment template
   ```

### **Step 2: Set Up Supabase**

1. **Go to [supabase.com](https://supabase.com)**
2. **Create new project**
3. **Get connection details:**
   - Project URL
   - Anon key
   - Service role key
   - Database password

4. **Run PostgreSQL schema:**
   ```sql
   -- Copy content from sql/database_postgresql.sql
   -- Run in Supabase SQL Editor
   ```

### **Step 3: Configure Environment Variables**

1. **Create `.env` file:**
   ```env
   # Supabase Configuration
   SUPABASE_URL=your_project_url
   SUPABASE_ANON_KEY=your_anon_key
   SUPABASE_SERVICE_KEY=your_service_key
   
   # Database Configuration (PostgreSQL)
   DB_HOST=db.your_project_ref.supabase.co
   DB_NAME=postgres
   DB_USER=postgres
   DB_PASS=your_database_password
   DB_PORT=5432
   
   # Application URL (will be set by Vercel)
   APP_URL=https://your-vercel-domain.vercel.app
   
   # Security
   CSRF_SECRET=your_generated_secret
   SESSION_SECRET=your_generated_secret
   
   # Production Settings
   DEBUG=false
   LOG_LEVEL=error
   TIMEZONE=UTC
   ```

2. **Generate secure secrets:**
   ```bash
   # Generate CSRF secret
   openssl rand -hex 32
   
   # Generate session secret
   openssl rand -hex 32
   ```

### **Step 4: Update Database Connection**

1. **Rename `includes/db_postgresql.php` to `includes/db.php`**
2. **Or update existing `includes/db.php` to use PostgreSQL**

### **Step 5: Deploy to Vercel**

#### **Option A: Vercel CLI (Recommended)**

1. **Install Vercel CLI:**
   ```bash
   npm i -g vercel
   ```

2. **Login to Vercel:**
   ```bash
   vercel login
   ```

3. **Deploy:**
   ```bash
   vercel
   ```

4. **Follow prompts:**
   ```
   Set up and deploy? Y
   Which scope? [your-account]
   Link to existing project? N
   What's your project name? student-time-advisor
   In which directory is your code located? ./
   Want to override the settings? N
   ```

#### **Option B: GitHub Integration**

1. **Go to [vercel.com](https://vercel.com)**
2. **Click "New Project"**
3. **Import your GitHub repository**
4. **Configure settings:**
   - Framework Preset: `Other`
   - Root Directory: `./`
   - Build Command: Leave empty
   - Output Directory: Leave empty

### **Step 6: Configure Environment Variables in Vercel**

1. **Go to your project dashboard**
2. **Click "Settings" → "Environment Variables"**
3. **Add each variable from your `.env` file:**
   ```
   SUPABASE_URL=your_value
   SUPABASE_ANON_KEY=your_value
   SUPABASE_SERVICE_KEY=your_value
   DB_HOST=your_value
   DB_NAME=your_value
   DB_USER=your_value
   DB_PASS=your_value
   CSRF_SECRET=your_value
   SESSION_SECRET=your_value
   ```

### **Step 7: Test Your Deployment**

1. **Visit your Vercel URL**
2. **Test all functionality:**
   - User registration
   - User login
   - Task creation
   - Dashboard features

## 🔧 **Vercel Configuration Details:**

### **vercel.json Explained:**

```json
{
  "functions": {
    "public/*.php": {
      "runtime": "vercel-php@0.6.0"  // PHP runtime for all PHP files
    }
  },
  "routes": [
    {
      "src": "/",                    // Root URL
      "dest": "/public/index.php"    // Routes to index.php
    },
    {
      "src": "/login",               // /login URL
      "dest": "/public/login.php"    // Routes to login.php
    }
  ]
}
```

### **Key Features:**
- **Clean URLs:** `/login` instead of `/login.php`
- **PHP Runtime:** All PHP files use Vercel's PHP runtime
- **Asset Handling:** CSS/JS files served from `/assets/`
- **Fallback:** All other requests go to appropriate PHP files

## 🗄️ **Database Connection Issues:**

### **Common Problems:**

1. **Connection Failed:**
   ```php
   // Check your .env variables in Vercel
   // Ensure DB_HOST includes full Supabase URL
   DB_HOST=db.your_project_ref.supabase.co
   ```

2. **SSL Issues:**
   ```php
   // Supabase requires SSL
   // The connection string includes sslmode=require
   ```

3. **Authentication Failed:**
   ```php
   // Verify database password
   // Check if user exists in Supabase
   ```

### **Debug Database Connection:**
```php
// Add this to debug connection issues
try {
    $pdo = DB::conn();
    echo "Database connected successfully!";
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage();
}
```

## 🌐 **Custom Domain Setup:**

1. **In Vercel Dashboard:**
   - Go to "Settings" → "Domains"
   - Add your domain
   - Update DNS records

2. **Update Environment:**
   ```env
   APP_URL=https://yourdomain.com
   ```

3. **Redeploy:**
   ```bash
   vercel --prod
   ```

## 📊 **Monitoring & Analytics:**

### **Vercel Dashboard:**
- Deployment status
- Performance metrics
- Error logs
- Function execution

### **Supabase Dashboard:**
- Database performance
- API usage
- Authentication logs
- Real-time subscriptions

## 🚨 **Troubleshooting:**

### **Deployment Issues:**

1. **Build Failed:**
   - Check `vercel.json` syntax
   - Verify file paths
   - Check PHP version compatibility

2. **Runtime Errors:**
   - Check Vercel function logs
   - Verify environment variables
   - Test database connection

3. **404 Errors:**
   - Check route configuration
   - Verify file locations
   - Check `vercel.json` routes

### **Debug Commands:**
```bash
# Check Vercel status
vercel ls

# View deployment logs
vercel logs

# Redeploy
vercel --prod

# Check environment variables
vercel env ls
```

## 🎯 **Post-Deployment Checklist:**

- [ ] **Database connection** working
- [ ] **User registration** functional
- [ ] **User login** working
- [ ] **All pages** accessible
- [ ] **Assets loading** correctly
- [ ] **Environment variables** set
- [ ] **Custom domain** configured (if applicable)
- [ ] **SSL certificate** active
- [ ] **Performance** acceptable
- [ ] **Error monitoring** set up

## 💡 **Performance Tips:**

1. **Enable Vercel Analytics** for performance monitoring
2. **Use Supabase caching** for database queries
3. **Optimize images** and assets
4. **Enable compression** in Vercel
5. **Use CDN** for static assets

## 🔗 **Useful Links:**

- [Vercel Documentation](https://vercel.com/docs)
- [Vercel PHP Runtime](https://github.com/vercel/vercel-php)
- [Supabase Documentation](https://supabase.com/docs)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)

## 🎉 **Deployment Complete!**

**Your Student Time Management Advisor is now running on:**
- 🌐 **Vercel** - Free hosting with global CDN
- 🗄️ **Supabase** - Free PostgreSQL database
- 🔐 **Built-in authentication**
- 📱 **Real-time features**
- 🚀 **Auto-scaling**

**Access your app at:** `https://your-project.vercel.app`

**Next steps:**
1. Test all functionality
2. Set up custom domain
3. Configure monitoring
4. Optimize performance

---

**Ready to deploy? Follow these steps and get your app live on Vercel + Supabase today! 🚀**
