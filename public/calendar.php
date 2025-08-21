<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure APP_URL is defined
if (!defined('APP_URL')) {
    define('APP_URL', 'http://localhost/student-time-advisor-php/public');
}

require_login();
$user = current_user();
$pdo = DB::conn();

// Get tasks for calendar
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ? ORDER BY due_at ASC");
$stmt->execute([$user['id']]);
$tasks = $stmt->fetchAll();

// Format tasks for FullCalendar
$calendar_events = [];
foreach ($tasks as $task) {
    $color = '';
    switch ($task['category']) {
        case 'Exam': $color = '#dc2626'; break; // red
        case 'Assignment': $color = '#2563eb'; break; // blue
        case 'Lab': $color = '#16a34a'; break; // green
        case 'Lecture': $color = '#9333ea'; break; // purple
        default: $color = '#6b7280'; break; // gray
    }
    
    $calendar_events[] = [
        'id' => $task['id'],
        'title' => $task['title'],
        'start' => $task['due_at'],
        'end' => date('Y-m-d H:i:s', strtotime($task['due_at'] . ' +' . $task['estimated_minutes'] . ' minutes')),
        'backgroundColor' => $color,
        'borderColor' => $color,
        'textColor' => '#ffffff',
        'extendedProps' => [
            'category' => $task['category'],
            'description' => $task['description'],
            'completed' => $task['completed'],
            'estimated_minutes' => $task['estimated_minutes']
        ]
    ];
}

include __DIR__ . '/../includes/layout/header.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-50 via-indigo-50 to-purple-50 rounded-xl p-6 mb-6 border border-blue-100">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Calendar</h1>
                    <p class="text-gray-600 mt-1">Visualize and manage your schedule</p>
                </div>
            </div>
            <button onclick="document.getElementById('createTaskModal').classList.remove('hidden')" 
                    class="mt-4 sm:mt-0 bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-6 py-3 rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 font-medium shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                + Add Task
            </button>
        </div>
    </div>

    <!-- Calendar Legend -->
    <div class="bg-gradient-to-br from-white to-gray-50 rounded-xl shadow-lg p-6 border border-gray-100">
        <div class="flex items-center mb-4">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center mr-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800">Task Categories</h3>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="flex items-center gap-3 p-3 bg-red-50 rounded-lg border border-red-200">
                <div class="w-4 h-4 rounded-full bg-red-500 shadow-sm"></div>
                <span class="text-sm font-medium text-red-700">Exam</span>
            </div>
            <div class="flex items-center gap-3 p-3 bg-blue-50 rounded-lg border border-blue-200">
                <div class="w-4 h-4 rounded-full bg-blue-500 shadow-sm"></div>
                <span class="text-sm font-medium text-blue-700">Assignment</span>
            </div>
            <div class="flex items-center gap-3 p-3 bg-green-50 rounded-lg border border-green-200">
                <div class="w-4 h-4 rounded-full bg-green-500 shadow-sm"></div>
                <span class="text-sm font-medium text-green-700">Lab</span>
            </div>
            <div class="flex items-center gap-3 p-3 bg-purple-50 rounded-lg border border-purple-200">
                <div class="w-4 h-4 rounded-full bg-purple-500 shadow-sm"></div>
                <span class="text-sm font-medium text-purple-700">Lecture</span>
            </div>
            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                <div class="w-4 h-4 rounded-full bg-gray-500 shadow-sm"></div>
                <span class="text-sm font-medium text-gray-700">Other</span>
            </div>
        </div>
    </div>

    <!-- Calendar Container -->
    <div class="bg-white rounded-xl shadow p-6">
        <div id="calendar"></div>
    </div>
</div>

<!-- Create Task Modal -->
<div id="createTaskModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Create New Task</h3>
                <button onclick="document.getElementById('createTaskModal').classList.add('hidden')" 
                        class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form method="post" action="<?php echo APP_URL; ?>/tasks.php" class="space-y-4">
                <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Task Title *</label>
                    <input type="text" name="title" required 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Enter task title">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="Lecture">Lecture</option>
                        <option value="Lab">Lab</option>
                        <option value="Exam">Exam</option>
                        <option value="Assignment">Assignment</option>
                        <option value="Other" selected>Other</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3" 
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Task description (optional)"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Due Date & Time *</label>
                    <input type="datetime-local" name="due_at" required 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Minutes</label>
                    <input type="number" name="estimated_minutes" value="60" min="15" step="15"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="submit" name="create" value="1" 
                            class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        Create Task
                    </button>
                    <button type="button" onclick="document.getElementById('createTaskModal').classList.add('hidden')"
                            class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Task Details Modal -->
