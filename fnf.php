<?php
error_log("DEBUG: fnf.php script started.");

// Set error display for debugging - REMOVE IN PRODUCTION
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Your session_start() block should be here
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'name' => 'FINLAB_SESSID',
        'cookie_lifetime' => 86400,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
        'use_only_cookies' => 1
    ]);
}
error_log("DEBUG: Session status after start: " . session_status());
error_log("DEBUG: Session ID after start: " . session_id());
error_log("DEBUG: User ID in session (initial): " . ($_SESSION['user_id'] ?? 'NOT SET'));

// Include shared config and functions
// Ensure these paths are correct relative to fnf.php
require_once 'config.php';
require_once 'functions.php'; // General functions (e.g., connect_db, sanitize_input, getEmployeeName)

// --- DEBUGGING START: Verify getEmployeeName function ---
if (function_exists('getEmployeeName')) {
    error_log("DEBUG: getEmployeeName function IS defined (in fnf.php after functions.php include).");
    // You might remove the 'echo' in production
    echo "";
} else {
    error_log("DEBUG: getEmployeeName function IS NOT defined (in fnf.php after functions.php include).");
    echo "";
    // For more aggressive debugging, you can force the error here if it's not defined
    // die("Critical Error: getEmployeeName function is not defined. Check functions.php!");
}
// --- DEBUGGING END ---


// Include necessary classes
// Ensure these paths are correct relative to fnf.php
require_once 'classes/FNFSettlement.php'; // Your FNF specific class
require_once 'classes/EmployeeManager.php'; // Needed for getEmployeeName, hasPermission, isDepartmentHead etc.


// =============================================
// SECTION 2: BUSINESS LOGIC
// =============================================

// Initialize managers
try {
    $pdo = connect_db(); // This relies on the connect_db() function from functions.php
} catch (Exception $e) {
    // If DB connection fails, display an error and exit
    error_log("Fatal: Database connection failed in fnf.php: " . $e->getMessage());
    die("<h1>Service Unavailable</h1><p>We are experiencing technical difficulties. Please try again later.</p><p>Error: " . htmlspecialchars($e->getMessage()) . "</p>");
}

$fnf = new FNFSettlement($pdo); // Pass PDO to FNFSettlement constructor
$employeeManager = new EmployeeManager($pdo); // Initialize EmployeeManager for permission checks

// DEMO USER_ID - In a real app, this would come from secure authentication
// For testing permissions, you can temporarily change this:
// $employeeId = 101; // Example: Admin/HR user
// $employeeId = 205; // Example: Department Head user
// $employeeId = 302; // Example: Finance user
// $employeeId = 401; // Example: Regular employee (for self-service view)
$employeeId = $_SESSION['user_id'] ?? 101; // Fallback for local testing if session not set (e.g., first access)

$isHR = $employeeManager->hasPermission($employeeId, 'hr_access');
$isFinance = $employeeManager->hasPermission($employeeId, 'finance_access');
$isDeptHead = $employeeManager->isDepartmentHead($employeeId);
$isEmployee = !$isHR && !$isFinance && !$isDeptHead; // Simple logic for an employee self-service view

// Initialize variables
$settlementData = [];
$pendingApprovals = [];
$settlementHistory = [];
$employeesForSettlement = [];
$error = '';
$message = '';
$hasAnomaly = false; // For AI/ML Anomaly Detection demo

