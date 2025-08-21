<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) $errors[] = 'Invalid CSRF token.';
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!$email) $errors[] = 'Email is required';
    if (!$password) $errors[] = 'Password is required';
    
    if (!$errors) {
        $pdo = DB::conn();
        $stmt = $pdo->prepare("SELECT id, name, email, password_hash FROM users WHERE email=?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user'] = ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email']];
            header('Location: ' . APP_URL . '/dashboard.php');
            exit;
        } else {
            $errors[] = 'Invalid email or password';
        }
    }
}

include __DIR__ . '/../includes/layout/header.php';
?>

<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Background decorative elements -->
    <div class="absolute inset-0">
        <div class="absolute top-0 left-1/4 w-64 h-64 bg-blue-200 rounded-full mix-blend-multiply filter blur-xl opacity-20"></div>
        <div class="absolute top-0 right-1/4 w-64 h-64 bg-purple-200 rounded-full mix-blend-multiply filter blur-xl opacity-20"></div>
        <div class="absolute bottom-0 left-1/3 w-64 h-64 bg-pink-200 rounded-full mix-blend-multiply filter blur-xl opacity-20"></div>
    </div>
    
    <div class="max-w-md w-full relative z-10">
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-2xl border border-white/20 p-8">
            <div class="text-center mb-8">
                <div class="mx-auto h-20 w-20 bg-gradient-to-br from-blue-600 via-purple-600 to-pink-600 rounded-2xl flex items-center justify-center shadow-2xl transform hover:scale-105 transition-transform duration-300 mb-6">
                    <svg class="h-10 w-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Welcome back! 👋</h1>
                <p class="text-gray-600">Sign in to continue your productivity journey</p>
            </div>
    
    <?php if($errors): ?>
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded">
            <?php foreach($errors as $e) echo '<p class="text-red-700">'.htmlspecialchars($e).'</p>'; ?>
        </div>
    <?php endif; ?>
    
    <form method="post" class="space-y-4">
        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
        
        <!-- Email Field -->
        <div class="form-group">
            <label class="form-label">Email Address</label>
            <input 
                class="form-control" 
                type="email" 
                name="email" 
                placeholder="Enter your email" 
                value="<?php echo htmlspecialchars($email); ?>"
                required
                autocomplete="email"
                autofocus
            >
        </div>
        
        <!-- Password Field -->
        <div class="form-group">
            <label class="form-label">Password</label>
            <div class="relative">
                <input 
                    class="form-control pr-12" 
                    type="password" 
                    name="password" 
                    id="password"
                    placeholder="Enter your password" 
                    required
                    autocomplete="current-password"
                >
                <button 
                    type="button" 
                    class="absolute inset-y-0 right-0 pr-3 flex items-center"
                    onclick="togglePasswordVisibility()"
                    title="Toggle password visibility"
                >
                    <svg id="eyeIcon" class="w-5 h-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between">
            <label class="flex items-center">
                <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                <span class="ml-2 text-sm text-gray-600">Remember me</span>
            </label>
            <a href="#" class="text-sm text-blue-600 hover:text-blue-700">Forgot password?</a>
        </div>
        
        <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white py-3 px-4 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-300 font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 5v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
            </svg>
            Sign In
        </button>
        
        <div class="text-center">
            <p class="text-sm text-gray-600">
                Don't have an account? 
                <a class="text-blue-600 hover:text-blue-700 font-medium transition-colors" href="<?php echo APP_URL; ?>/register.php">Sign up</a>
            </p>
        </div>
    </form>
    
    <!-- Demo Account Info -->
    <div class="mt-6 p-4 bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-200 rounded-lg">
        <h3 class="text-sm font-medium text-blue-800 mb-2">Demo Account</h3>
        <p class="text-xs text-blue-700 mb-2">You can test the system with these credentials:</p>
        <div class="text-xs text-blue-600 space-y-1">
            <div><strong>Email:</strong> demo@example.com</div>
            <div><strong>Password:</strong> password123</div>
        </div>
    </div>
</div>

<script>
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
        `;
        eyeIcon.title = 'Hide password';
    } else {
        passwordInput.type = 'password';
        eyeIcon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
        `;
        eyeIcon.title = 'Show password';
    }
}

// Add some nice animations
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const inputs = form.querySelectorAll('input[type="email"], input[type="password"]');
    
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('ring-2', 'ring-blue-500', 'ring-opacity-50');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('ring-2', 'ring-blue-500', 'ring-opacity-50');
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>