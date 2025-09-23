<?php
/**
 * calendar.php - Displays calendar, tasks, and integrates chat.
 * - Uses shared navbar.php (which includes chat).
 * - Links main style.css.
 * - Uses Lucide icons.
 */

// --- Authentication Check ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=login_required');
    exit;
}
// --- END Authentication Check ---

require_once 'config.php';
require_once 'functions.php';

// --- Set Navbar Variables ---
$isDashboard = false;
$pageTitle = "Calendar";
$pageIconClass = "calendar-days";
$pageIconColorClass = "calendar";

// --- Database Connection & Initial Data Fetch (Example) ---
$pdo = connect_db();
$events = []; // Initialize events array
$todos = []; // Initialize todos array
$fetch_error = null;
if (!$pdo) {
    $fetch_error = "Database connection failed.";
} else {
    try {
        // Example: Fetch events (adjust query as needed)
        // $eventStmt = $pdo->prepare("SELECT id, title, start_datetime as start, end_datetime as end, description, status, category FROM calendar_events WHERE user_id = ?");
        // $eventStmt->execute([$_SESSION['user_id']]);
        // $events = $eventStmt->fetchAll(PDO::FETCH_ASSOC);

        // Example: Fetch todos (adjust query as needed)
        // $todoStmt = $pdo->prepare("SELECT id, title, description, due_date as dueDate, assigned_by as assignedBy, priority, completed, created_at, tags FROM todos WHERE user_id = ? ORDER BY dueDate ASC, createdAt ASC");
        // $todoStmt->execute([$_SESSION['user_id']]);
        // $todos = $todoStmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Database Error fetching calendar/todo data: " . $e->getMessage());
        $fetch_error = "Error fetching data.";
    }
    $pdo = null; // Close connection
}

