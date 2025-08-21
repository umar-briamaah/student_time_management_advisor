<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/password_utils.php';

$errors = [];
$success_message = '';
$token = $_GET['token'] ?? '';
$token_valid = false;
$user_id = null;

// Validate token
if ($token) {
    try {
        $pdo = DB::conn();
        $stmt = $pdo->prepare("
            SELECT pr.user_id, pr.expires_at, u.name, u.email 
            FROM password_resets pr 
            JOIN users u ON pr.user_id = u.id 
            WHERE pr.token = ? AND pr.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $reset_data = $stmt->fetch();
        
        if ($reset_data) {
            $token_valid = true;
            $user_id = $reset_data['user_id'];
        } else {
            $errors[] = 'Invalid or expired reset token. Please request a new password reset.';
        }
    } catch (Exception $e) {
        if (defined('DEBUG') && DEBUG) {
            error_log('Token validation error: ' . $e->getMessage());
        }
        $errors[] = 'An error occurred while validating the reset token.';
    }
} else {
    $errors[] = 'No reset token provided.';
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        
        // Validate password
        $user_data = ['id' => $user_id];
        $password_errors = PasswordValidator::validate($password, $password_confirm, $user_data);
        $errors = array_merge($errors, $password_errors);
        
        if (empty($errors)) {
            try {
                $pdo = DB::conn();
                
                // Update password
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$hash, $user_id]);
                
                // Delete used reset token
                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
                $stmt->execute([$token]);
                
                $success_message = 'Password has been reset successfully! You can now log in with your new password.';
                
                // Redirect to login after 3 seconds
                header("refresh:3;url=" . APP_URL . "/login.php");
                
            } catch (Exception $e) {
                if (defined('DEBUG') && DEBUG) {
                    error_log('Password reset error: ' . $e->getMessage());
                }
                $errors[] = 'Failed to reset password. Please try again.';
            }
        }
    }
}

include __DIR__ . '/../includes/layout/header.php';
?>

<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-green-50 via-blue-50 to-purple-50 py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    <div class="max-w-md w-full relative z-10">
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-2xl border border-white/20 p-8">
            <div class="text-center mb-8">
                <div class="mx-auto h-20 w-20 bg-gradient-to-br from-green-600 via-blue-600 to-purple-600 rounded-2xl flex items-center justify-center shadow-2xl transform hover:scale-105 transition-transform duration-300 mb-6">
                    <svg class="h-10 w-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Reset Password 🔑</h1>
                <p class="text-gray-600">Create a new secure password for your account</p>
            </div>

            <?php if ($success_message): ?>
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex">
                        <svg class="w-5 h-5 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <p class="text-green-800"><?php echo htmlspecialchars($success_message); ?></p>
                    </div>
                    <p class="text-sm text-green-700 mt-2">Redirecting to login page...</p>
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

            <?php if ($token_valid && !$success_message): ?>
                <form method="post" class="space-y-6" id="resetPasswordForm">
                    <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                    
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <div class="relative">
                            <input 
                                class="form-control pr-12" 
                                type="password" 
                                name="password" 
                                id="password"
                                placeholder="Enter your new password" 
                                required
                                autocomplete="new-password"
                                oninput="checkPasswordStrength(this.value)"
                            >
                            <button 
                                type="button" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center transition-colors duration-200 hover:bg-gray-100 rounded-r-md"
                                onclick="togglePasswordVisibility('password')"
                                title="Toggle password visibility"
                            >
                                <svg id="eyeIconPassword" class="w-5 h-5 text-gray-400 hover:text-gray-600 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <div class="mt-2" id="passwordStrength">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-2">
                                    <div id="strengthBar" class="h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                                </div>
                                <span id="strengthText" class="text-xs font-medium text-gray-500">Weak</span>
                            </div>
                            <div id="strengthFeedback" class="text-xs text-gray-600"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <div class="relative">
                            <input 
                                class="form-control pr-12" 
                                type="password" 
                                name="password_confirm" 
                                id="passwordConfirm"
                                placeholder="Confirm your new password" 
                                required
                                autocomplete="new-password"
                                oninput="checkPasswordMatch()"
                            >
                            <button 
                                type="button" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center transition-colors duration-200 hover:bg-gray-100 rounded-r-md"
                                onclick="togglePasswordVisibility('passwordConfirm')"
                                title="Toggle password visibility"
                            >
                                <svg id="eyeIconPasswordConfirm" class="w-5 h-5 text-gray-400 hover:text-gray-600 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                        <div id="passwordMatch" class="mt-1 text-xs"></div>
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-green-600 to-blue-600 text-white py-3 px-4 rounded-lg hover:from-green-700 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-300 font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed" 
                            id="submitBtn" 
                            disabled>
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Reset Password
                    </button>
                </form>
            <?php endif; ?>
            
            <div class="text-center space-y-3 mt-6">
                <p class="text-sm text-gray-600">
                    Remember your password? 
                    <a class="text-blue-600 hover:text-blue-700 transition-colors font-medium" href="<?php echo APP_URL; ?>/login.php">Sign in</a>
                </p>
                <p class="text-sm text-gray-600">
                    Need a new reset link? 
                    <a class="text-blue-600 hover:text-blue-700 transition-colors font-medium" href="<?php echo APP_URL; ?>/forgot_password.php">Request reset</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// Password strength checker