try {
    // Get employees who need settlement (resigned/left/terminated)
    $employeesForSettlement = $fnf->getEmployeesForSettlement();
    
    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // CSRF Token validation (essential for POST requests)
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("CSRF token mismatch. Please try again.");
        }

        if (isset($_POST['initiate_settlement'])) {
            $empId = (int)sanitize_input($_POST['employee_id']);
            if ($fnf->initiateSettlement($empId, $employeeId)) {
                $message = "Settlement initiated successfully. Waiting for department head approval and No Dues clearance.";
                auditLog($employeeId, 'INITIATE_FNF', "Initiated settlement for employee $empId");
            } else {
                throw new Exception("Failed to initiate settlement");
            }
        }
        
        // --- Added for "No Dues" Clearance ---
        if ($isDeptHead && isset($_POST['clear_no_dues'])) {
            $settlementId = (int)sanitize_input($_POST['settlement_id']);
            $deptName = sanitize_input($_POST['department_name']); // e.g., 'IT', 'Admin', 'Library'
            if ($fnf->clearNoDues($settlementId, $deptName, $employeeId)) {
                $message = "No Dues for " . htmlspecialchars($deptName) . " cleared successfully. Waiting for next step.";
                auditLog($employeeId, 'CLEAR_NO_DUES', "Cleared No Dues for settlement $settlementId by $deptName");
            } else {
                throw new Exception("Failed to clear No Dues for " . htmlspecialchars($deptName));
            }
        }
        // --- End Added for "No Dues" Clearance ---

        if ($isDeptHead && isset($_POST['approve_settlement'])) {
            $settlementId = (int)sanitize_input($_POST['settlement_id']);
            if ($fnf->approveSettlement($settlementId, $employeeId)) {
                $message = "Settlement approved. Waiting for HR processing.";
                auditLog($employeeId, 'APPROVE_FNF', "Approved settlement $settlementId");
            } else {
                throw new Exception("Failed to approve settlement");
            }
        }
        
        if ($isHR && isset($_POST['process_settlement'])) {
            $settlementId = (int)sanitize_input($_POST['settlement_id']);
            if ($fnf->processSettlement($settlementId, $employeeId)) {
                $message = "Settlement processed. Ready for finance payment.";
                auditLog($employeeId, 'PROCESS_FNF', "Processed settlement $settlementId");
            } else {
                throw new Exception("Failed to process settlement");
            }
        }
        
        if ($isFinance && isset($_POST['complete_settlement'])) {
            $settlementId = (int)sanitize_input($_POST['settlement_id']);
            if ($fnf->completeSettlement($settlementId, $employeeId)) {
                $message = "Settlement completed and payment processed.";
                auditLog($employeeId, 'COMPLETE_FNF', "Completed settlement $settlementId");
            } else {
                throw new Exception("Failed to complete settlement");
            }
        }
        
        // --- Sample: Handle Exit Survey Submission (very basic) ---
        if (isset($_POST['submit_exit_survey'])) {
            $surveyContent = sanitize_input($_POST['survey_feedback']);
            $employeeBeingSurveyedId = (int)sanitize_input($_POST['survey_employee_id']);
            // In a real app, save this to DB
            error_log("DEBUG: Exit Survey submitted for $employeeBeingSurveyedId: $surveyContent");
            $message = "Exit survey submitted successfully for employee " . htmlspecialchars($employeeManager->getEmployeeName($employeeBeingSurveyedId)) . ".";
            auditLog($employeeId, 'EXIT_SURVEY_SUBMIT', "Exit survey submitted for employee $employeeBeingSurveyedId");
        }
        // --- End Sample Exit Survey ---
    }
    
    // Generate a new CSRF token for the form
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Get data for display
    $pendingApprovals = $fnf->getPendingApprovals($employeeId);
    $settlementHistory = $fnf->getSettlementHistory();
    
    // Calculate settlement if requested via GET (e.g., from a link)
    if (isset($_GET['action']) && $_GET['action'] == 'calculate' && isset($_GET['employee_id'])) {
        $empId = (int)sanitize_input($_GET['employee_id']);
        $settlementData = $fnf->calculateSettlement($empId);
        auditLog($employeeId, 'CALCULATE_FNF', "Calculated settlement for employee $empId");
        
        // --- Sample: Anomaly Detection Trigger (on calculation) ---
        // Assuming calculateSettlement might return a flag or we check here
        if (isset($settlementData['net_amount']) && $settlementData['net_amount'] > 50000 && $empId == 103) { // Arbitrary condition for demo
             $hasAnomaly = true;
             $error = "Anomaly Detected: Final settlement amount for " . htmlspecialchars($employeeManager->getEmployeeName($empId)) . " is unusually high. Please review carefully.";
             auditLog($employeeId, 'FNF_ANOMALY', "Anomaly detected for settlement of employee $empId. Amount: " . $settlementData['net_amount']);
        }
        // --- End Sample Anomaly Detection ---
    }

} catch (Exception $e) {
    error_log("FNF System Error: " . $e->getMessage());
    $error = "A system error occurred. Please try again or contact support. Details: " . $e->getMessage(); // Show more detail for debugging
    auditLog($employeeId, 'SYSTEM_ERROR', $e->getMessage());
}

