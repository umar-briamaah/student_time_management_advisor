<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/password_utils.php';

$errors = [];
$password_strength = 0;
$password_feedback = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) $errors[] = 'Invalid CSRF token.';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // Validation
    if (!$name) $errors[] = 'Name is required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required';
    
    // Enhanced password validation using utility class
    $user_data = ['name' => $name, 'email' => $email];
    $password_errors = PasswordValidator::validate($password, $password_confirm, $user_data);
    $errors = array_merge($errors, $password_errors);
    
    if (!$errors) {
        $pdo = DB::conn();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email already registered';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?,?,?)");
            $stmt->execute([$name, $email, $hash]);
            
            $user_id = $pdo->lastInsertId();
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            $_SESSION['user'] = ['id' => $user_id, 'name'=>$name, 'email'=>$email];
            
            // Send welcome email in background (non-blocking)
            try {
                if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                    require_once __DIR__ . '/../includes/mailer.php';
                    $emailSystem = new EmailSystem();
                    $emailSystem->sendWelcomeEmail($user_id);
                }
            } catch (Exception $e) {
                // Log error but don't prevent registration
                if (defined('DEBUG') && DEBUG) {
                    error_log('Failed to send welcome email: ' . $e->getMessage());
                }
            }
            
            header('Location: ' . APP_URL . '/dashboard.php');
            exit;
        }
    }
}



include __DIR__ . '/../includes/layout/header.php';
?>

<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-green-50 via-blue-50 to-purple-50 py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Background decorative elements -->
    <div class="absolute inset-0">
        <div class="absolute top-0 left-1/4 w-64 h-64 bg-green-200 rounded-full mix-blend-multiply filter blur-xl opacity-20"></div>
        <div class="absolute top-0 right-1/4 w-64 h-64 bg-blue-200 rounded-full mix-blend-multiply filter blur-xl opacity-20"></div>
        <div class="absolute bottom-0 left-1/3 w-64 h-64 bg-purple-200 rounded-full mix-blend-multiply filter blur-xl opacity-20"></div>
    </div>
    
    <div class="max-w-md w-full relative z-10">
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-2xl border border-white/20 p-8">
            <div class="text-center mb-8">
                <div class="mx-auto h-20 w-20 bg-gradient-to-br from-green-600 via-blue-600 to-purple-600 rounded-2xl flex items-center justify-center shadow-2xl transform hover:scale-105 transition-transform duration-300 mb-6">
                    <svg class="h-10 w-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Join Us! 🚀</h1>
                <p class="text-gray-600">Create your account and start managing your time effectively</p>
            </div>
    
    <?php if($errors): ?>
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded">
            <?php foreach($errors as $e) echo '<p class="text-red-700">'.htmlspecialchars($e).'</p>'; ?>
        </div>
    <?php endif; ?>
    
    <form method="post" class="space-y-4" id="registerForm">
        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
        
        <!-- Name Field -->
        <div class="form-group">
            <label class="form-label">Full Name</label>
            <input 
                class="form-control" 
                name="name" 
                placeholder="Enter your full name" 
                value="<?php echo htmlspecialchars($name ?? ''); ?>"
                required
                autocomplete="name"
            >
        </div>
        
        <!-- Email Field -->
        <div class="form-group">
            <label class="form-label">Email Address</label>
            <input 
                class="form-control" 
                type="email" 
                name="email" 
                placeholder="Enter your email" 
                value="<?php echo htmlspecialchars($email ?? ''); ?>"
                required
                autocomplete="email"
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
                    placeholder="Create a strong password" 
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
            
            <!-- Password Strength Indicator -->
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
        
        <!-- Password Confirmation Field -->
        <div class="form-group">
            <label class="form-label">Confirm Password</label>
            <div class="relative">
                <input 
                    class="form-control pr-12" 
                    type="password" 
                    name="password_confirm" 
                    id="passwordConfirm"
                    placeholder="Confirm your password" 
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
        
        <!-- Password Requirements -->
        <div class="bg-gray-50 p-3 rounded-lg">
            <h4 class="text-sm font-medium text-gray-700 mb-2">Password Requirements:</h4>
            <ul class="text-xs text-gray-600 space-y-1">
                <li class="flex items-center gap-2">
                    <span id="reqLength" class="w-2 h-2 rounded-full bg-gray-300"></span>
                    At least 8 characters
                </li>
                <li class="flex items-center gap-2">
                    <span id="reqLower" class="w-2 h-2 rounded-full bg-gray-300"></span>
                    Lowercase letters (a-z)
                </li>
                <li class="flex items-center gap-2">
                    <span id="reqUpper" class="w-2 h-2 rounded-full bg-gray-300"></span>
                    Uppercase letters (A-Z)
                </li>
                <li class="flex items-center gap-2">
                    <span id="reqNumber" class="w-2 h-2 rounded-full bg-gray-300"></span>
                    Numbers (0-9)
                </li>
                <li class="flex items-center gap-2">
                    <span id="reqSpecial" class="w-2 h-2 rounded-full bg-gray-300"></span>
                    Special characters (!@#$%^&*)
                </li>
            </ul>
        </div>
        
        <button type="submit" class="w-full bg-gradient-to-r from-green-600 to-blue-600 text-white py-3 px-4 rounded-lg hover:from-green-700 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-300 font-medium shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed" id="submitBtn" disabled>
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
            </svg>
            Create Account
        </button>
        
        <p class="text-sm text-gray-600 text-center">
            Already have an account? 
            <a class="text-blue-600 hover:text-blue-700 transition-colors" href="<?php echo APP_URL; ?>/login.php">Sign in</a>
        </p>
    </form>
        </div>
    </div>

<script>
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
    
    // Update requirement indicators
    updateRequirementIndicators(password);
    
    // Check if form can be submitted
    checkFormValidity();
}

