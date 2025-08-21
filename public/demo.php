<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure APP_URL is defined
if (!defined('APP_URL')) {
    define('APP_URL', 'http://localhost/student-time-advisor-php/public');
}

include __DIR__ . '/../includes/layout/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="text-center animate-on-scroll">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Modern UI Components Demo</h1>
        <p class="text-gray-600">Showcasing cutting-edge UX design patterns and modern interactions</p>
        <div class="mt-4">
            <button class="btn btn-primary" onclick="window.staApp.setLoading(true); setTimeout(() => window.staApp.setLoading(false), 2000);">
                Test Loading State
            </button>
        </div>
    </div>

    <!-- Buttons Section -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-xl font-semibold text-gray-800">Enhanced Button Styles</h2>
        </div>
        <div class="card-body">
            <div class="flex flex-wrap gap-4">
                <button class="btn btn-primary">Primary Button</button>
                <button class="btn btn-secondary">Secondary Button</button>
                <button class="btn btn-success">Success Button</button>
                <button class="btn btn-danger">Danger Button</button>
                <button class="btn btn-primary btn-sm">Small Button</button>
                <button class="btn btn-primary btn-lg">Large Button</button>
            </div>
        </div>
    </div>

    <!-- Forms Section -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-xl font-semibold text-gray-800">Enhanced Form Elements</h2>
        </div>
        <div class="card-body">
            <form class="space-y-4">
                <div class="form-group">
                    <label class="form-label">Text Input</label>
                    <input type="text" class="form-input" placeholder="Enter some text">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email Input</label>
                    <input type="email" class="form-input" placeholder="Enter your email">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Textarea</label>
                    <textarea class="form-input" rows="3" placeholder="Enter a description"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Select</label>
                    <select class="form-input">
                        <option>Option 1</option>
                        <option>Option 2</option>
                        <option>Option 3</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Submit Form</button>
            </form>
        </div>
    </div>

    <!-- Badges Section -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-xl font-semibold text-gray-800">Enhanced Badges</h2>
        </div>
        <div class="card-body">
            <div class="flex flex-wrap gap-2">
                <span class="badge badge-primary">Primary</span>
                <span class="badge badge-success">Success</span>
                <span class="badge badge-warning">Warning</span>
                <span class="badge badge-danger">Danger</span>
                <span class="badge badge-info">Info</span>
            </div>
        </div>
    </div>

    <!-- Progress Bars Section -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-xl font-semibold text-gray-800">Enhanced Progress Bars</h2>
        </div>
        <div class="card-body space-y-4">
            <div>
                <div class="flex justify-between text-sm mb-2">
                    <span>Basic Progress</span>
                    <span>75%</span>
                </div>
                <div class="progress">
                    <div class="progress-bar" style="width: 75%"></div>
                </div>
            </div>
            
            <div>
                <div class="flex justify-between text-sm mb-2">
                    <span>Task Completion</span>
                    <span>45%</span>
                </div>
                <div class="progress">
                    <div class="progress-bar" style="width: 45%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts Section -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-xl font-semibold text-gray-800">Enhanced Alerts</h2>
        </div>
        <div class="card-body space-y-4">
            <div class="alert alert-success">
                <span>✅</span>
                <span>This is a success message with some additional text.</span>
            </div>
            
            <div class="alert alert-warning">
                <span>⚠️</span>
                <span>This is a warning message that requires attention.</span>
            </div>
            
            <div class="alert alert-danger">
                <span>❌</span>
                <span>This is an error message indicating a problem.</span>
            </div>
            
            <div class="alert alert-info">
                <span>ℹ️</span>
                <span>This is an informational message for the user.</span>
            </div>
        </div>
    </div>

    <!-- Tooltips Section -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-xl font-semibold text-gray-800">Enhanced Tooltips</h2>
        </div>
        <div class="card-body">
            <div class="flex flex-wrap gap-4">
                <button class="btn btn-primary tooltip" data-tooltip="This is a helpful tooltip!">
                    Hover for Tooltip
                </button>
                
                <span class="tooltip" data-tooltip="Another useful tooltip">
                    <span class="text-blue-600 cursor-help underline">Hover here too</span>
                </span>
            </div>
        </div>
    </div>

    <!-- Tables Section -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-xl font-semibold text-gray-800">Enhanced Tables</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Task</th>
                            <th>Category</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Complete Project</td>
                            <td>Assignment</td>
                            <td>Dec 15, 2024</td>
                            <td><span class="badge badge-warning">Pending</span></td>
                        </tr>
                        <tr>
                            <td>Study for Exam</td>
                            <td>Exam</td>
                            <td>Dec 20, 2024</td>
                            <td><span class="badge badge-danger">Overdue</span></td>
                        </tr>
                        <tr>
                            <td>Lab Report</td>
                            <td>Lab</td>
                            <td>Dec 18, 2024</td>
                            <td><span class="badge badge-success">Completed</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modals Section -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-xl font-semibold text-gray-800">Enhanced Modals</h2>
        </div>
        <div class="card-body">
            <div class="flex gap-4">
                <button class="btn btn-primary" data-modal="demoModal1">
                    Open Modal 1
                </button>
                <button class="btn btn-secondary" data-modal="demoModal2">
                    Open Modal 2
                </button>
            </div>
        </div>
    </div>

    <!-- Animations Section -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-xl font-semibold text-gray-800">Enhanced Animations</h2>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="animate-on-scroll p-4 bg-blue-100 rounded-lg text-center">
                    <h3 class="font-semibold">Fade In</h3>
                    <p class="text-sm text-gray-600">This element animates when scrolled into view</p>
                </div>
                
                <div class="animate-on-scroll p-4 bg-green-100 rounded-lg text-center">
                    <h3 class="font-semibold">Slide In</h3>
                    <p class="text-sm text-gray-600">Another animated element</p>
                </div>
                
                <div class="animate-on-scroll p-4 bg-purple-100 rounded-lg text-center">
                    <h3 class="font-semibold">Bounce In</h3>
                    <p class="text-sm text-gray-600">Yet another animated element</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Section -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-xl font-semibold text-gray-800">Enhanced Search</h2>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Search Tasks</label>
                <input type="text" class="form-input search-input" 
                       placeholder="Type to search..." 
                       data-search-target=".searchable-item">
            </div>
            
            <div class="mt-4 space-y-2">
                <div class="searchable-item p-3 bg-gray-50 rounded">
                    <strong>Complete Math Assignment</strong> - Due Dec 15
                </div>
                <div class="searchable-item p-3 bg-gray-50 rounded">
                    <strong>Study for Physics Exam</strong> - Due Dec 20
                </div>
                <div class="searchable-item p-3 bg-gray-50 rounded">
                    <strong>Write Lab Report</strong> - Due Dec 18
                </div>
                <div class="searchable-item p-3 bg-gray-50 rounded">
                    <strong>Group Project Meeting</strong> - Due Dec 22
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications Section -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-xl font-semibold text-gray-800">Enhanced Notifications</h2>
        </div>
        <div class="card-body">
            <div class="flex gap-4">
                <button class="btn btn-success" onclick="showDemoNotification('success')">
                    Show Success
                </button>
                <button class="btn btn-warning" onclick="showDemoNotification('warning')">
                    Show Warning
                </button>
                <button class="btn btn-danger" onclick="showDemoNotification('error')">
                    Show Error
                </button>
                <button class="btn btn-info" onclick="showDemoNotification('info')">
                    Show Info
                </button>
            </div>
        </div>
    </div>

    <!-- Modern Components Section -->
    <div class="card animate-on-scroll">
        <div class="card-header">
            <h2 class="text-xl font-semibold text-gray-800">Modern UX Components</h2>
        </div>
        <div class="card-body space-y-6">
            <!-- Skeleton Loading -->
            <div>
                <h3 class="text-lg font-medium mb-3">Skeleton Loading</h3>
                <div class="space-y-3">
                    <div class="skeleton h-4 w-full"></div>
                    <div class="skeleton h-4 w-3/4"></div>
                    <div class="skeleton h-4 w-1/2"></div>
                </div>
            </div>

            <!-- Modern Chips -->
            <div>
                <h3 class="text-lg font-medium mb-3">Modern Chips</h3>
                <div class="flex flex-wrap gap-2">
                    <span class="chip">Design</span>
                    <span class="chip">Development</span>
                    <span class="chip">UX Research</span>
                    <span class="chip">Prototyping</span>
                </div>
            </div>

            <!-- Enhanced Progress with Labels -->
            <div>
                <h3 class="text-lg font-medium mb-3">Enhanced Progress Indicators</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-sm mb-2">
                            <span class="font-medium">Project Completion</span>
                            <span class="text-primary-600 font-semibold">87%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar" style="width: 87%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between text-sm mb-2">
                            <span class="font-medium">Learning Progress</span>
                            <span class="text-success-600 font-semibold">92%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar" style="width: 92%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Interactive Elements -->
            <div>
                <h3 class="text-lg font-medium mb-3">Interactive Elements</h3>
                <div class="flex flex-wrap gap-4">
                    <button class="btn btn-primary" onclick="this.classList.toggle('loading')">
                        <span class="btn-text">Toggle Loading</span>
                    </button>
                    <button class="btn btn-secondary" onclick="this.classList.toggle('animate-pulse')">
                        Toggle Pulse
                    </button>
                    <button class="btn btn-success" onclick="this.classList.toggle('animate-bounce')">
                        Toggle Bounce
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Action Button -->
    <div class="fab" onclick="showDemoNotification('info')" title="Quick Action">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
    </div>