// Get flash message from session if exists
$flash_message = $_SESSION['flash_message'] ?? null;
$flash_status = $_SESSION['flash_status'] ?? null;
unset($_SESSION['flash_message'], $_SESSION['flash_status']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - FinLab ERP</title>
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> {/* Keep for chat icons */}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://unpkg.com/peerjs@1.4.7/dist/peerjs.min.js"></script>
</head>
<body class="preload">

    <?php include 'navbar.php'; // Includes navbar, modals, chat HTML & JS ?>

    <div class="page-content"> 

        <div class="loading-overlay"> <div class="loading-spinner"></div> </div>

        
        <div class="search-container collapsed">
            <input type="text" id="searchInput" placeholder="Search meetings, tasks, people..." aria-label="Search input">
            <button id="generateReportBtnTrigger" class="btn btn-primary btn-small"> <i data-lucide="file-text"></i> Report </button>
        </div>

        <?php if ($flash_message): ?> <div class="update-message <?php echo $flash_status === 'success' ? 'success' : 'error'; ?>"> <span><?php echo htmlspecialchars($flash_message); ?></span> <button onclick="this.parentElement.style.display='none'">&times;</button> </div> <?php endif; ?>
        <?php if (isset($fetch_error)): ?> <div class="update-message error"> <span><?php echo htmlspecialchars($fetch_error); ?></span> </div> <?php endif; ?>

        <div class="main-container">
             <div class="calendar-container">
                 <div id="calendar"></div>
             </div>
             <div class="resize-handle" title="Resize panels"></div>
             <div class="todo-container">
                 <div class="todo-header">
                     <h2>Tasks</h2>
                     <button id="toggleTodoPanel" class="btn btn-icon btn-small" aria-label="Toggle task panel"> <i data-lucide="chevron-right"></i> </button>
                 </div>
                 <div class="form-group todo-filter">
                     <label for="todoFilter" class="visually-hidden">Filter Tasks</label>
                     <select id="todoFilter" class="form-control"> <option value="all">All Tasks</option> <option value="pending">Pending</option> <option value="today">Due Today</option> <option value="week">Due This Week</option> <option value="completed">Completed</option> <option value="rejected">Rejected</option> </select>
                 </div>
                 <div class="task-sections-container">
                     <p class="loading-placeholder">Loading tasks...</p>
                 </div>
             </div>
        </div>

        <div class="fab-container"> <button class="fab" id="quickAddBtn" aria-label="Quick add"> <i data-lucide="plus"></i> </button> </div>

        <div class="quick-add-menu" id="quickAddMenu">
             <button class="quick-add-option" data-type="meeting" aria-label="Add meeting"> <i data-lucide="users"></i> <span>New Meeting</span> </button>
             <button class="quick-add-option" data-type="task" aria-label="Add task"> <i data-lucide="check-square"></i> <span>New Task</span> </button>
             <button class="quick-add-option" data-type="reminder" aria-label="Add reminder"> <i data-lucide="bell"></i> <span>New Reminder</span> </button>
        </div>

        <div id="taskModal" class="modal hidden">  </div>
        <div id="taskFormModal" class="modal task-form-modal hidden">  </div>
        <div id="reportModal" class="modal hidden">  </div>
        <div id="confirmationModal" class="modal hidden">  </div>
        <div id="callModal" class="modal call-modal hidden"></div>

        <div id="undoNotification" class="hidden" style="position: fixed; bottom: 90px; right: 24px; z-index: 100;">  </div>

    </div> 

    <script>
        // Ensure jQuery is loaded before accessing $
        if (typeof jQuery !== 'undefined') {
            $(document).ready(function() {
                console.log("CALENDAR DEBUG: DOM ready.");

                // --- Calendar Specific JS ---
                let calendar;
                // Use PHP-provided data or default to empty arrays
                let tasks = <?php echo json_encode($events ?: []); ?>;
                let todos = <?php echo json_encode($todos ?: []); ?>; // Assuming $todos might be fetched
                let notifications = []; // Example, fetch if needed
                let lastAction = null; let lastActionData = null;
                let peer; let localStream; let currentCall = null; let isMuted = false; let isVideoOff = false;

                // --- Initialization Functions ---
                function initializeCalendar() { /* ... FullCalendar init logic ... */ }
                function loadSampleData() { /* ... Removed, use fetched or empty data ... */ }
                function initializeChat() { console.log("CALENDAR DEBUG: Chat initialized by navbar.php"); } // Placeholder
                function initializeWebRTC() { console.log("CALENDAR DEBUG: Initializing WebRTC..."); /* ... PeerJS logic ... */ }
                function loadFromLocalStorage() { console.log("CALENDAR DEBUG: Loading settings from local storage..."); /* ... load settings ... */ }
                function saveToLocalStorage() { console.log("CALENDAR DEBUG: Saving data to local storage..."); /* ... save tasks/todos ... */ }

                // --- Rendering Functions ---
                function refreshCalendar() { /* ... Renders tasks onto FullCalendar ... */ }
                function renderTodoList() { console.log("CALENDAR DEBUG: Rendering todo list..."); /* ... Renders todos into side panel ... */ }
                function updateNotificationBadge() { console.log("CALENDAR DEBUG: Updating notification badge..."); /* ... Updates badge in navbar ... */ }
                // ... other rendering helpers ...

                // --- Event Handling Functions ---
                function handleCalendarSelect(info) { /* ... Opens taskModal for new event ... */ }
                function handleEventClick(info) { /* ... Opens taskModal with existing event data ... */ }
                function handleEventDrop(info) { /* ... Handles event drag/drop ... */ }
                function handleEventResize(info) { /* ... Handles event resize ... */ }
                function showNotification(message, type = 'info') { /* ... Shows temporary popup ... */ }
                function showConfirmation(message, callback) { /* ... Shows confirmation modal ... */ }
                function showUndoOption(message) { /* ... Shows undo notification ... */ }
                function undoLastAction() { /* ... Reverts last action ... */ }
                function searchTasks(query) { /* ... Filters calendar/todos ... */ }
                function startTour() { console.log("CALENDAR DEBUG: Starting tour..."); /* ... tour logic ... */ }
                function startCall() { console.log("CALENDAR DEBUG: Starting call..."); /* ... WebRTC call logic ... */ }
                function endCall() { console.log("CALENDAR DEBUG: Ending call..."); /* ... WebRTC call logic ... */ }
                function toggleMute() { /* ... WebRTC mute logic ... */ }
                function toggleVideo() { /* ... WebRTC video logic ... */ }

                // --- Setup Event Listeners ---
                function setupEventListeners() {
                    console.log("CALENDAR DEBUG: Setting up event listeners...");
                    // Search Toggle
                    $('#searchToggle').off('click').on('click', function() { $('.search-container').toggleClass('collapsed'); if (!$('.search-container').hasClass('collapsed')) { $('#searchInput').focus(); } });
                    // Todo Panel Toggle
                    $('#toggleTodoPanel').off('click').on('click', function() { $('.todo-container').toggleClass('collapsed'); const icon = $(this).find('i'); icon.attr('data-lucide', $('.todo-container').hasClass('collapsed') ? 'chevron-left' : 'chevron-right'); if (typeof lucide !== 'undefined') lucide.createIcons(); });
                    // Quick Add Button & Menu
                    $('#quickAddBtn').off('click').on('click', function() { $('#quickAddMenu').toggleClass('show'); });
                    $('.quick-add-option').off('click').on('click', function() { /* ... handle quick add options ... */ });
                    // Modal Close Buttons (using delegation on document for simplicity)
                    $(document).off('click', '.close-modal-btn').on('click', '.close-modal-btn', function() { $(this).closest('.modal').addClass('hidden').css('display', 'none'); });
                    // Close Modals on Background Click
                    $('.modal').off('click').on('click', function(e) { if ($(e.target).hasClass('modal')) { $(this).addClass('hidden').css('display', 'none'); } });
                    // Save Task Modal Button
                    $('#saveTaskBtn').off('click').on('click', function() { /* ... save task/event logic ... */ });
                    // Delete Task Modal Button
                    $('#deleteTaskBtn').off('click').on('click', function() { /* ... delete task/event logic ... */ });
                    // Todo List Actions (Delegated)
                    $('.task-sections-container').off('click').on('click', '.complete-todo', function() { /* ... complete logic ... */ });
                    $('.task-sections-container').on('click', '.delete-todo', function() { /* ... delete logic ... */ });
                    $('.task-sections-container').on('click', '.reject-todo', function() { /* ... reject logic ... */ });
                    $('.task-sections-container').on('click', '.task-section-header', function() { /* ... toggle section ... */ });
                    // Report Modal Trigger & Logic
                    $('#generateReportBtnTrigger').off('click').on('click', function() { openModal('reportModal'); });
                    $('#reportType').off('change').on('change', function() { $('#taskStatusFilter').toggleClass('hidden', $(this).val() !== 'tasks'); });
                    $('#generateReportBtn').off('click').on('click', function() { /* ... generate report logic ... */ });
                    // Confirmation Modal Confirm Button
                    $('#confirmActionBtn').off('click').on('click', function() { const callback = $(this).data('callback'); if (callback && typeof callback === 'function') { callback(); } closeModal('confirmationModal'); });
                    // Add Task Button (in empty list)
                    $('.task-sections-container').on('click', '.add-task-btn', function() { openModal('taskFormModal'); });
                    // Undo Button
                    $('#undoActionBtn').off('click').on('click', function() { undoLastAction(); $('#undoNotification').addClass('hidden'); });
                    // File Attachment Preview
                    $('#eventAttachments').off('change').on('change', function() { /* ... attachment preview logic ... */ });
                    // Call Buttons
                    $('#chatCallBtn').off('click').on('click', startCall); // Assuming chatCallBtn exists in chat UI
                    $('#muteBtn').off('click').on('click', toggleMute);
                    $('#videoBtn').off('click').on('click', toggleVideo);
                    $('#endCallBtn').off('click').on('click', endCall);

                    console.log("CALENDAR DEBUG: Event listeners attached.");
                }

                // --- Setup Keyboard Shortcuts ---
                function setupKeyboardShortcuts() { /* ... shortcut logic ... */ }

                // --- Initial Setup Calls ---
                try {
                    initializeCalendar();
                    // loadSampleData(); // Load real data via PHP now
                    renderTodoList(); // Render based on fetched or empty $todos
                    updateNotificationBadge(); // Based on fetched or sample notifications
                    loadFromLocalStorage(); // Load user settings
                    // initializeChat(); // Handled by navbar.php
                    initializeWebRTC();
                    setupEventListeners();
                    setupKeyboardShortcuts();
                } catch(initError) {
                     console.error("CALENDAR DEBUG: Error during initialization:", initError);
                     // Display a user-friendly error on the page?
                }

                // Remove preload class after setup
                setTimeout(() => { document.body.classList.remove('preload'); }, 100);
                console.log("CALENDAR DEBUG: Page setup finished.");

            }); // End document ready
        } // End jQuery check
    </script>

   
    <script> if ('serviceWorker' in navigator)  </script>
   
    <script> let deferredPrompt; window.addEventListener('beforeinstallprompt', (e) => ); function installPWA() </script>
   
    <script> const manifest = ; console.log('Web App Manifest:', manifest); </script>

</body>
</html>