// --- Sample Notification Count (for header) ---
$notificationCount = count($pendingApprovals); // Simple demo: count pending approvals as notifications
// --- End Sample Notification Count ---

// =============================================
// SECTION 3: VIEW RENDERING
// =============================================
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinLab - Final Settlement (FNF)</title>
    
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    
    <style>
        /* All previous CSS styles remain the same */
        /* Add the new styles for workflow */
        
        .workflow-steps {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            position: relative;
        }
        
        .workflow-steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: var(--border-color);
            z-index: 1;
        }
        
        .workflow-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            text-align: center;
            width: 18%; /* Adjusted width for 5 steps */
        }
        
        .workflow-step-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: var(--bg-color);
            border: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 5px;
        }
        
        .workflow-step.active .workflow-step-icon {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .workflow-step-label {
            font-size: 0.8rem;
            color: var(--text-light);
        }
        
        .workflow-step.active .workflow-step-label {
            color: var(--primary-dark);
            font-weight: 500;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap; /* Allow wrapping for more buttons */
        }

        /* --- Custom Styles for new features --- */
        .notification-icon {
            position: relative;
            margin-right: 15px;
            display: inline-block;
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger-color);
            color: white;
            font-size: 0.7rem;
            padding: 3px 6px;
            border-radius: 50%;
            line-height: 1;
        }
        .floating-chatbot-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            font-size: 1.5rem;
            cursor: pointer;
            z-index: 1000;
            transition: background-color 0.3s ease;
        }
        .floating-chatbot-btn:hover {
            background-color: var(--primary-dark);
        }
        .modal-body .row {
            margin-bottom: 10px;
        }
        .modal-body .row strong {
            display: block;
            margin-bottom: 3px;
        }
        /* --- End Custom Styles --- */
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .workflow-steps {
                flex-wrap: wrap;
                gap: 15px;
            }
            
            .workflow-steps::before {
                display: none;
            }
            
            .workflow-step {
                width: 45%;
            }
            .action-buttons {
                flex-direction: column; /* Stack buttons on small screens */
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; // Ensure navbar.php is in the same directory as fnf.php ?>
    
    <div class="fnf-container">
        <header class="fnf-header">
            <h1><i class="fas fa-file-invoice-dollar"></i> Final Settlement Portal</h1>
            <div class="user-controls">
                <button class="btn-icon notification-icon" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <?php if ($notificationCount > 0): ?>
                        <span class="notification-badge"><?= $notificationCount ?></span>
                    <?php endif; ?>
                </button>
                <button id="themeToggle" class="btn-icon">
                    <i class="fas fa-moon"></i>
                </button>
                <span class="user-greeting">Welcome, <?= htmlspecialchars(getEmployeeName($employeeId)) ?></span>
            </div>
        </header>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($hasAnomaly): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> <strong>Anomaly Detected:</strong> This settlement might require extra scrutiny due to unusual patterns.
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        <?php if ($isEmployee): ?>
        <section class="info-card">
            <h2><i class="fas fa-user-circle"></i> Your Final Settlement Status</h2>
            <p>As an employee, you can track the status of your final settlement and access relevant documents here.</p>
            <div class="d-flex justify-content-center gap-3 mt-3">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#fnfSimulationModal">
                    <i class="fas fa-calculator me-2"></i>Simulate My F&F
                </button>
                <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#noDuesStatusModal">
                    <i class="fas fa-handshake-slash me-2"></i>My No Dues Status
                </button>
                <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#exitSurveyModal">
                    <i class="fas fa-poll me-2"></i>Complete Exit Survey
                </button>
            </div>
        </section>
        <?php endif; ?>
        <?php if ($isHR || $isDeptHead || $isFinance): ?>
        <section class="employee-selection-card">
            <h2><i class="fas fa-user-minus"></i> Employees Pending Settlement</h2>
            <p>Employees with status 'Resigned', 'Left', or 'Terminated' appear here for settlement processing.</p>
            
            <?php if (!empty($employeesForSettlement)): ?>
                <table class="employee-selection-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Employee No.</th>
                            <th>Department</th>
                            <th>Termination Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employeesForSettlement as $employee): ?>
                            <tr>
                                <td>
                                    <img src="<?= htmlspecialchars($employee['avatar_url'] ?? 'https://placehold.co/30x30/87CEEB/ffffff?text=EMP') ?>" 
                                         class="employee-list-photo" 
                                         onerror="this.src='https://placehold.co/30x30/87CEEB/ffffff?text=EMP'">
                                    <?= htmlspecialchars($employee['full_name']) ?>
                                </td>
                                <td><?= htmlspecialchars($employee['employee_number']) ?></td>
                                <td><?= htmlspecialchars($employee['department_name']) ?></td>
                                <td><?= date('d M Y', strtotime($employee['termination_date'])) ?></td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($employee['employee_status']) ?>">
                                        <?= htmlspecialchars($employee['employee_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($employee['has_pending_settlement']): ?>
                                        <span class="workflow-badge badge-<?= strtolower($employee['settlement_status']) ?>">
                                            <?= htmlspecialchars($employee['settlement_status']) ?>
                                        </span>
                                        <a href="fnf_details.php?id=<?= $employee['settlement_id'] ?>" class="btn btn-small">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="employee_id" value="<?= $employee['id'] ?>">
                                            <button type="submit" name="initiate_settlement" class="btn btn-primary initiate-btn">
                                                <i class="fas fa-play"></i> Initiate
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-results">No employees currently require settlement processing.</div>
            <?php endif; ?>
        </section>
        <?php endif; // End of HR/Admin/Dept Head View for Employee Selection ?>


        <?php if (!empty($pendingApprovals)): ?>
        <section class="approval-section">
            <h2><i class="fas fa-clipboard-check"></i> Pending Approvals & Actions</h2>
            <div class="approval-list">
                <?php foreach ($pendingApprovals as $approval): ?>
                    <div class="approval-item">
                        <div class="approval-info">
                            <h3><?= htmlspecialchars($approval['employee_name']) ?></h3>
                            <p>Department: <?= htmlspecialchars($approval['department_name']) ?></p>
                            <p>Termination Date: <?= date('d M Y', strtotime($approval['termination_date'])) ?></p>
                            <p>Net Amount: ₹<?= number_format($approval['net_amount'], 2) ?></p>
                        </div>
                        
                        <div class="workflow-steps">
                            <div class="workflow-step <?= $approval['status'] !== 'INITIATED' ? 'active' : '' ?>">
                                <div class="workflow-step-icon">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div class="workflow-step-label">Initiated</div>
                            </div>
                            <div class="workflow-step <?= $approval['no_dues_status'] === 'CLEARED' || $approval['status'] === 'PENDING_HR_PROCESSING' || $approval['status'] === 'PENDING_PAYMENT' || $approval['status'] === 'COMPLETED' ? 'active' : '' ?>">
                                <div class="workflow-step-icon">
                                    <i class="fas fa-handshake"></i>
                                </div>
                                <div class="workflow-step-label">No Dues</div>
                            </div>
                            <div class="workflow-step <?= $approval['status'] === 'PENDING_HR_PROCESSING' || $approval['status'] === 'PENDING_PAYMENT' || $approval['status'] === 'COMPLETED' ? 'active' : '' ?>">
                                <div class="workflow-step-icon">
                                    <i class="fas fa-user-cog"></i>
                                </div>
                                <div class="workflow-step-label">HR Processing</div>
                            </div>
                            <div class="workflow-step <?= $approval['status'] === 'PENDING_PAYMENT' || $approval['status'] === 'COMPLETED' ? 'active' : '' ?>">
                                <div class="workflow-step-icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="workflow-step-label">Payment</div>
                            </div>
                            <div class="workflow-step <?= $approval['status'] === 'COMPLETED' ? 'active' : '' ?>">
                                <div class="workflow-step-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="workflow-step-label">Completed</div>
                            </div>
                        </div>
                        <div class="action-buttons">
                            <?php if ($isDeptHead && $approval['status'] === 'INITIATED' && $employeeManager->getEmployeeDepartmentId($employeeId) == $approval['department_id'] && $approval['no_dues_status'] !== 'CLEARED'): ?>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="settlement_id" value="<?= $approval['id'] ?>">
                                    <input type="hidden" name="department_name" value="<?= htmlspecialchars($approval['department_name']) ?>">
                                    <button type="submit" name="clear_no_dues" class="btn btn-warning">
                                        <i class="fas fa-clipboard-check"></i> Clear No Dues
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php if ($isDeptHead && $approval['status'] === 'PENDING_DEPT_APPROVAL'): ?>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="settlement_id" value="<?= $approval['id'] ?>">
                                    <button type="submit" name="approve_settlement" class="btn btn-success">
                                        <i class="fas fa-check"></i> Approve Settlement
                                    </button>
                                </form>
                            <?php elseif ($isHR && $approval['status'] === 'PENDING_HR_PROCESSING'): ?>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="settlement_id" value="<?= $approval['id'] ?>">
                                    <button type="submit" name="process_settlement" class="btn btn-primary">
                                        <i class="fas fa-cog"></i> Process Settlement
                                    </button>
                                </form>
                            <?php elseif ($isFinance && $approval['status'] === 'PENDING_PAYMENT'): ?>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="settlement_id" value="<?= $approval['id'] ?>">
                                    <button type="submit" name="complete_settlement" class="btn btn-success">
                                        <i class="fas fa-check-circle"></i> Complete Payment
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <a href="fnf_details.php?id=<?= $approval['id'] ?>" class="btn btn-info">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            
                            <?php if (($approval['status'] === 'INITIATED' || $approval['status'] === 'PENDING_DEPT_APPROVAL') && ($isHR || $isDeptHead)): ?>
                                <a href="fnf_calculate.php?employee_id=<?= $approval['employee_id'] ?>" class="btn btn-secondary">
                                    <i class="fas fa-calculator"></i> Re-Calculate
                                &nbsp;</a>
                            <?php endif; ?>

                            <?php if ($isHR && $approval['status'] === 'COMPLETED' && !$fnf->hasSignedSettlement($approval['id'])): // Assuming a method exists ?>
                                <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#digitalSignModal">
                                    <i class="fas fa-signature"></i> Digitally Sign
                                </button>
                            <?php endif; ?>
                            </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <section class="settlement-history-card">
            <h2><i class="fas fa-history"></i> Settlement History</h2>
            <?php if ($isHR || $isFinance): ?>
            <div class="text-end mb-3">
                <a href="compliance_dashboard.php" class="btn btn-outline-info btn-sm">
                    <i class="fas fa-chart-line me-1"></i> View Compliance Dashboard
                </a>
            </div>
            <?php endif; ?>
            <?php if (!empty($settlementHistory)): ?>
                <table class="settlement-history-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Termination Date</th>
                            <th>Net Amount</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($settlementHistory as $history): ?>
                            <tr>
                                <td><?= htmlspecialchars($history['employee_name']) ?></td>
                                <td><?= date('d M Y', strtotime($history['termination_date'])) ?></td>
                                <td>₹<?= number_format($history['net_amount'], 2) ?></td>
                                <td>
                                    <span class="workflow-badge badge-<?= strtolower($history['status']) ?>">
                                        <?= htmlspecialchars($history['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('d M Y H:i', strtotime($history['updated_at'])) ?></td>
                                <td>
                                    <a href="fnf_details.php?id=<?= $history['id'] ?>" class="btn btn-small btn-info">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-results">No settlement history available.</div>
            <?php endif; ?>
        </section>
        
    </div>

    <div class="modal fade" id="fnfSimulationModal" tabindex="-1" aria-labelledby="fnfSimulationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="fnfSimulationModalLabel"><i class="fas fa-calculator me-2"></i>Simulate Your Final Settlement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">This is an **estimation** based on your current data (leave balance, outstanding loans, etc.). The final amount may vary.</p>
                    <div class="row mb-2">
                        <div class="col-6"><strong>Estimated Unpaid Salary:</strong></div>
                        <div class="col-6">₹ <span id="simulated_unpaid_salary">0.00</span></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6"><strong>Estimated Leave Encashment:</strong></div>
                        <div class="col-6">₹ <span id="simulated_leave_encashment">0.00</span></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6"><strong>Estimated Deductions (Loans/etc.):</strong></div>
                        <div class="col-6">₹ <span id="simulated_deductions">0.00</span></div>
                    </div>
                    <hr>
                    <div class="row fw-bold fs-5">
                        <div class="col-6"><strong>Estimated Net Payable:</strong></div>
                        <div class="col-6">₹ <span id="simulated_net_payable">0.00</span></div>
                    </div>
                    <small class="text-info mt-3 d-block"><i class="fas fa-info-circle me-1"></i> Data updated as of: <?= date('d M Y') ?></small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="runSimulationBtn"><i class="fas fa-sync-alt me-1"></i> Run Simulation</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="noDuesStatusModal" tabindex="-1" aria-labelledby="noDuesStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="noDuesStatusModalLabel"><i class="fas fa-handshake-slash me-2"></i>No Dues Clearance Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Track the clearance status from various departments.</p>
                    <ul class="list-group" id="noDuesStatusList">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            IT Department 
                            <span class="badge bg-warning">Pending</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Admin Department 
                            <span class="badge bg-success">Cleared</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Finance Department 
                            <span class="badge bg-warning">Pending</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Library Department 
                            <span class="badge bg-secondary">N/A</span>
                        </li>
                    </ul>
                    <small class="text-muted mt-3 d-block"><i class="fas fa-lightbulb me-1"></i> Please ensure all departments clear your dues for smooth processing.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="exitSurveyModal" tabindex="-1" aria-labelledby="exitSurveyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title" id="exitSurveyModalLabel"><i class="fas fa-poll me-2"></i>Complete Your Exit Survey</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Your honest feedback helps us improve. This survey is anonymous.</p>
                    <form id="exitSurveyForm" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="survey_employee_id" value="<?= $employeeId ?>"> <div class="mb-3">
                            <label for="reasonForLeaving" class="form-label">Reason for leaving:</label>
                            <select class="form-select" id="reasonForLeaving" name="reason_for_leaving">
                                <option value="">-- Select --</option>
                                <option value="better_opportunity">Better Career Opportunity</option>
                                <option value="salary">Compensation/Benefits</option>
                                <option value="work_environment">Work Environment/Culture</option>
                                <option value="relocation">Relocation</option>
                                <option value="personal">Personal Reasons</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="feedbackText" class="form-label">Any specific feedback or suggestions for improvement?</label>
                            <textarea class="form-control" id="feedbackText" name="survey_feedback" rows="5" placeholder="Your feedback..."></textarea>
                        </div>
                        <button type="submit" name="submit_exit_survey" class="btn btn-primary"><i class="fas fa-paper-plane me-2"></i>Submit Survey</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="digitalSignModal" tabindex="-1" aria-labelledby="digitalSignModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="digitalSignModalLabel"><i class="fas fa-signature me-2"></i>Digitally Sign Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>By clicking 'Sign Now', you are digitally signing the final settlement statement for this employee, acknowledging its accuracy and completeness.</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> This action is legally binding and irreversible.</p>
                    <div class="text-center mt-4">
                        <button type="button" class="btn btn-success btn-lg"><i class="fas fa-signature me-2"></i>Sign Now</button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    <div class="floating-chatbot-btn" title="Chat with AI Assistant">
        <i class="fas fa-robot"></i>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <script>
    $(document).ready(function() {
        // Theme toggle
        $('#themeToggle').on('click', function() {
            const currentTheme = $('html').attr('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            $('html').attr('data-theme', newTheme);
            document.cookie = `theme=${newTheme}; path=/; max-age=31536000; SameSite=Lax`;
            $(this).find('i').toggleClass('fa-moon fa-sun');
        });
        
        // Set initial theme icon
        const currentTheme = $('html').attr('data-theme');
        if (currentTheme === 'dark') {
            $('#themeToggle i').removeClass('fa-moon').addClass('fa-sun');
        }
        
        // Initialize DataTables for the tables
        // Check if tables exist before initializing DataTables
        if ($.fn.DataTable) { // Check if DataTables plugin is loaded
            if ($('.employee-selection-table').length) {
                $('.employee-selection-table').DataTable({
                    "paging": true,
                    "ordering": true,
                    "info": true,
                    "searching": true,
                    "autoWidth": false,
                    "responsive": true
                });
            }
            if ($('.settlement-history-table').length) {
                $('.settlement-history-table').DataTable({
                    "paging": true,
                    "ordering": true,
                    "info": true,
                    "searching": true,
                    "autoWidth": false,
                    "responsive": true
                });
            }
        }

        // Confirmation for critical actions
        $('form').on('submit', function(e) {
            const form = this;
            if (form.querySelector('[name="complete_settlement"]') || 
                form.querySelector('[name="approve_settlement"]') ||
                form.querySelector('[name="clear_no_dues"]') // Added for No Dues clearance
            ) {
                e.preventDefault();
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This action cannot be undone!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745', // Use Bootstrap success color
                    cancelButtonColor: '#dc3545', // Use Bootstrap danger color
                    confirmButtonText: 'Yes, proceed!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            } else if (form.id === 'exitSurveyForm') { // Specific confirmation for exit survey
                 e.preventDefault();
                 Swal.fire({
                    title: 'Submit Survey?',
                    text: "Your feedback is valuable!",
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#007bff',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, submit!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            }
        });

        // --- Sample: F&F Simulation (AJAX Call) ---
        $('#runSimulationBtn').on('click', function() {
            // In a real app, you'd fetch employee-specific data
            // and send an AJAX request to a backend endpoint (e.g., simulate_fnf.php)
            // For demo, we'll just use dummy values and a slight delay.
            Swal.fire({
                title: 'Simulating...',
                text: 'Calculating estimated settlement amount.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            setTimeout(() => {
                const unpaidSalary = (Math.random() * 20000).toFixed(2);
                const leaveEncashment = (Math.random() * 10000).toFixed(2);
                const deductions = (Math.random() * 5000).toFixed(2);
                const netPayable = (unpaidSalary * 1 + leaveEncashment * 1 - deductions * 1).toFixed(2); // Convert to number for arithmetic

                $('#simulated_unpaid_salary').text(numberWithCommas(unpaidSalary));
                $('#simulated_leave_encashment').text(numberWithCommas(leaveEncashment));
                $('#simulated_deductions').text(numberWithCommas(deductions));
                $('#simulated_net_payable').text(numberWithCommas(netPayable));
                
                Swal.close();
            }, 1500); // Simulate network delay
        });

        function numberWithCommas(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        // --- End Sample: F&F Simulation ---

        // --- Sample: Chatbot (Just opens an alert for demo) ---
        $('.floating-chatbot-btn').on('click', function() {
            Swal.fire({
                title: 'AI Assistant',
                text: 'Welcome to the AI chat! How can I help you with your final settlement queries today?',
                icon: 'question',
                confirmButtonText: 'Okay',
                customClass: {
                    confirmButton: 'btn btn-primary'
                },
                buttonsStyling: false
            });
            // In a real application, this would open a chatbot interface
        });
        // --- End Sample: Chatbot ---

        // For modals, ensure they use Bootstrap 5's data attributes: data-bs-toggle, data-bs-target
        // And if you use dismiss, data-bs-dismiss="modal"

    });
    </script>
</body>
</html>