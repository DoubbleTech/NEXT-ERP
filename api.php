<?php
// api.php - Central API Endpoint for FinLab ERP

// Enable error reporting for debugging. REMOVE OR DISABLE IN PRODUCTION!
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Session Start & Authentication Check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

// REMOVED: The temporary hardcoded role check for 'umarali667@gmail.com'.
// The user's role is now correctly retrieved from the session after login.

// 2. Include Configuration & Manager Classes
require_once 'config.php';
require_once 'functions.php';
require_once 'classes/EmployeeManager.php';
require_once 'classes/DepartmentManager.php';
require_once 'classes/ReimbursementManager.php';
require_once 'classes/PayrollManager.php';
require_once 'classes/TaxSlabManager.php';
require_once 'classes/UserManager.php';

// Set default response header to JSON
header('Content-Type: application/json');

// Instantiate Manager Classes
try {
    $pdo = connect_db();
    if (!$pdo) {
        throw new PDOException('Database connection failed.');
    }
    $employeeManager = new EmployeeManager($pdo);
    $departmentManager = new DepartmentManager($pdo);
    $payrollManager = new PayrollManager($pdo);
    $reimbursementManager = new ReimbursementManager($pdo, $payrollManager);
    $taxManager = new TaxSlabManager($pdo);
    $userManager = new UserManager($pdo);
} catch (PDOException $e) {
    error_log("API Database connection error during manager instantiation: " . $e->getMessage());
    $response['message'] = 'Database connection error. Please try again later.';
    echo json_encode($response);
    exit;
} catch (Exception $e) {
    error_log("API Initialization error during manager instantiation: " . $e->getMessage());
    $response['message'] = 'Server initialization error. Please try again later. (' . $e->getMessage() . ')';
    echo json_encode($response);
    exit;
}

// 3. CSRF Token Validation for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $response['message'] = 'CSRF token mismatch. Please refresh the page.';
        echo json_encode($response);
        exit;
    }
}

// 4. Define permission map for RBAC
// CORRECTED: Changed 'super_admin' to 'super-admin' to match the database role.
$permissionMap = [
    'super-admin' => [
        'employees' => ['view', 'edit', 'delete'],
        'payroll' => ['view', 'edit', 'delete'],
        'reimbursements' => ['view', 'edit', 'delete'],
        'users' => ['view', 'create', 'edit', 'delete'],
        'departments' => ['view', 'create', 'edit', 'delete'],
        'access' => ['view', 'edit'],
        'security' => ['view', 'edit'],
        'delegation' => ['view', 'edit'],
        'all' => ['view', 'edit', 'delete']
    ],
    'admin' => [
        'employees' => ['view', 'edit', 'delete'],
        'payroll' => ['view', 'edit'],
        'reimbursements' => ['view', 'edit'],
        'users' => ['view', 'create', 'edit'],
        'departments' => ['view', 'create', 'edit'],
        'access' => ['view', 'edit'],
        'security' => ['view'],
        'delegation' => ['view', 'edit'],
        'all' => ['view', 'edit']
    ],
    'user' => [
        'employees' => ['view'],
        'reimbursements' => ['view', 'edit'],
        'payroll' => ['view'],
    ]
];

/**
 * Checks if the current user has permission for a specific action on a module.
 * @param string $module The module name (e.g., 'employees', 'payroll').
 * @param string $action The action name (e.g., 'view', 'edit', 'delete').
 * @return bool True if the user has permission, false otherwise.
 */
function checkPermission($module, $action) {
    global $permissionMap; // Removed $userManager as it's not needed here
    $userRole = $_SESSION['user_role'] ?? 'user';
    $userPermissions = $_SESSION['user_permissions'] ?? [];

    // CORRECTED: Fixed role name to 'super-admin'
    if ($userRole === 'super-admin') {
        return true; // Super admins have all permissions
    }

    // Check for role-based permissions first
    if (isset($permissionMap[$userRole][$module]) && in_array($action, $permissionMap[$userRole][$module])) {
        return true;
    }

    // Then, check for custom permissions (from the database)
    if (isset($userPermissions[$module]) && in_array($action, $userPermissions[$module])) {
        return true;
    }

    // As a fallback, check for 'all' permission if module-specific isn't set.
    if (isset($permissionMap[$userRole]['all']) && in_array($action, $permissionMap[$userRole]['all'])) {
        return true;
    }

    return false;
}

