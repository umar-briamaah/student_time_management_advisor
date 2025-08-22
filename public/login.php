<?php
/**
 * Login page for Student Time Management Advisor
 */

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set security headers
set_security_headers();

// Check if user is already logged in
if (is_logged_in()) {
    log_user_activity('login_redirect', ['reason' => 'already_authenticated']);
    header('Location: /dashboard.php');
    exit();
}

// Initialize variables
$error_message = '';
$success_message = '';
$email = '';
$errors = [];

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check rate limiting
    if (!check_rate_limit('login_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 5, 300)) {
        $error_message = 'Too many login attempts. Please wait 5 minutes before trying again.';
        $errors[] = $error_message;
        log_security_event('login_rate_limit_exceeded', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // Validate CSRF token
        if (!verify_csrf($_POST['csrf'] ?? '')) {
            $error_message = 'Invalid request. Please try again.';
            $errors[] = $error_message;
            log_security_event('csrf_validation_failed', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'email' => $email
            ]);
        } elseif (empty($email) || empty($password)) {
            $error_message = 'Please enter both email and password.';
            $errors[] = $error_message;
            log_message('warning', 'Login attempt with missing credentials', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'email' => $email
            ]);
        } else {
            try {
                $pdo = DB::conn();
                
                // Get user by email
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND active = 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['login_time'] = time();
                    
                    // Set remember me cookie if requested
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        $expires = time() + (30 * 24 * 60 * 60); // 30 days
                        
                        // Store token in database
                        $stmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                        $stmt->execute([$user['id'], $token, date('Y-m-d H:i:s', $expires)]);
                        
                        setcookie('remember_token', $token, $expires, '/', '', isset($_SERVER['HTTPS']), true);
                    }
                    
                    log_user_activity('login_successful', [
                        'user_id' => $user['id'],
                        'email' => $user['email'],
                        'remember_me' => $remember,
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                    ]);
            
                    header('Location: /dashboard.php');
                    exit();
                    
                } else {
                    // Login failed
                    $error_message = 'Invalid email or password.';
                    $errors[] = $error_message;
                    log_message('warning', 'Failed login attempt', [
                        'email' => $email,
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);
                }
                
            } catch (Exception $e) {
                log_message('error', 'Database error during login', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                $error_message = 'An error occurred. Please try again later.';
                $errors[] = $error_message;
            }
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
    
    <form method="post" class="space-y-4" id="loginForm">
        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
        
        <!-- Dynamic Error Display Area -->
        <div id="form-error" class="hidden"></div>
        
        <!-- Email Field -->
        <div class="form-group">
            <label for="email" class="form-label">Email Address</label>
            <input 
                id="email"
                class="form-control" 
                type="email" 
                name="email" 
                placeholder="Enter your email" 
                value="<?php echo htmlspecialchars($email); ?>"
                required
                autocomplete="email"
                autofocus
            >
            <!-- Email feedback will be inserted here by JavaScript -->
        </div>
        
        <!-- Password Field -->
        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <div class="relative">
                <input 
                    id="password"
                    class="form-control pr-12" 
                    type="password" 
                    name="password" 
                    placeholder="Enter your password" 
                    required
                    autocomplete="current-password"
                >
                <button 
                    type="button" 
                    class="absolute inset-y-0 right-0 pr-3 flex items-center"
                    onclick="togglePasswordVisibility('password')"
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
            <label for="remember" class="flex items-center">
                <input id="remember" type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                <span class="ml-2 text-sm text-gray-600">Remember me</span>
            </label>
            <!-- <a href="#" class="text-sm text-blue-600 hover:text-blue-700">Forgot password?</a> -->
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
            <p class="text-sm text-gray-600 text-center">
                <a class="text-blue-600 hover:text-blue-700 font-medium transition-colors" href="<?php echo APP_URL; ?>/forgot_password.php">Forgot your password?</a>
            </p>
        </div>
    </form>
    

</div>

<script>
// Email validation and existence checking
let emailCheckTimeout;
let isCheckingEmail = false;

function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function checkEmailExistence(email) {
    if (!email || !validateEmail(email)) {
        hideEmailFeedback();
        return;
    }

    // Clear previous timeout
    clearTimeout(emailCheckTimeout);
    
    // Set new timeout to avoid too many requests
    emailCheckTimeout = setTimeout(() => {
        performEmailCheck(email);
    }, 500);
}

function performEmailCheck(email) {
    if (isCheckingEmail) return;
    
    isCheckingEmail = true;
    showEmailChecking();
    
    // Create form data for AJAX request
    const formData = new FormData();
    formData.append('email', email);
    formData.append('action', 'check_email');
    formData.append('csrf', document.querySelector('input[name="csrf"]').value);
    
    fetch('<?php echo APP_URL; ?>/check_email.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        isCheckingEmail = false;
        if (data.exists) {
            showEmailExists();
        } else {
            showEmailNotRegistered();
        }
    })
    .catch(error => {
        isCheckingEmail = false;
        console.error('Error checking email:', error);
        hideEmailFeedback();
    });
}

function showEmailChecking() {
    const emailInput = document.getElementById('email');
    const feedbackDiv = getOrCreateFeedbackDiv();
    
    feedbackDiv.innerHTML = `
        <div class="feedback-message info">
            <svg class="spinner text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Checking email...</span>
        </div>
    `;
    
    emailInput.classList.add('checking');
    emailInput.classList.remove('error', 'success');
}

function showEmailExists() {
    const emailInput = document.getElementById('email');
    const feedbackDiv = getOrCreateFeedbackDiv();
    
    feedbackDiv.innerHTML = `
        <div class="feedback-message success">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>Email is registered ✓</span>
        </div>
    `;
    
    emailInput.classList.add('success');
    emailInput.classList.remove('error', 'checking');
}

function showEmailNotRegistered() {
    const emailInput = document.getElementById('email');
    const feedbackDiv = getOrCreateFeedbackDiv();
    
    feedbackDiv.innerHTML = `
        <div class="feedback-message error">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>Email not registered. <a href="<?php echo APP_URL; ?>/register.php" class="underline hover:no-underline font-medium">Sign up here</a></span>
        </div>
    `;
    
    emailInput.classList.add('error');
    emailInput.classList.remove('success', 'checking');
}

function hideEmailFeedback() {
    const emailInput = document.getElementById('email');
    const feedbackDiv = document.getElementById('email-feedback');
    
    if (feedbackDiv) {
        feedbackDiv.remove();
    }
    
    emailInput.classList.remove('error', 'success', 'checking');
}

function getOrCreateFeedbackDiv() {
    let feedbackDiv = document.getElementById('email-feedback');
    if (!feedbackDiv) {
        feedbackDiv = document.createElement('div');
        feedbackDiv.id = 'email-feedback';
        const emailInput = document.getElementById('email');
        emailInput.parentNode.appendChild(feedbackDiv);
    }
    return feedbackDiv;
}

function togglePasswordVisibility(fieldId) {
    const passwordInput = document.getElementById(fieldId);
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

// Enhanced form validation
function validateForm() {
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const submitBtn = document.querySelector('button[type="submit"]');
    
    if (!email || !password) {
        showFormError('Please fill in all required fields');
        return false;
    }
    
    if (!validateEmail(email)) {
        showFormError('Please enter a valid email address');
        return false;
    }
    
    // Check if email exists before allowing form submission
    if (document.getElementById('email-feedback') && 
        document.getElementById('email-feedback').textContent.includes('not registered')) {
        showFormError('Please use a registered email address or sign up first');
        return false;
    }
    
    hideFormError();
    submitBtn.disabled = false;
    return true;
}

function showFormError(message) {
    let errorDiv = document.getElementById('form-error');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.id = 'form-error';
        errorDiv.className = 'mb-4 p-3 bg-red-50 border border-red-200 rounded text-red-700';
        const form = document.querySelector('form');
        form.insertBefore(errorDiv, form.firstChild);
    }
    errorDiv.textContent = message;
    errorDiv.classList.remove('hidden'); // Show the error div
}

function hideFormError() {
    const errorDiv = document.getElementById('form-error');
    if (errorDiv) {
        errorDiv.classList.add('hidden'); // Hide the error div
    }
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const emailInput = document.getElementById('email');
    const inputs = form.querySelectorAll('input[type="email"], input[type="password"]');
    
    // Email validation on input
    emailInput.addEventListener('input', function() {
        const email = this.value.trim();
        if (email) {
            checkEmailExistence(email);
        } else {
            hideEmailFeedback();
        }
    });
    
    // Email validation on blur
    emailInput.addEventListener('blur', function() {
        const email = this.value.trim();
        if (email && !validateEmail(email)) {
            showFormError('Please enter a valid email address');
        }
    });
    
    // Form submission validation
    form.addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            return false;
        }
    });
    
    // Input focus effects
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('ring-2', 'ring-blue-500', 'ring-opacity-50');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('ring-2', 'ring-blue-500', 'ring-opacity-50');
        });
    });
    
    // Real-time form validation
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('border-red-500');
                this.classList.add('border-gray-200');
            }
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>