function updateRequirementIndicators(password) {
    const reqs = {
        length: password.length >= 8,
        lower: /[a-z]/.test(password),
        upper: /[A-Z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[^A-Za-z0-9]/.test(password)
    };
    
    Object.keys(reqs).forEach(req => {
        const indicator = document.getElementById(`req${req.charAt(0).toUpperCase() + req.slice(1)}`);
        if (reqs[req]) {
            indicator.className = 'w-2 h-2 rounded-full bg-green-500';
        } else {
            indicator.className = 'w-2 h-2 rounded-full bg-gray-300';
        }
    });
}

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

function checkFormValidity() {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('passwordConfirm').value;
    const submitBtn = document.getElementById('submitBtn');
    
    // Basic validation
    const isValid = password.length >= 8 && password === confirm && password !== '';
    
    submitBtn.disabled = !isValid;
    
    if (isValid) {
        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        submitBtn.classList.add('hover:bg-blue-700');
    } else {
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        submitBtn.classList.remove('hover:bg-blue-700');
    }
}

// Initialize form validation
document.addEventListener('DOMContentLoaded', function() {
    checkFormValidity();
    
    // Add keyboard shortcut for toggling password visibility
    document.addEventListener('keydown', function(e) {
        // Ctrl+Shift+P to toggle password visibility
        if (e.ctrlKey && e.shiftKey && e.key === 'P') {
            e.preventDefault();
            const activeElement = document.activeElement;
            if (activeElement.id === 'password') {
                togglePasswordVisibility('password');
            } else if (activeElement.id === 'passwordConfirm') {
                togglePasswordVisibility('passwordConfirm');
            }
        }
    });
    
    // Add focus effects for password fields
    const passwordFields = ['password', 'passwordConfirm'];
    passwordFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        const container = field.parentElement;
        
        field.addEventListener('focus', function() {
            container.classList.add('ring-2', 'ring-blue-500', 'ring-opacity-50');
        });
        
        field.addEventListener('blur', function() {
            container.classList.remove('ring-2', 'ring-blue-500', 'ring-opacity-50');
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>