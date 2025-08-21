# 🚀 Supabase Deployment Guide

## 🌟 **What is Supabase?**

Supabase is an open-source alternative to Firebase that provides:

- **Database:** PostgreSQL (serverless)
- **Authentication:** Built-in user management
- **API:** Auto-generated REST and GraphQL APIs
- **Real-time:** Live subscriptions
- **Storage:** File storage
- **Hosting:** Serverless functions

## 💰 **Pricing:**

- **Free Tier:** $0/month (500MB database, 2GB bandwidth)
- **Pro:** $25/month (8GB database, 250GB bandwidth)
- **Team:** $599/month (100GB database, 1TB bandwidth)

## 📋 **Prerequisites:**

1. Supabase account (free)
2. Domain name (optional)
3. Your application code

## 🚀 **Step-by-Step Deployment:**

### **Step 1: Create Supabase Project**

1. **Go to [supabase.com](https://supabase.com)**
2. **Click "Start your project"**
3. **Sign in with GitHub**
4. **Click "New Project"**
5. **Choose organization**
6. **Enter project details:**
   - Name: `student-time-advisor`
   - Database Password: `your_secure_password`
   - Region: Choose closest to your users
7. **Click "Create new project"**

### **Step 2: Set Up Database**

1. **Go to SQL Editor in your project**
2. **Copy and paste the PostgreSQL schema:**

   ```sql
   -- Copy content from sql/database_postgresql.sql
   ```

3. **Click "Run" to execute the schema**

### **Step 3: Configure Environment Variables**

1. **Go to Settings → API in your project**
2. **Copy these values:**
   - Project URL
   - Anon (public) key
   - Service role (secret) key

3. **Create `.env` file:**

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
   
   # Application URL
   APP_URL=https://yourdomain.com
   
   # Security
   CSRF_SECRET=your_generated_secret
   SESSION_SECRET=your_generated_secret
   
   # Production Settings
   DEBUG=false
   LOG_LEVEL=error
   TIMEZONE=UTC
   ```

### **Step 4: Update Database Connection**

1. **Rename `includes/db_postgresql.php` to `includes/db.php`**
2. **Or update your existing `includes/db.php` to use PostgreSQL**

### **Step 5: Deploy Application**

#### **Option A: Deploy to Vercel (Recommended)**

1. **Install Vercel CLI:**

   ```bash
   npm i -g vercel
   ```

2. **Deploy:**

   ```bash
   vercel
   ```

3. **Follow prompts:**
   - Set up and deploy: `Y`
   - Which scope: Choose your account
   - Link to existing project: `N`
   - Project name: `student-time-advisor`
   - Directory: `./public`
   - Override settings: `N`

#### **Option B: Deploy to Netlify**

1. **Push code to GitHub**
2. **Connect Netlify to your repository**
3. **Build settings:**
   - Build command: Leave empty
   - Publish directory: `public`

#### **Option C: Deploy to Railway**

1. **Go to [railway.app](https://railway.app)**
2. **Connect GitHub repository**
3. **Deploy automatically**

### **Step 6: Configure Custom Domain (Optional)**

1. **In Vercel/Netlify:**
   - Go to Domains
   - Add your domain
   - Update DNS records

2. **Update `.env`:**

   ```env
   APP_URL=https://yourdomain.com
   ```

## 🔧 **Configuration Files:**

### **Update `includes/config.php`:**

```php
<?php
// Load .env
$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, '"\'');
        $_ENV[$key] = $value;
    }
}

// Supabase Configuration
define('SUPABASE_URL', $_ENV['SUPABASE_URL'] ?? '');
define('SUPABASE_ANON_KEY', $_ENV['SUPABASE_ANON_KEY'] ?? '');
define('SUPABASE_SERVICE_KEY', $_ENV['SUPABASE_SERVICE_KEY'] ?? '');

