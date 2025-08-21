<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
$user = current_user();

$message = '';
$error = '';

// Check PHPMailer availability after autoloader is included
$phpmailer_available = class_exists('PHPMailer\PHPMailer\PHPMailer');
$mailer_file_exists = file_exists(__DIR__ . '/../includes/mailer.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        try {
            require_once __DIR__ . '/../includes/mailer.php';
            $emailSystem = new EmailSystem();
            
            $test_type = $_POST['test_type'] ?? 'welcome';
            
            switch ($test_type) {
                case 'welcome':
                    $result = $emailSystem->sendWelcomeEmail($user['id']);
                    if ($result) {
                        $message = 'Welcome email sent successfully! Check your inbox.';
                    } else {
                        $error = 'Failed to send welcome email.';
                    }
                    break;
                    
                case 'reminder':
                    // Get a sample task
                    $pdo = DB::conn();
                    $stmt = $pdo->prepare("SELECT id FROM tasks WHERE user_id = ? LIMIT 1");
                    $stmt->execute([$user['id']]);
                    $task = $stmt->fetch();
                    
                    if ($task) {
                        $result = $emailSystem->sendTaskReminder($user['id'], $task['id']);
                        if ($result) {
                            $message = 'Task reminder email sent successfully! Check your inbox.';
                        } else {
                            $error = 'Failed to send task reminder email.';
                        }
                    } else {
                        $error = 'No tasks found. Create a task first to test reminders.';
                    }
                    break;
                    
                case 'daily_motivation':
                    $result = $emailSystem->sendDailyMotivation($user['id']);
                    if ($result) {
                        $message = 'Daily motivation email sent successfully! Check your inbox.';
                    } else {
                        $error = 'Failed to send daily motivation email.';
                    }
                    break;
                    
                default:
                    $error = 'Invalid test type selected.';
            }
            
        } catch (Exception $e) {
            $error = 'Email system error: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/layout/header.php';
?>

<div class="min-h-screen bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">📧 Email System Test</h1>
            <p class="text-lg text-gray-600">Test the PHPMailer integration and email functionality</p>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span class="text-green-800 font-medium"><?php echo htmlspecialchars($message); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    <span class="text-red-800 font-medium"><?php echo htmlspecialchars($error); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Email Test Form -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Test Email Functions</h2>
            
            <form method="post" class="space-y-4">
                <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Select Email Type</label>
                    <select name="test_type" class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                        <option value="welcome">🎓 Welcome Email</option>
                        <option value="reminder">⏰ Task Reminder</option>
                        <option value="daily_motivation">🌟 Daily Motivation</option>
                    </select>
                </div>
                
                <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-xl hover:from-blue-700 hover:to-purple-700 transition-all duration-300 font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                    📤 Send Test Email
                </button>
            </form>
        </div>

        <!-- Email System Information -->
        <div class="grid md:grid-cols-2 gap-6">
            <!-- Current User Info -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">👤 Current User</h3>
                <div class="space-y-2">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>User ID:</strong> <?php echo $user['id']; ?></p>
                </div>
            </div>

            <!-- Email System Status -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">🔧 System Status</h3>
                <div class="space-y-2">
                    <p><strong>PHPMailer:</strong> 
                        <span class="<?php echo $phpmailer_available ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $phpmailer_available ? '✅ Available' : '❌ Not Found'; ?>
                        </span>
                    </p>
                    <p><strong>Mailer File:</strong> 
                        <span class="<?php echo $mailer_file_exists ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $mailer_file_exists ? '✅ Exists' : '❌ Missing'; ?>
                        </span>
                    </p>
                    <p><strong>Database:</strong> 
                        <span class="text-green-600">✅ Connected</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Email Templates Preview -->
        <div class="bg-white rounded-xl shadow-lg p-6 mt-8">
            <h3 class="text-2xl font-semibold text-gray-800 mb-4">📋 Available Email Templates</h3>
            
            <div class="grid md:grid-cols-3 gap-4">
                <div class="border border-gray-200 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-800 mb-2">🎓 Welcome Email</h4>
                    <p class="text-sm text-gray-600 mb-3">Sent to new users upon registration</p>
                    <div class="text-xs text-gray-500">
                        <strong>Features:</strong> Welcome message, getting started guide, quick tips
                    </div>
                </div>
                
                <div class="border border-gray-200 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-800 mb-2">⏰ Task Reminder</h4>
                    <p class="text-sm text-gray-600 mb-3">Sent for upcoming task deadlines</p>
                    <div class="text-xs text-gray-500">
                        <strong>Features:</strong> Task details, due date, helpful tips
                    </div>
                </div>
                
                <div class="border border-gray-200 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-800 mb-2">🌟 Daily Motivation</h4>
                    <p class="text-sm text-gray-600 mb-3">Daily inspirational content</p>
                    <div class="text-xs text-gray-500">
                        <strong>Features:</strong> Motivational quotes, action steps, daily tips
                    </div>
                </div>
            </div>
        </div>

        <!-- Setup Instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mt-8">
            <h3 class="text-xl font-semibold text-blue-800 mb-4">⚙️ Setup Instructions</h3>
            
            <div class="space-y-3 text-blue-700">
                <p><strong>1. PHPMailer Installation:</strong></p>
                <ul class="list-disc list-inside ml-4 space-y-1 text-sm">
                    <li>Install via Composer: <code class="bg-blue-100 px-2 py-1 rounded">composer require phpmailer/phpmailer</code></li>
                    <li>Or download manually from GitHub and place in project directory</li>
                </ul>
                
                <p><strong>2. Email Configuration:</strong></p>
                <ul class="list-disc list-inside ml-4 space-y-1 text-sm">
                    <li>Update SMTP settings in <code class="bg-blue-100 px-2 py-1 rounded">includes/mailer.php</code></li>
                    <li>For Gmail: Use App Password instead of regular password</li>
                    <li>Enable 2FA and generate App Password in Google Account settings</li>
                </ul>
                
                <p><strong>3. Test the System:</strong></p>
                <ul class="list-disc list-inside ml-4 space-y-1 text-sm">
                    <li>Use the form above to send test emails</li>
                    <li>Check your email inbox for test messages</li>
                    <li>Review error logs if emails fail to send</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