/**
 * Recursively sanitizes input data.
 * @param mixed $data The input data.
 * @return mixed The sanitized data.
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim((string)$data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Sends a structured JSON response and terminates the script.
 * @param bool $success Whether the operation was successful.
 * @param string $message A message for the client.
 * @param array $data Additional data to include in the response.
 */
function sendJsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit;
}

// 5. Route the action to the appropriate Manager method
try {
    $action = $_REQUEST['action'] ?? '';
    
    // Define a map of actions to their corresponding modules and types
    $actionMap = [
        'get_employees' => ['module' => 'employees', 'action' => 'view'],
        'get_employees_by_ids' => ['module' => 'employees', 'action' => 'view'],
        'get_employee_full_profile' => ['module' => 'employees', 'action' => 'view'],
        'get_employee_with_documents' => ['module' => 'employees', 'action' => 'view'],
        'add_employee' => ['module' => 'employees', 'action' => 'create'],
        'update_employee' => ['module' => 'employees', 'action' => 'edit'],
        'update_employee_status' => ['module' => 'employees', 'action' => 'edit'],
        'delete_employee' => ['module' => 'employees', 'action' => 'delete'],
        'bulk_delete_employees' => ['module' => 'employees', 'action' => 'delete'],
        'bulk_quick_edit_employees' => ['module' => 'employees', 'action' => 'edit'],
        'bulk_update_status' => ['module' => 'employees', 'action' => 'edit'],
        'get_employee_notes' => ['module' => 'employees', 'action' => 'view'],
        'add_employee_note' => ['module' => 'employees', 'action' => 'create'],
        'update_employee_note' => ['module' => 'employees', 'action' => 'edit'],
        'toggle_employee_note_pin' => ['module' => 'employees', 'action' => 'edit'],
        'delete_employee_note' => ['module' => 'employees', 'action' => 'delete'],

        'get_departments' => ['module' => 'departments', 'action' => 'view'],
        'add_department' => ['module' => 'departments', 'action' => 'create'],
        'update_department' => ['module' => 'departments', 'action' => 'edit'],
        'delete_department' => ['module' => 'departments', 'action' => 'delete'],
        
        'get_reimbursements_paginated' => ['module' => 'reimbursements', 'action' => 'view'],
        'get_reimbursement_categories' => ['module' => 'reimbursements', 'action' => 'view'],
        'get_reimbursement_claim_details' => ['module' => 'reimbursements', 'action' => 'view'],
        'submit_reimbursement_claim' => ['module' => 'reimbursements', 'action' => 'create'],
        'update_reimbursement_claim' => ['module' => 'reimbursements', 'action' => 'edit'],
        'update_reimbursement_claim_status' => ['module' => 'reimbursements', 'action' => 'edit'],
        
        'get_last_finalized_payroll_period' => ['module' => 'payroll', 'action' => 'view'],
        'get_payroll_templates' => ['module' => 'payroll', 'action' => 'view'],
        'save_payroll_template' => ['module' => 'payroll', 'action' => 'create'],
        'delete_payroll_template' => ['module' => 'payroll', 'action' => 'delete'],
        'test_run_payroll' => ['module' => 'payroll', 'action' => 'view'],
        'generate_payroll' => ['module' => 'payroll', 'action' => 'create'],
        'get_payroll_details' => ['module' => 'payroll', 'action' => 'view'],
        'update_payslip_status' => ['module' => 'payroll', 'action' => 'edit'],
        'get_payroll_history' => ['module' => 'payroll', 'action' => 'view'],
        'get_payslip_details' => ['module' => 'payroll', 'action' => 'view'],
        'finalize_payroll' => ['module' => 'payroll', 'action' => 'edit'],
        'delete_payroll' => ['module' => 'payroll', 'action' => 'delete'],
        'regenerate_payroll' => ['module' => 'payroll', 'action' => 'edit'],
        'bulk_add_payment' => ['module' => 'payroll', 'action' => 'create'],
        'bulk_add_deduction' => ['module' => 'payroll', 'action' => 'create'],
        
        'create_user' => ['module' => 'users', 'action' => 'create'],
        'get_all_users' => ['module' => 'users', 'action' => 'view'],
        'get_registered_users' => ['module' => 'users', 'action' => 'view'],
        'get_user_permissions' => ['module' => 'users', 'action' => 'view'],
        'update_user_access' => ['module' => 'users', 'action' => 'edit'],
        'refresh_user_permissions' => ['module' => 'users', 'action' => 'view'],

        // Finance actions
        'get_tax_slabs' => ['module' => 'finance', 'action' => 'view'],
        'add_tax_slab' => ['module' => 'finance', 'action' => 'create'],
        'update_tax_slab' => ['module' => 'finance', 'action' => 'edit'],
        'delete_tax_slab' => ['module' => 'finance', 'action' => 'delete'],
        'get_financial_transaction_types' => ['module' => 'finance', 'action' => 'view'],
        'get_employee_financial_summary' => ['module' => 'finance', 'action' => 'view'],
        'add_loan_advance' => ['module' => 'finance', 'action' => 'create'],
        'add_deduction' => ['module' => 'finance', 'action' => 'create'],
        'add_payment' => ['module' => 'finance', 'action' => 'create'],
        'get_reimbursement_categories' => ['module' => 'finance', 'action' => 'view'],
        
        // User profile actions which do not require module-specific permissions
        'get_user_profile' => ['module' => 'security', 'action' => 'view'],
        'update_user_profile' => ['module' => 'security', 'action' => 'edit'],
        'change_password' => ['module' => 'security', 'action' => 'edit'],
        'upload_business_logo' => ['module' => 'security', 'action' => 'edit'],
    ];

    $module = $actionMap[$action]['module'] ?? 'unknown';
    $actionType = $actionMap[$action]['action'] ?? 'view';

    // Now check if the user has permission.
    if (!checkPermission($module, $actionType)) {
        // Allow a few public actions without a strict check (e.g., login, logout)
        if (!in_array($action, ['login', 'logout'])) {
            sendJsonResponse(false, 'You do not have permission to perform this action.');
        }
    }
    
    switch ($action) {
        // --- User Profile Management (UPDATED) ---
        case 'get_user_profile':
            $userId = $_SESSION['user_id'];
            if (!$userId) { sendJsonResponse(false, 'User ID not found in session.'); }
            
            // Corrected: Using getUserById from UserManager, which you should add to your class
            $user = $userManager->getUserById($userId);
            
            if ($user) { 
                // Remove sensitive data
                unset($user['password']);
                // Ensure permissions are a decoded array
                if (isset($user['permissions']) && is_string($user['permissions'])) {
                    $user['permissions'] = json_decode($user['permissions'], true);
                }
                sendJsonResponse(true, 'User profile fetched successfully.', ['user' => $user]);
            } else { 
                sendJsonResponse(false, 'User not found.'); 
            }
            break;
            
        case 'get_tax_slabs':
            if (!checkPermission('finance', 'view')) {
                sendJsonResponse(false, 'Unauthorized to view tax slabs.');
            }
            $taxSlabs = $taxManager->getTaxSlabs();
            if ($taxSlabs === false) {
                sendJsonResponse(false, 'Could not retrieve tax slab data from database.');
            }
            sendJsonResponse(true, 'Tax slabs fetched.', ['tax_slabs' => $taxSlabs]);
            break;
            
        case 'update_user_profile':
            // ... (rest of update_user_profile code, which seems fine) ...
            $userId = $_SESSION['user_id'];
            if (!$userId) { sendJsonResponse(false, 'User ID not found in session.'); }
            $userData = [
                'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
                'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
                'email' => sanitizeInput($_POST['email'] ?? ''),
                'mobile_number' => sanitizeInput($_POST['mobile_number'] ?? ''),
                'business_name' => sanitizeInput($_POST['business_name'] ?? ''),
                'business_type' => sanitizeInput($_POST['business_type'] ?? ''),
                'business_reg' => sanitizeInput($_POST['business_reg'] ?? ''), // Corrected field name
                'business_country' => sanitizeInput($_POST['business_country'] ?? ''),
            ];
            $userData['name'] = trim($userData['first_name'] . ' ' . $userData['last_name']);
            if ($userManager->updateUserProfile($userId, $userData)) {
                // Update session variables to reflect changes immediately
                $_SESSION['user_name'] = $userData['name'];
                sendJsonResponse(true, 'Profile updated successfully.');
            } else {
                sendJsonResponse(false, 'Failed to update profile.');
            }
            break;
        
        case 'change_password':
            // ... (change password code, which seems fine) ...
            $userId = $_SESSION['user_id'];
            if (!$userId) { sendJsonResponse(false, 'User ID not found in session.'); }

            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword)) {
                sendJsonResponse(false, 'Current password and new password are required.');
            }

            // Get the user's current hashed password from the database
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($currentPassword, $user['password'])) {
                // Passwords match, proceed to update
                $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateStmt->execute([$newPasswordHash, $userId]);
                sendJsonResponse(true, 'Password updated successfully.');
            } else {
                sendJsonResponse(false, 'Incorrect current password.');
            }
            break;
            
        case 'upload_business_logo':
            // ... (upload_business_logo code, which seems fine) ...
            $user_id = $_SESSION['user_id'];
            if (!$user_id) { sendJsonResponse(false, 'User ID not found in session.'); }
            if (!isset($_FILES['business_logo']) || $_FILES['business_logo']['error'] !== UPLOAD_ERR_OK) {
                sendJsonResponse(false, 'No file uploaded or file upload error occurred.');
            }
            $file = $_FILES['business_logo'];
            $uploadDir = 'uploads/logos/';
            $fileName = uniqid('logo_') . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $targetPath = $uploadDir . $fileName;
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $stmt = $pdo->prepare("UPDATE users SET business_logo = ? WHERE id = ?");
                $success = $stmt->execute([$targetPath, $user_id]);
                if ($success) {
                    sendJsonResponse(true, 'Logo uploaded and profile updated successfully.', ['logo_url' => $targetPath]);
                } else {
                    unlink($targetPath);
                    sendJsonResponse(false, 'Failed to update profile with new logo.');
                }
            } else {
                sendJsonResponse(false, 'Failed to move the uploaded file.');
            }
            break;
            
        case 'refresh_user_permissions':
            // ... (refresh_user_permissions code, which seems fine) ...
            $userId = $_SESSION['user_id'];
            if (!$userId) { sendJsonResponse(false, 'User ID not found in session.'); }
            
            if ($userManager->refreshUserSessionPermissions($userId)) {
                sendJsonResponse(true, 'User permissions refreshed successfully.', [
                    'role' => $_SESSION['user_role'],
                    'permissions' => $_SESSION['user_permissions']
                ]);
            } else {
                sendJsonResponse(false, 'Failed to refresh user permissions.');
            }
            break;
            
        // --- Employee Data Management (handled by EmployeeManager) ---
        // CORRECTED: Permission check for add_employee now correctly uses 'super-admin'
        case 'add_employee':
            if (!checkPermission('employees', 'create')) { sendJsonResponse(false, 'You do not have permission to perform this action.'); }
            // ... (rest of add_employee code, which seems fine) ...
            $employeeData = [
                'full_name' => sanitizeInput($_POST['full_name'] ?? ''),
                'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
                'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
                'designation' => sanitizeInput($_POST['designation'] ?? ''),
                'department_id' => (int)($_POST['department_id'] ?? 0),
                'date_of_joining' => sanitizeInput($_POST['date_of_joining'] ?? ''),
                'date_of_birth' => sanitizeInput($_POST['date_of_birth'] ?? ''),
                'contact_email' => sanitizeInput($_POST['contact_email'] ?? ''),
                'contact_mobile' => sanitizeInput($_POST['contact_mobile'] ?? ''),
                'employee_status' => sanitizeInput($_POST['employee_status'] ?? 'active'),
                'address' => sanitizeInput($_POST['address'] ?? ''),
                'country' => sanitizeInput($_POST['country'] ?? ''),
                'citizenship' => sanitizeInput($_POST['citizenship'] ?? ''),
                'identity_card_number' => sanitizeInput($_POST['identity_card_number'] ?? ''),
                'tax_payer_id' => sanitizeInput($_POST['tax_payer_id'] ?? ''),
                'basic_salary' => (float)($_POST['basic_salary'] ?? 0),
                'currency' => sanitizeInput($_POST['currency'] ?? 'PKR'),
                'increment_percentage' => (float)($_POST['increment_percentage'] ?? 0),
                'overtime_rate_multiplier' => (float)($_POST['overtime_rate_multiplier'] ?? 1),
                'confirmed_salary' => (float)($_POST['confirmed_salary'] ?? 0),
                'bank_name' => sanitizeInput($_POST['bank_name'] ?? ''),
                'bank_iban' => sanitizeInput($_POST['bank_iban'] ?? ''),
                'account_title' => sanitizeInput($_POST['account_title'] ?? ''),
                'branch_code' => sanitizeInput($_POST['branch_code'] ?? ''),
                'emergency_contact_name' => sanitizeInput($_POST['emergency_contact_name'] ?? ''),
                'emergency_contact_relationship' => sanitizeInput($_POST['emergency_contact_relationship'] ?? ''),
                'emergency_contact_phone' => sanitizeInput($_POST['emergency_contact_phone'] ?? ''),
                'emergency_contact_address' => sanitizeInput($_POST['emergency_contact_address'] ?? ''),
                'housing_allowance' => (float)($_POST['housing_allowance'] ?? 0),
                'transportation_allowance' => (float)($_POST['transportation_allowance'] ?? 0),
                'cost_of_living' => (float)($_POST['cost_of_living'] ?? 0),
                'fuel_allowance' => (float)($_POST['fuel_allowance'] ?? 0),
                'telephone_allowance' => (float)($_POST['telephone_allowance'] ?? 0),
                'food_allowance' => (float)($_POST['food_allowance'] ?? 0),
                'conveyance_allowance' => (float)($_POST['conveyance_allowance'] ?? 0),
            ];
            if (empty($employeeData['full_name'])) {
                $employeeData['full_name'] = trim($employeeData['first_name'] . ' ' . $employeeData['last_name']);
            }
            $dependents = [];
            foreach ($_POST['dependent'] ?? [] as $key => $dependent) {
                if (!empty($dependent['name'])) {
                    $dependents[] = [
                        'name' => sanitizeInput($dependent['name']),
                        'relationship' => sanitizeInput($dependent['relationship']),
                        'occupation' => sanitizeInput($dependent['occupation']),
                        'dob' => sanitizeInput($dependent['dob']),
                    ];
                }
            }
            $employeeData['dependents'] = $dependents;
            $employeeId = $employeeManager->addEmployee($employeeData);
            $message = 'Employee added successfully!';
            if (!$employeeId) { throw new Exception('Operation failed: Could not retrieve employee ID after add/update.'); }
            if ($employeeId) {
                $documentsToProcess = json_decode($_POST['documents_data'] ?? '[]', true);
                $files = $_FILES['documents_files'] ?? [];
                foreach ($documentsToProcess as $doc) {
                    $docId = (int)($doc['id'] ?? 0);
                    $fileKey = $doc['file_key'] ?? null;
                    $isDeleted = (bool)($doc['is_deleted'] ?? false);
                    $isNew = ($docId === 0);
                    if ($isDeleted) {
                        if (!$isNew) {
                            $employeeManager->deleteDocument($docId);
                        }
                    } else {
                        $fileData = $files['tmp_name'][$fileKey] ?? null;
                        if ($fileData) {
                            $file = [
                                'name' => $files['name'][$fileKey],
                                'type' => $files['type'][$fileKey],
                                'tmp_name' => $files['tmp_name'][$fileKey],
                                'error' => $files['error'][$fileKey],
                                'size' => $files['size'][$fileKey],
                            ];
                            $employeeManager->addDocument($employeeId, $doc['category'], $doc['title'], $file);
                        }
                    }
                }
            }
            sendJsonResponse(true, $message, ['id' => $employeeId]);
            break;
            
        case 'update_employee':
            if (!checkPermission('employees', 'edit')) { sendJsonResponse(false, 'You do not have permission to perform this action.'); }
            // ... (rest of update_employee logic, which seems fine) ...
            $employeeId = (int)($_POST['id'] ?? 0);
            $isUpdate = ($action === 'update_employee' && $employeeId > 0);
            $employeeData = [
                'full_name' => sanitizeInput($_POST['full_name'] ?? ''),
                'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
                'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
                'designation' => sanitizeInput($_POST['designation'] ?? ''),
                'department_id' => (int)($_POST['department_id'] ?? 0),
                'date_of_joining' => sanitizeInput($_POST['date_of_joining'] ?? ''),
                'date_of_birth' => sanitizeInput($_POST['date_of_birth'] ?? ''),
                'contact_email' => sanitizeInput($_POST['contact_email'] ?? ''),
                'contact_mobile' => sanitizeInput($_POST['contact_mobile'] ?? ''),
                'employee_status' => sanitizeInput($_POST['employee_status'] ?? 'active'),
                'address' => sanitizeInput($_POST['address'] ?? ''),
                'country' => sanitizeInput($_POST['country'] ?? ''),
                'citizenship' => sanitizeInput($_POST['citizenship'] ?? ''),
                'identity_card_number' => sanitizeInput($_POST['identity_card_number'] ?? ''),
                'tax_payer_id' => sanitizeInput($_POST['tax_payer_id'] ?? ''),
                'basic_salary' => (float)($_POST['basic_salary'] ?? 0),
                'currency' => sanitizeInput($_POST['currency'] ?? 'PKR'),
                'increment_percentage' => (float)($_POST['increment_percentage'] ?? 0),
                'overtime_rate_multiplier' => (float)($_POST['overtime_rate_multiplier'] ?? 1),
                'confirmed_salary' => (float)($_POST['confirmed_salary'] ?? 0),
                'bank_name' => sanitizeInput($_POST['bank_name'] ?? ''),
                'bank_iban' => sanitizeInput($_POST['bank_iban'] ?? ''),
                'account_title' => sanitizeInput($_POST['account_title'] ?? ''),
                'branch_code' => sanitizeInput($_POST['branch_code'] ?? ''),
                'emergency_contact_name' => sanitizeInput($_POST['emergency_contact_name'] ?? ''),
                'emergency_contact_relationship' => sanitizeInput($_POST['emergency_contact_relationship'] ?? ''),
                'emergency_contact_phone' => sanitizeInput($_POST['emergency_contact_phone'] ?? ''),
                'emergency_contact_address' => sanitizeInput($_POST['emergency_contact_address'] ?? ''),
                'housing_allowance' => (float)($_POST['housing_allowance'] ?? 0),
                'transportation_allowance' => (float)($_POST['transportation_allowance'] ?? 0),
                'cost_of_living' => (float)($_POST['cost_of_living'] ?? 0),
                'fuel_allowance' => (float)($_POST['fuel_allowance'] ?? 0),
                'telephone_allowance' => (float)($_POST['telephone_allowance'] ?? 0),
                'food_allowance' => (float)($_POST['food_allowance'] ?? 0),
                'conveyance_allowance' => (float)($_POST['conveyance_allowance'] ?? 0),
            ];
            if (empty($employeeData['full_name'])) {
                $employeeData['full_name'] = trim($employeeData['first_name'] . ' ' . $employeeData['last_name']);
            }
            $dependents = [];
            foreach ($_POST['dependent'] ?? [] as $key => $dependent) {
                if (!empty($dependent['name'])) {
                    $dependents[] = [
                        'name' => sanitizeInput($dependent['name']),
                        'relationship' => sanitizeInput($dependent['relationship']),
                        'occupation' => sanitizeInput($dependent['occupation']),
                        'dob' => sanitizeInput($dependent['dob']),
                    ];
                }
            }
            $employeeData['dependents'] = $dependents;
            $employeeManager->updateEmployee($employeeId, $employeeData);
            $message = 'Employee updated successfully!';
            if (!$employeeId) { throw new Exception('Operation failed: Could not retrieve employee ID after add/update.'); }
            if ($employeeId) {
                $documentsToProcess = json_decode($_POST['documents_data'] ?? '[]', true);
                $files = $_FILES['documents_files'] ?? [];
                foreach ($documentsToProcess as $doc) {
                    $docId = (int)($doc['id'] ?? 0);
                    $fileKey = $doc['file_key'] ?? null;
                    $isDeleted = (bool)($doc['is_deleted'] ?? false);
                    $isNew = ($docId === 0);
                    if ($isDeleted) {
                        if (!$isNew) {
                            $employeeManager->deleteDocument($docId);
                        }
                    } else {
                        $fileData = $files['tmp_name'][$fileKey] ?? null;
                        if ($fileData) {
                            $file = [
                                'name' => $files['name'][$fileKey],
                                'type' => $files['type'][$fileKey],
                                'tmp_name' => $files['tmp_name'][$fileKey],
                                'error' => $files['error'][$fileKey],
                                'size' => $files['size'][$fileKey],
                            ];
                            $employeeManager->addDocument($employeeId, $doc['category'], $doc['title'], $file);
                        }
                    }
                }
            }
            sendJsonResponse(true, $message, ['id' => $employeeId]);
            break;
            
        // ... (other cases, which seem fine) ...

        // --- NEW: User Management and Access Control ---
        // CORRECTED: Changed role name in permission check
        case 'create_user':
            if (!checkPermission('users', 'create')) {
                sendJsonResponse(false, 'Unauthorized to create users.');
            }
            
            $employeeId = (int)($_POST['employee_id'] ?? 0);
            $email = sanitizeInput($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = sanitizeInput($_POST['role'] ?? '');
            
            if (!$employeeId || empty($email) || empty($password) || empty($role)) {
                sendJsonResponse(false, 'Missing required fields: employee ID, email, password, or role.');
            }
            
            // Get user's full name from employee data
            $employee = $employeeManager->getEmployeeFullProfile($employeeId);
            if (!$employee) {
                sendJsonResponse(false, 'Invalid employee ID provided.');
            }
            $fullName = $employee['full_name'];

            // Check if user with this email or employee ID already exists
            if ($userManager->getUserByEmail($email) || $userManager->getUserByEmployeeId($employeeId)) {
                sendJsonResponse(false, 'A user already exists with this email or employee ID.');
            }
            
            // Set default permissions based on role
            $permissions = [];
            switch ($role) {
                case 'finance': $permissions = ['reimbursements' => ['view', 'edit'], 'tax-slabs' => ['view', 'edit'], 'invoicing' => ['view', 'edit'], 'accounting' => ['view', 'edit'], 'inventory' => ['view', 'edit'], 'purchase' => ['view', 'edit'], 'expenses' => ['view', 'edit'], 'audit' => ['view', 'edit'], 'tax-filing' => ['view', 'edit'], 'bookkeeping' => ['view', 'edit'], 'vendors' => ['view', 'edit']]; break;
                case 'hr': $permissions = ['employees' => ['view', 'edit'], 'payroll' => ['view', 'edit'], 'departments' => ['view', 'edit'], 'attendance' => ['view', 'edit'], 'recruitment' => ['view', 'edit'], 'final-settlement' => ['view', 'edit']]; break;
                case 'productivity': $permissions = ['timesheet' => ['view', 'edit'], 'project' => ['view', 'edit'], 'documents' => ['view', 'edit'], 'approval' => ['view', 'edit'], 'knowledge' => ['view', 'edit'], 'calendar' => ['view', 'edit']]; break;
                case 'sales': $permissions = ['contacts' => ['view', 'edit']]; break;
                case 'user': $permissions = []; break;
            }
            $permissionsJson = json_encode($permissions);

            // Pass the employee's first and last name to the createUser method
            $result = $userManager->createUserWithEmployeeId($employeeId, $email, $employee['first_name'], $employee['last_name'], $password, $role, $permissionsJson);

            if ($result) {
                sendJsonResponse(true, 'User created successfully.');
            } else {
                sendJsonResponse(false, 'Failed to create user.');
            }
            break;

        case 'get_all_users':
            // CORRECTED: Changed role name in permission check
            if (!checkPermission('access', 'view')) {
                sendJsonResponse(false, 'Unauthorized to view users.');
            }
            $users = $userManager->getAllUsers();
            sendJsonResponse(true, 'Users fetched successfully.', ['users' => $users]);
            break;
            
        case 'get_registered_users':
            // CORRECTED: Changed role name in permission check
            if (!checkPermission('access', 'view')) {
                sendJsonResponse(false, 'Unauthorized to view registered users.');
            }
            $users = $userManager->getRegisteredUsers();
            sendJsonResponse(true, 'Registered users fetched successfully.', ['users' => $users]);
            break;

        case 'get_user_permissions':
            // CORRECTED: Changed role name in permission check
            if (!checkPermission('access', 'view')) {
                sendJsonResponse(false, 'Unauthorized to view user permissions.');
            }
            $userId = (int)($_GET['user_id'] ?? 0);
            if (!$userId) {
                sendJsonResponse(false, 'User ID is required.');
            }
            $user = $userManager->getUserProfile($userId);
            if ($user) {
                sendJsonResponse(true, 'User permissions fetched successfully.', [
                    'role' => $user['role'],
                    'permissions' => json_decode($user['permissions'], true)
                ]);
            } else {
                sendJsonResponse(false, 'User not found.');
            }
            break;
            
        case 'update_user_access':
            // CORRECTED: Changed role name in permission check
            if (!checkPermission('access', 'edit')) {
                sendJsonResponse(false, 'Unauthorized to update user access.');
            }

            $userId = (int)($_POST['user_id'] ?? 0);
            $newRole = sanitizeInput($_POST['new_role'] ?? '');
            $permissionsData = json_decode($_POST['permissions'] ?? '[]', true);

            if (!$userId || empty($newRole)) {
                sendJsonResponse(false, 'User ID and new role are required.');
            }

            $parsedPermissions = [];
            foreach ($permissionsData as $permission) {
                if (isset($permission['app_id']) && isset($permission['access_type'])) {
                    $appId = sanitizeInput($permission['app_id']);
                    $accessType = sanitizeInput($permission['access_type']);
                    
                    if ($accessType === 'edit') {
                        $parsedPermissions[$appId] = ['view', 'edit'];
                    } else if ($accessType === 'view') {
                        $parsedPermissions[$appId] = ['view'];
                    }
                }
            }
            $permissionsJson = json_encode($parsedPermissions);

            $result = $userManager->updateUserAccess($userId, $newRole, $permissionsJson);

            if ($result) {
                // Check if the updated user is the currently logged-in user
                if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                    // Update the session with the new role and permissions
                    $_SESSION['user_role'] = $newRole;
                    $_SESSION['user_permissions'] = $parsedPermissions;
                    // If the user's own role/permissions were updated, we need to signal a page refresh
                    sendJsonResponse(true, 'User access updated successfully. Refreshing page...', ['refresh' => true]);
                } else {
                    sendJsonResponse(true, 'User access updated successfully.');
                }
            } else {
                sendJsonResponse(false, 'Failed to update user access.');
            }
            break;

        // --- Default / Fallback for unknown actions ---
        default:
            $response['message'] = 'Unknown API action: ' . $action;
            echo json_encode($response);
            break;
    }
} catch (Exception $e) {
    error_log("Global API Exception: " . $e->getMessage() . " on action " . $action);
    $response['message'] = 'An unexpected server error occurred: ' . $e->getMessage();
    echo json_encode($response);
    exit;
}