</div>

<!-- Demo Modal 1 -->
<div id="demoModal1" class="modal">
    <div class="modal-content w-96">
        <div class="modal-header">
            <h3 class="text-lg font-semibold">Demo Modal 1</h3>
            <button class="modal-close text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <p>This is a demo modal showcasing the enhanced modal functionality.</p>
            <p class="mt-2">It includes proper focus management, keyboard navigation, and accessibility features.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary modal-close">Cancel</button>
            <button class="btn btn-primary">Confirm</button>
        </div>
    </div>
</div>

<!-- Demo Modal 2 -->
<div id="demoModal2" class="modal">
    <div class="modal-content w-96">
        <div class="modal-header">
            <h3 class="text-lg font-semibold">Demo Modal 2</h3>
            <button class="modal-close text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <p>Another demo modal with different content.</p>
            <p class="mt-2">You can have multiple modals on the same page.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary modal-close">Close</button>
        </div>
    </div>
</div>

<script>
function showDemoNotification(type) {
    if (window.staApp && window.staApp.showNotification) {
        const messages = {
            success: 'This is a success notification!',
            warning: 'This is a warning notification!',
            error: 'This is an error notification!',
            info: 'This is an info notification!'
        };
        
        window.staApp.showNotification(messages[type], type);
    } else {
        // Fallback if app.js is not loaded
        alert(messages[type]);
    }
}

// Add some interactive elements
document.addEventListener('DOMContentLoaded', function() {
    // Add loading states to buttons
    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener('click', function() {
            if (!this.classList.contains('modal-close')) {
                this.classList.add('loading');
                setTimeout(() => {
                    this.classList.remove('loading');
                }, 1000);
            }
        });
    });
    
    // Add hover effects to cards
    document.querySelectorAll('.card').forEach(card => {
        card.classList.add('hover-lift');
    });
});
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