function checkPasswordStrength(password) {
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    const strengthFeedback = document.getElementById('strengthFeedback');
    const submitBtn = document.getElementById('submitBtn');
    
    let score = 0;
    let feedback = [];
    
    // Length check
    if (password.length >= 8) score += 1;
    if (password.length >= 12) score += 1;
    if (password.length >= 16) score += 1;
    
    // Character variety checks
    if (/[a-z]/.test(password)) score += 1;
    if (/[A-Z]/.test(password)) score += 1;
    if (/[0-9]/.test(password)) score += 1;
    if (/[^A-Za-z0-9]/.test(password)) score += 1;
    
    // Update strength bar
    const percentage = Math.min(100, (score / 7) * 100);
    strengthBar.style.width = percentage + '%';
    
    // Update strength text and color
    let strength = 'Very Weak';
    let color = 'bg-red-500';
    
    if (score >= 6) {
        strength = 'Very Strong';
        color = 'bg-green-500';
    } else if (score >= 4) {
        strength = 'Strong';
        color = 'bg-green-500';
    } else if (score >= 3) {
        strength = 'Good';
        color = 'bg-blue-500';
    } else if (score >= 2) {
        strength = 'Fair';
        color = 'bg-yellow-500';
    } else if (score >= 1) {
        strength = 'Weak';
        color = 'bg-orange-500';
    }
    
    strengthBar.className = `h-2 rounded-full transition-all duration-300 ${color}`;
    strengthText.textContent = strength;
    
    // Update feedback
    feedback = [];
    if (password.length < 8) feedback.push('Make it at least 8 characters long');
    if (!/[a-z]/.test(password)) feedback.push('Add lowercase letters');
    if (!/[A-Z]/.test(password)) feedback.push('Add uppercase letters');
    if (!/[0-9]/.test(password)) feedback.push('Add numbers');
    if (!/[^A-Za-z0-9]/.test(password)) feedback.push('Add special characters');
    
    strengthFeedback.innerHTML = feedback.map(f => `• ${f}`).join('<br>');
    
    // Check if form can be submitted
    checkFormValidity();
}

// Toggle password visibility
function togglePasswordVisibility(fieldId) {
    const passwordInput = document.getElementById(fieldId);
    const eyeIcon = document.getElementById(fieldId === 'password' ? 'eyeIconPassword' : 'eyeIconPasswordConfirm');
    
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

// Check password match
function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('passwordConfirm').value;
    const matchDiv = document.getElementById('passwordMatch');
    
    if (confirm === '') {
        matchDiv.textContent = '';
        matchDiv.className = 'mt-1 text-xs';
    } else if (password === confirm) {
        matchDiv.textContent = '✓ Passwords match';
        matchDiv.className = 'mt-1 text-xs text-green-600';
    } else {
        matchDiv.textContent = '✗ Passwords do not match';
        matchDiv.className = 'mt-1 text-xs text-red-600';
    }
    
    checkFormValidity();
}

// Check form validity
function checkFormValidity() {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('passwordConfirm').value;
    const submitBtn = document.getElementById('submitBtn');
    
    // Basic validation
    const isValid = password.length >= 8 && password === confirm && password !== '';
    
    submitBtn.disabled = !isValid;
    
    if (isValid) {
        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    } else {
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
    }
}

// Form submission handling
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('resetPasswordForm');
    const submitBtn = document.getElementById('submitBtn');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value.trim();
            const confirm = document.getElementById('passwordConfirm').value.trim();
            
            if (!password) {
                e.preventDefault();
                showNotification('Please enter a new password.', 'error');
                return;
            }
            
            if (password !== confirm) {
                e.preventDefault();
                showNotification('Passwords do not match.', 'error');
                return;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                showNotification('Password must be at least 8 characters long.', 'error');
                return;
            }
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Resetting Password...
            `;
        });
    }
    
    // Real-time validation
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('passwordConfirm');
    
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });
    }
    
    if (confirmInput) {
        confirmInput.addEventListener('input', function() {
            checkPasswordMatch();
        });
    }
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
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