// Database Configuration (PostgreSQL)
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'postgres');
define('DB_USER', $_ENV['DB_USER'] ?? 'postgres');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_PORT', $_ENV['DB_PORT'] ?? '5432');

// Application URL
define('APP_URL', rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/'));

// Security
define('CSRF_SECRET', $_ENV['CSRF_SECRET'] ?? 'default_secret');
define('SESSION_SECRET', $_ENV['SESSION_SECRET'] ?? 'default_secret');

// Settings
define('DEBUG', $_ENV['DEBUG'] ?? 'true');
define('LOG_LEVEL', $_ENV['LOG_LEVEL'] ?? 'debug');
define('TIMEZONE', $_ENV['TIMEZONE'] ?? 'UTC');
?>
```

## 🗄️ **Database Differences (MySQL → PostgreSQL):**

| MySQL | PostgreSQL | Notes |
|-------|------------|-------|
| `AUTO_INCREMENT` | `SERIAL` | Auto-incrementing IDs |
| `INT` | `INTEGER` | Integer types |
| `VARCHAR` | `VARCHAR` | Same |
| `TEXT` | `TEXT` | Same |
| `TIMESTAMP` | `TIMESTAMP` | Same |
| `NOW()` | `CURRENT_TIMESTAMP` | Current timestamp |

## 🔒 **Security Features:**

### **Row Level Security (RLS):**

```sql
-- Enable RLS on users table
ALTER TABLE users ENABLE ROW LEVEL SECURITY;

-- Create policy for users to see only their data
CREATE POLICY "Users can view own data" ON users
    FOR SELECT USING (auth.uid() = id);

-- Create policy for users to update own data
CREATE POLICY "Users can update own data" ON users
    FOR UPDATE USING (auth.uid() = id);
```

### **API Security:**

- **Anon key:** Public access (limited)
- **Service key:** Admin access (keep secret)

## 📊 **Monitoring & Analytics:**

### **Supabase Dashboard:**

- Database performance
- API usage
- Authentication logs
- Storage usage

### **Custom Monitoring:**

```php
// Add to your application
function log_activity($user_id, $action, $details = '') {
    $pdo = DB::conn();
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, details, created_at) 
        VALUES (?, ?, ?, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$user_id, $action, $details]);
}
```

## 🚨 **Troubleshooting:**

### **Common Issues:**

1. **Connection Failed:**
   - Check database password
   - Verify host URL
   - Check SSL settings

2. **Authentication Issues:**
   - Verify API keys
   - Check RLS policies
   - Test with service key

3. **Performance Issues:**
   - Check database indexes
   - Monitor query performance
   - Use connection pooling

### **Debug Commands:**

```php
// Test database connection
$db_info = DB::getInfo();
var_dump($db_info);

// Test Supabase connection
$response = file_get_contents(SUPABASE_URL . '/rest/v1/');
var_dump($response);
```

## 🎯 **Next Steps After Deployment:**

1. **Test all functionality**
2. **Set up monitoring**
3. **Configure backups**
4. **Set up CI/CD**
5. **Performance optimization**

## 💡 **Benefits of Supabase:**

- ✅ **No server management**
- ✅ **Automatic scaling**
- ✅ **Built-in authentication**
- ✅ **Real-time features**
- ✅ **Free tier available**
- ✅ **PostgreSQL power**
- ✅ **Auto-generated APIs**

## 🔗 **Useful Links:**

- [Supabase Documentation](https://supabase.com/docs)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [Vercel Deployment](https://vercel.com/docs)
- [Netlify Deployment](https://docs.netlify.com/)

---

**Your Student Time Management Advisor will be running on Supabase with:**

- 🗄️ **PostgreSQL database**
- 🔐 **Built-in authentication**
- 🌐 **Serverless hosting**
- 📱 **Real-time updates**
- 🚀 **Auto-scaling**

**Ready to deploy? Follow the steps above and your app will be live on Supabase! 🎉**
