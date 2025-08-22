<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $email = trim($_POST['email'] ?? '');
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            try {
                $pdo = DB::conn();
                
                // Check if user exists
                $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Generate reset token
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Store reset token in database
                    $stmt = $pdo->prepare("
                        INSERT INTO password_reset_tokens (user_id, token, expires_at) 
                        VALUES (?, ?, ?) 
                        ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)
                    ");
                    $stmt->execute([$user['id'], $token, $expires]);
                    
                    // Send reset email
                    try {
                        require_once __DIR__ . '/../includes/mailer.php';
                        $emailSystem = new EmailSystem();
                        $reset_link = APP_URL . '/reset_password.php?token=' . $token;
                        
                        $emailSystem->sendPasswordResetEmail($user['id'], $reset_link);
                        $success_message = 'Password reset instructions have been sent to your email.';
                    } catch (Exception $e) {
                        if (defined('DEBUG') && DEBUG) {
                            error_log('Failed to send password reset email: ' . $e->getMessage());
                        }
                        $errors[] = 'Failed to send reset email. Please try again.';
                    }
                } else {
                    // Don't reveal if email exists or not for security
                    $success_message = 'If an account with that email exists, password reset instructions have been sent.';
                }
            } catch (Exception $e) {
                if (defined('DEBUG') && DEBUG) {
                    error_log('Password reset error: ' . $e->getMessage());
                }
                $errors[] = 'An error occurred. Please try again.';
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
        <div class="absolute top-0 right-1/4 w-64 h-64 bg-indigo-200 rounded-full mix-blend-multiply filter blur-xl opacity-20"></div>
        <div class="absolute bottom-0 left-1/3 w-64 h-64 bg-purple-200 rounded-full mix-blend-multiply filter blur-xl opacity-20"></div>
    </div>
    
    <div class="max-w-md w-full relative z-10">
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-2xl border border-white/20 p-8">
            <div class="text-center mb-8">
                <div class="mx-auto h-20 w-20 bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-600 rounded-2xl flex items-center justify-center shadow-2xl transform hover:scale-105 transition-transform duration-300 mb-6">
                    <svg class="h-10 w-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Forgot Password? 🔐</h1>
                <p class="text-gray-600">Enter your email to receive password reset instructions</p>
            </div>

            <?php if ($success_message): ?>
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex">
                        <svg class="w-5 h-5 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <p class="text-green-800"><?php echo htmlspecialchars($success_message); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <?php foreach($errors as $error): ?>
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-red-800"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-6" id="forgotPasswordForm">
                <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="relative">
                        <input 
                            class="form-control pl-10" 
                            type="email" 
                            name="email" 
                            id="email"
                            placeholder="Enter your email address" 
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            required
                            autocomplete="email"
                            oninput="validateEmail(this.value)"
                        >
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m6.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                            </svg>
                        </div>
                    </div>
                    <div id="emailValidation" class="mt-2 text-sm"></div>
                </div>
                
                <button type="submit" 
                        class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-3 px-4 rounded-lg hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-300 font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed" 
                        id="submitBtn" 
                        disabled>
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    Send Reset Instructions
                </button>
                
                <div class="text-center space-y-3">
                    <p class="text-sm text-gray-600">
                        Remember your password? 
                        <a class="text-blue-600 hover:text-blue-700 transition-colors font-medium" href="<?php echo APP_URL; ?>/login.php">Sign in</a>
                    </p>
                    <p class="text-sm text-gray-600">
                        Don't have an account? 
                        <a class="text-blue-600 hover:text-blue-700 transition-colors font-medium" href="<?php echo APP_URL; ?>/register.php">Create one</a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Email validation
function validateEmail(email) {
    const emailValidation = document.getElementById('emailValidation');
    const submitBtn = document.getElementById('submitBtn');
    
    if (!email) {
        emailValidation.innerHTML = '';
        submitBtn.disabled = true;
        return;
    }
    
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const isValid = emailRegex.test(email);
    
    if (isValid) {
        emailValidation.innerHTML = '<span class="text-green-600">✓ Valid email address</span>';
        emailValidation.className = 'mt-2 text-sm';
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    } else {
        emailValidation.innerHTML = '<span class="text-red-600">✗ Please enter a valid email address</span>';
        emailValidation.className = 'mt-2 text-sm';
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
    }
}

// Form submission handling
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('forgotPasswordForm');
    const submitBtn = document.getElementById('submitBtn');
    
    form.addEventListener('submit', function(e) {
        const email = document.getElementById('email').value.trim();
        
        if (!email) {
            e.preventDefault();
            showNotification('Please enter your email address.', 'error');
            return;
        }
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Sending...
        `;
        
        // Re-enable after a delay (in case of errors)
        setTimeout(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = `
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                Send Reset Instructions
            `;
        }, 10000);
    });
    
    // Real-time validation
    const emailInput = document.getElementById('email');
    emailInput.addEventListener('input', function() {
        validateEmail(this.value);
    });
    
    // Focus effects
    emailInput.addEventListener('focus', function() {
        this.parentElement.classList.add('ring-2', 'ring-blue-500', 'ring-opacity-50');
    });
    
    emailInput.addEventListener('blur', function() {
        this.parentElement.classList.remove('ring-2', 'ring-blue-500', 'ring-opacity-50');
    });
});

// Notification system
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 max-w-sm ${
        type === 'success' ? 'bg-green-500 text-white' :
        type === 'error' ? 'bg-red-500 text-white' :
        'bg-blue-500 text-white'
    }`;
    
    notification.innerHTML = `
        <div class="flex items-center">
            <span class="mr-2">${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</span>
            <span>${message}</span>
        </div>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        notification.style.transition = 'opacity 0.5s ease-out';
        notification.style.opacity = '0';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 500);
    }, 5000);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+Enter to submit form
    if (e.ctrlKey && e.key === 'Enter') {
        e.preventDefault();
        const submitBtn = document.getElementById('submitBtn');
        if (!submitBtn.disabled) {
            submitBtn.click();
        }
    }
    
    // Escape to clear form
    if (e.key === 'Escape') {
        document.getElementById('email').value = '';
        validateEmail('');
    }
});
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