<div id="taskDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Task Details</h3>
                <button onclick="document.getElementById('taskDetailsModal').classList.add('hidden')" 
                        class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div id="taskDetailsContent" class="space-y-4">
                <!-- Task details will be populated here -->
            </div>
            
            <div class="flex gap-3 pt-4">
                <button onclick="completeTask()" id="completeTaskBtn"
                        class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors font-medium">
                    Mark Complete
                </button>
                <button onclick="document.getElementById('taskDetailsModal').classList.add('hidden')"
                        class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- FullCalendar CSS and JS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

<script>
let currentTaskId = null;

document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        height: 'auto',
        events: <?php echo json_encode($calendar_events); ?>,
        eventClick: function(info) {
            showTaskDetails(info.event);
        },
        eventDidMount: function(info) {
            // Add tooltip
            info.el.title = info.event.title;
        },
        dayMaxEvents: true,
        moreLinkClick: 'popover',
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            meridiem: 'short'
        },
        slotMinTime: '06:00:00',
        slotMaxTime: '22:00:00',
        allDaySlot: false,
        slotDuration: '00:30:00',
        selectable: true,
        select: function(info) {
            // Pre-fill the create task modal with selected date/time
            const startDate = info.startStr;
            const endDate = info.endStr;
            
            // Convert to datetime-local format
            const startDateTime = startDate.replace('T', ' ').substring(0, 16);
            
            document.querySelector('input[name="due_at"]').value = startDateTime;
            document.getElementById('createTaskModal').classList.remove('hidden');
        },
        selectConstraint: {
            start: '00:00',
            end: '24:00'
        }
    });
    
    calendar.render();
});

function showTaskDetails(event) {
    const task = event.event;
    currentTaskId = task.id;
    
    const content = document.getElementById('taskDetailsContent');
    const completeBtn = document.getElementById('completeTaskBtn');
    
    // Populate task details
    content.innerHTML = `
        <div>
            <h4 class="font-medium text-gray-900 mb-2">${task.title}</h4>
            <div class="space-y-2 text-sm">
                <div class="flex items-center gap-2">
                    <span class="px-2 py-1 text-xs rounded-full ${getCategoryColor(task.extendedProps.category)}">
                        ${task.extendedProps.category}
                    </span>
                </div>
                ${task.extendedProps.description ? `<p class="text-gray-600">${task.extendedProps.description}</p>` : ''}
                <div class="text-gray-500">
                    <div>Due: ${formatDateTime(task.start)}</div>
                    ${task.extendedProps.estimated_minutes ? `<div>Estimated: ${event.extendedProps.estimated_minutes} minutes</div>` : ''}
                </div>
            </div>
        </div>
    `;
    
    // Show/hide complete button based on completion status
    if (task.extendedProps.completed) {
        completeBtn.style.display = 'none';
    } else {
        completeBtn.style.display = 'block';
    }
    
    document.getElementById('taskDetailsModal').classList.remove('hidden');
}

function completeTask() {
    if (!currentTaskId) return;
    
    // Create a form to submit the completion
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?php echo APP_URL; ?>/tasks.php';
    
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrf';
    csrfInput.value = '<?php echo csrf_token(); ?>';
    
    const taskIdInput = document.createElement('input');
    taskIdInput.type = 'hidden';
    taskIdInput.name = 'complete_task_id';
    taskIdInput.value = currentTaskId;
    
    form.appendChild(csrfInput);
    form.appendChild(taskIdInput);
    
    document.body.appendChild(form);
    form.submit();
}

function getCategoryColor(category) {
    switch(category) {
        case 'Exam': return 'bg-red-100 text-red-700 border-red-200';
        case 'Assignment': return 'bg-blue-100 text-blue-700 border-blue-200';
        case 'Lab': return 'bg-green-100 text-green-700 border-green-200';
        case 'Lecture': return 'bg-purple-100 text-purple-700 border-purple-200';
        default: return 'bg-gray-100 text-gray-700 border-gray-200';
    }
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Close modals when clicking outside
window.onclick = function(event) {
    const createModal = document.getElementById('createTaskModal');
    const detailsModal = document.getElementById('taskDetailsModal');
    
    if (event.target === createModal) {
        createModal.classList.add('hidden');
    }
    if (event.target === detailsModal) {
        detailsModal.classList.add('hidden');
    }
}
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>