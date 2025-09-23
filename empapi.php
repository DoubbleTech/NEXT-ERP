<?php
// api.php

// 1. Session Start & Authentication Check (Minimal for API, relies on frontend redirect)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Basic API authentication: If user_id is not set in session, deny access.
// More granular API key or token-based authentication might be needed for a real production API.
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

// 2. Include Configuration & Manager Classes
require_once 'config.php'; // Contains $pdo database connection
require_once 'employeemanager.php'; // Contains the EmployeeManager class

// Set default response header
header('Content-Type: application/json');

// Initialize response array
$response = ['success' => false, 'message' => 'Invalid request.'];

// Instantiate EmployeeManager
try {
    $employeeManager = new EmployeeManager($pdo);
} catch (PDOException $e) {
    $response['message'] = 'Database connection error: ' . $e->getMessage();
    echo json_encode($response);
    exit;
}

// 3. CSRF Token Validation for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        $response['message'] = 'CSRF token mismatch. Please refresh the page.';
        echo json_encode($response);
        exit;
    }
}

// 4. Determine Request Action
$action = $_REQUEST['action'] ?? ''; // Use $_REQUEST to handle both GET and POST actions

// Input validation helper (simplified, use more robust validation in EmployeeManager methods)
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)));
}

// Function for sending JSON response
function sendJsonResponse($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data);
    exit;
}

switch ($action) {
    // --- Employee Data Management ---
    case 'get_employees':
        // Expects 'name', 'department_id', 'status' filters as JSON strings
        $nameFilter = sanitizeInput($_GET['name'] ?? '');
        $departmentIds = json_decode($_GET['department_id'] ?? '[]', true);
        $statusFilter = json_decode($_GET['status'] ?? '[]', true);

        // Convert 'all' filter to null or empty array for backend logic
        if (is_array($departmentIds) && in_array('all', $departmentIds)) {
            $departmentIds = null;
        }
        if (is_array($statusFilter) && in_array('all', $statusFilter)) {
            $statusFilter = null;
        }

        try {
            $employees = $employeeManager->getEmployees($nameFilter, $departmentIds, $statusFilter);
            $counts = $employeeManager->getEmployeeCounts($nameFilter, $departmentIds, $statusFilter); // Separate method for counts
            sendJsonResponse(true, 'Employees fetched successfully.', ['employees' => $employees, 'counts' => $counts]);
        } catch (Exception $e) {
            sendJsonResponse(false, 'Failed to fetch employees: ' . $e->getMessage());
        }
        break;

    case 'get_employees_by_ids':
        // Used by bulk actions to get specific employee details
        $ids = json_decode($_GET['ids'] ?? '[]', true); // Expects JSON array of IDs
        if (!is_array($ids) || empty($ids)) {
            sendJsonResponse(false, 'No employee IDs provided.');
        }
        try {
            $employees = $employeeManager->getEmployeesByIds($ids);
            sendJsonResponse(true, 'Employees fetched successfully.', ['employees' => $employees]);
        } catch (Exception $e) {
            sendJsonResponse(false, 'Failed to fetch employees by IDs: ' . $e->getMessage());
        }
        break;

    case 'add_employee':
    case 'update_employee':
        $employeeId = sanitizeInput($_POST['id'] ?? null); // Will be null for add
        $isUpdate = ($action === 'update_employee' && $employeeId);

        $employeeData = [
            'full_name' => sanitizeInput($_POST['full_name'] ?? ''),
            'designation' => sanitizeInput($_POST['designation'] ?? ''),
            'department_id' => (int)($_POST['department_id'] ?? 0),
            'date_of_joining' => sanitizeInput($_POST['date_of_joining'] ?? ''),
            'identity_card_number' => sanitizeInput($_POST['identity_card_number'] ?? ''),
            'date_of_birth' => sanitizeInput($_POST['date_of_birth'] ?? ''),
            'contact_email' => sanitizeInput($_POST['contact_email'] ?? ''),
            'contact_mobile' => sanitizeInput($_POST['contact_mobile'] ?? ''),
            'employee_status' => sanitizeInput($_POST['employee_status'] ?? 'active'),
            'emergency_contact_name' => sanitizeInput($_POST['emergency_contact_name'] ?? ''),
            'emergency_contact_relationship' => sanitizeInput($_POST['emergency_contact_relationship'] ?? ''),
            'emergency_contact_phone' => sanitizeInput($_POST['emergency_contact_phone'] ?? ''),
            'dependent1_name' => sanitizeInput($_POST['dependent1_name'] ?? ''),
            'dependent1_relationship' => sanitizeInput($_POST['dependent1_relationship'] ?? ''),
            'dependent1_dob' => sanitizeInput($_POST['dependent1_dob'] ?? ''),
            'basic_salary' => (float)($_POST['basic_salary'] ?? 0),
            'currency' => sanitizeInput($_POST['currency'] ?? 'PKR'),
            'increment_percentage' => (float)($_POST['increment_percentage'] ?? 0),
            'tax_slab_id' => (int)($_POST['tax_slab_id_hidden'] ?? 0), // Use hidden field for auto-selected value
            'tax_payer_id' => sanitizeInput($_POST['tax_payer_id'] ?? ''),
            'bank_name' => sanitizeInput($_POST['bank_name'] ?? ''),
            'bank_iban' => sanitizeInput($_POST['bank_iban'] ?? ''),
            'overtime_rate_multiplier' => (float)($_POST['overtime_rate_multiplier'] ?? 1.5),
            'address' => sanitizeInput($_POST['address'] ?? ''),
            'country' => sanitizeInput($_POST['country'] ?? ''),
            'confirmed_salary' => ($_POST['employee_status'] === 'probation' ? (float)($_POST['confirmed_salary'] ?? 0) : null),
        ];

        // Handle status change reasons/dates
        $statusChangeReason = sanitizeInput($_POST['status_change_reason'] ?? '');
        $leaveStartDate = sanitizeInput($_POST['leave_start_date'] ?? null);
        $leaveEndDate = sanitizeInput($_POST['leave_end_date'] ?? null);
        $leaveRemarks = sanitizeInput($_POST['leave_remarks'] ?? null);
        $autoReactivate = isset($_POST['auto_reactivate_after_leave']) ? 1 : 0;

        // Validation (basic examples, more comprehensive validation in EmployeeManager)
        if (empty($employeeData['full_name']) || empty($employeeData['designation']) || empty($employeeData['department_id']) || empty($employeeData['date_of_joining']) || empty($employeeData['basic_salary'])) {
            sendJsonResponse(false, 'Missing required employee fields.');
        }

        try {
            $result = false;
            if ($isUpdate) {
                $result = $employeeManager->updateEmployee($employeeId, $employeeData, $statusChangeReason, $leaveStartDate, $leaveEndDate, $leaveRemarks, $autoReactivate);
                $message = 'Employee updated successfully!';
            } else {
                $newEmployeeId = $employeeManager->addEmployee($employeeData);
                $result = ($newEmployeeId !== false);
                $message = 'Employee added successfully!';
                $employeeId = $newEmployeeId; // Set employeeId for document/avatar handling
            }

            // Handle avatar upload
            if ($employeeId && isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
                $employeeManager->uploadAvatar($employeeId, $_FILES['avatar_file']);
            } elseif ($employeeId && isset($_POST['remove_avatar']) && $_POST['remove_avatar'] === '1') {
                $employeeManager->removeAvatar($employeeId);
            }

            // Handle document uploads and deletions
            if ($employeeId && isset($_POST['documents']) && is_array($_POST['documents'])) {
                foreach ($_POST['documents'] as $idx => $docData) {
                    $docId = $docData['id'] ?? null;
                    $docCategory = sanitizeInput($docData['category'] ?? '');
                    $docDescription = sanitizeInput($docData['description'] ?? '');
                    $isMandatory = isset($docData['is_mandatory']) ? (int)$docData['is_mandatory'] : 0;
                    $filePathExisting = sanitizeInput($docData['file_path_existing'] ?? '');
                    $fileNameExisting = sanitizeInput($docData['file_name_existing'] ?? '');
                    $deleteDocument = isset($docData['delete_document']) ? (int)$docData['delete_document'] : 0;
                    $customCategoryName = sanitizeInput($docData['custom_category_name'] ?? '');

                    if ($docCategory === 'Other' && !empty($customCategoryName)) {
                         $docCategory = 'Other ' . $customCategoryName;
                    }

                    $fileUpload = $_FILES["documents_files_{$idx}"] ?? null;

                    if ($deleteDocument && $docId) {
                        $employeeManager->deleteDocument($docId);
                    } elseif ($fileUpload && $fileUpload['error'] === UPLOAD_ERR_OK) {
                        if ($docId) {
                            // Update existing document with new file
                            $employeeManager->updateDocument($docId, $employeeId, $docCategory, $docDescription, $isMandatory, $fileUpload);
                        } else {
                            // Add new document
                            $employeeManager->addDocument($employeeId, $docCategory, $docDescription, $isMandatory, $fileUpload);
                        }
                    } elseif ($docId) { // Existing document with no new file uploaded, just update metadata
                        $employeeManager->updateDocumentMetadata($docId, $employeeId, $docCategory, $docDescription, $isMandatory);
                    }
                }
            }


            if ($result) {
                sendJsonResponse(true, $message);
            } else {
                sendJsonResponse(false, 'Operation failed. No changes applied.');
            }
        } catch (Exception $e) {
            sendJsonResponse(false, 'Error during employee ' . ($isUpdate ? 'update' : 'add') . ': ' . $e->getMessage());
        }
        break;

    case 'delete_employee':
        $employeeId = sanitizeInput($_POST['id'] ?? null);
        if (!$employeeId) {
            sendJsonResponse(false, 'Employee ID is required.');
        }
        try {
            $employeeManager->deleteEmployee($employeeId);
            sendJsonResponse(true, 'Employee deleted successfully.');
        } catch (Exception $e) {
            sendJsonResponse(false, 'Failed to delete employee: ' . $e->getMessage());
        }
        break;

    case 'get_departments':
        try {
            $departments = $employeeManager->getDepartments();
            sendJsonResponse(true, 'Departments fetched.', ['departments' => $departments]);
        } catch (Exception $e) {
            sendJsonResponse(false, 'Failed to fetch departments: ' . $e->getMessage());
        }
        break;

    case 'get_tax_slabs':
        try {
            $taxSlabs = $employeeManager->getTaxSlabs();
            sendJsonResponse(true, 'Tax slabs fetched.', ['tax_slabs' => $taxSlabs]);
        } catch (Exception $e) {
            sendJsonResponse(false, 'Failed to fetch tax slabs: ' . $e->getMessage());
        }
        break;

    // --- Employee Notes Management ---
    case 'get_employee_notes':
        $employeeId = sanitizeInput($_GET['employee_id'] ?? null);
        if (!$employeeId) {
            sendJsonResponse(false, 'Employee ID is required.');
        }
        try {
            $notes = $employeeManager->getEmployeeNotes($employeeId);
            sendJsonResponse(true, 'Notes fetched.', ['notes' => $notes]);
        } catch (Exception $e) {
            sendJsonResponse(false, 'Failed to fetch notes: ' . $e->getMessage());
        }
        break;

    case 'add_employee_note':
        $employeeId = sanitizeInput($_POST['employee_id'] ?? null);
        $noteText = sanitizeInput($_POST['note_text'] ?? '');
        if (!$employeeId || empty($noteText)) {
            sendJsonResponse(false, 'Employee ID and note text are required.');
        }
        try {
            $noteId = $employeeManager->addEmployeeNote($employeeId, $noteText, $_SESSION['user_id']); // Assuming user_id is the author
            sendJsonResponse(true, 'Note added successfully.', ['note_id' => $noteId]);
        } catch (Exception $e) {
            sendJsonResponse(false, 'Failed to add note: ' . $e->getMessage());
        }
        break;

    case 'update_employee_note':
        $noteId = sanitizeInput($_POST['note_id'] ?? null);
        $noteText = sanitizeInput($_POST['note_text'] ?? '');
        if (!$noteId || empty($noteText)) {
            sendJsonResponse(false, 'Note ID and note text are required.');
        }
        try {
            $employeeManager->updateEmployeeNote($noteId, $noteText);
            sendJsonResponse(true, 'Note updated successfully.');
        } catch (Exception $e) {
            sendJsonResponse(false, 'Failed to update note: ' . $e->getMessage());
        }
        break;

    case 'toggle_employee_note_pin':
        $noteId = sanitizeInput($_POST['note_id'] ?? null);
        $isPinned = (int)($_POST['is_pinned'] ?? 0); // 0 or 1
        if (!$noteId) {
            sendJsonResponse(false, 'Note ID is required.');
        }
        try {
            $employeeManager->toggleNotePinStatus($noteId, $isPinned);
            sendJsonResponse(true, $isPinned ? 'Note pinned.' : 'Note unpinned.');
        } catch (Exception $e) {
            sendJsonResponse(false, 'Failed to change note pin status: ' . $e->getMessage());
        }
        break;

    case 'delete_employee_note':
        $noteId = sanitizeInput($_POST['note_id'] ?? null);
        if (!$noteId) {
            sendJsonResponse(false, 'Note ID is required.');
        }
        try {
            $employeeManager->deleteEmployeeNote($noteId);
            sendJsonResponse(true, 'Note deleted successfully.');
        } catch (Exception $e) {
            sendJsonResponse(false, 'Failed to delete note: ' . $e->getMessage());
        }
        break;

         // --- Financial Management ---
    case 'get_financial_transaction_types':
        try {
            $types = [
                'deduction' => ['General Deduction', 'Fine', 'Loan', 'Advance', 'Other Deduction'],
                'earning' => ['Bonus', 'Allowance', 'Commission', 'Overtime Pay', 'Other Payment']
            ];
            // In a real app, these might come from a config table or database
            sendJsonResponse(true, 'Transaction types fetched.', ['types' => $types]);
        } catch (Exception $e) {
            sendJsonResponse(false, 'Failed to fetch transaction types: ' . $e->getMessage());
        }
        break;

    case 'get_employee_financial_summary':
        $employeeId = sanitizeInput($_GET['id'] ?? null);
        if (!$employeeId) {
            sendJsonResponse(false, 'Employee ID is required.');
        }
        try {
            $summary = $employeeManager->getEmployeeFinancialSummary($employeeId);
            sendJsonResponse(true, 'Financial summary fetched.', ['summary' => $summary]);
        } catch (Exception $e) {
            sendJsonResponse(false, 'Failed to fetch financial summary: ' . $e->getMessage());
        }
        break;

    case 'add_loan_advance':
        $employeeId = sanitizeInput($_POST['employee_id'] ?? null);
        $loanAdvanceType = sanitizeInput($_POST['loan_advance_type'] ?? ''); // 'Loan' or 'Advance'
        $amount = (float)($_POST['amount'] ?? 0);
        $monthlyDeductionAmount = (float)($_POST['monthly_deduction_amount'] ?? 0);
        $startDate = sanitizeInput($_POST['start_date'] ?? '');
        $notes = sanitizeInput($_POST['notes'] ?? '');

        if (!$employeeId || empty($loanAdvanceType) || $amount <= 0 || empty($startDate)) {
            sendJsonResponse(false, 'Missing required loan/advance fields or invalid amount.');
        }
        try {
            $employeeManager->addLoanAdvance($employeeId, $loanAdvanceType, $amount, $monthlyDeductionAmount, $startDate, $notes);
            sendJsonResponse(true, "$loanAdvanceType added successfully.");
        } catch (Exception $e) {
            sendJsonResponse(false, "Failed to add $loanAdvanceType: " . $e->getMessage());
        }
        break;

    case 'add_deduction':
        $employeeId = sanitizeInput($_POST['employee_id'] ?? null);
        $deductionType = sanitizeInput($_POST['deduction_type'] ?? '');
        $deductionAmount = (float)($_POST['deduction_amount'] ?? 0);
        $deductionDate = sanitizeInput($_POST['deduction_date'] ?? '');
        $remarks = sanitizeInput($_POST['remarks'] ?? '');

        if (!$employeeId || empty($deductionType) || $deductionAmount <= 0 || empty($deductionDate)) {
            sendJsonResponse(false, 'Missing required deduction fields or invalid amount.');
        }
        try {
            $employeeManager->addAdditionalTransaction($employeeId, 'deduction', $deductionType, $deductionAmount, $deductionDate, $remarks);
            sendJsonResponse(true, 'Deduction added successfully.');
        } catch (Exception $e) {
            sendJsonResponse(false, 'Failed to add deduction: ' . $e->getMessage());
        }
        break;

    case 'add_payment':
        $employeeId = sanitizeInput($_POST['employee_id'] ?? null);
        $paymentType = sanitizeInput($_POST['payment_type'] ?? '');
        $paymentAmount = (float)($_POST['payment_amount'] ?? 0);
        $paymentDate = sanitizeInput($_POST['payment_date'] ?? '');
        $remarks = sanitizeInput($_POST['remarks'] ?? '');

        if (!$employeeId || empty($paymentType) || $paymentAmount <= 0 || empty($paymentDate)) {
            sendJsonResponse(false, 'Missing required payment fields or invalid amount.');
        }
        try {
            $employeeManager->addAdditionalTransaction($employeeId, 'earning', $paymentType, $paymentAmount, $paymentDate, $remarks);
            sendJsonResponse(true, 'Payment added successfully.');
        } catch (Exception $e) {
            sendJsonResponse(false, 'Failed to add payment: ' . $e->getMessage());
        }
        break;

    case 'set_employee_leave':
        $employeeId = sanitizeInput($_POST['employee_id'] ?? null);
        $startDate = sanitizeInput($_POST['start_date'] ?? null);
        $endDate = sanitizeInput($_POST['end_date'] ?? null);
        $remarks = sanitizeInput($_POST['remarks'] ?? null);
        $autoReactivate = (int)($_POST['auto_reactivate'] ?? 0);

        if (!$employeeId || empty($startDate) || empty($endDate)) {
            sendJsonResponse(false, 'Employee ID, start date, and end date are required for leave.');
        }
        try {
            $employeeManager->setEmployeeLeave($employeeId, $startDate, $endDate, $remarks, $autoReactivate);
            sendJsonResponse(true, 'Leave details recorded and status updated.');
        } catch (Exception $e) {
            sendJsonResponse(false, 'Failed to record leave: ' . $e->getMessage());
        }
        break;

    // --- Bulk Actions ---
    case 'bulk_delete_employees':
        $employeeIds = json_decode($_POST['employee_ids'] ?? '[]', true);
        if (!is_array($employeeIds) || empty($employeeIds)) {
            sendJsonResponse(false, 'No employee IDs provided for bulk deletion.');
        }
        try {
            $employeeManager->bulkDeleteEmployees($employeeIds);
            sendJsonResponse(true, count($employeeIds) . ' employee(s) deleted successfully.');
        } catch (Exception $e) {
            sendJsonResponse(false, 'Failed to bulk delete employees: ' . $e->getMessage());
        }
        break;

    case 'bulk_add_payment':
        $employeeIds = json_decode($_POST['employee_ids'] ?? '[]', true);
        $paymentType = sanitizeInput($_POST['payment_type'] ?? '');
        $paymentAmount = (float)($_POST['payment_amount'] ?? 0);
        $remarks = sanitizeInput($_POST['remarks'] ?? '');
        $paymentDate = sanitizeInput($_POST['payment_date'] ?? '');

        if (!is_array($employeeIds) || empty($employeeIds) || empty($paymentType) || $paymentAmount <= 0 || empty($paymentDate)) {
            sendJsonResponse(false, 'Missing required fields or invalid amount for bulk payment.');
        }
        try {
            $employeeManager->bulkAddTransaction($employeeIds, 'earning', $paymentType, $paymentAmount, $paymentDate, $remarks);
            sendJsonResponse(true, 'Bulk payment added for ' . count($employeeIds) . ' employee(s) successfully.');
        } catch (Exception $e) {
            sendJsonResponse(false, 'Failed to perform bulk payment: ' . $e->getMessage());
        }
        break;

    case 'bulk_add_deduction':
        $employeeIds = json_decode($_POST['employee_ids'] ?? '[]', true);
        $deductionType = sanitizeInput($_POST['deduction_type'] ?? '');
        $deductionAmount = (float)($_POST['deduction_amount'] ?? 0);
        $remarks = sanitizeInput($_POST['remarks'] ?? '');
        $deductionDate = sanitizeInput($_POST['deduction_date'] ?? '');

        if (!is_array($employeeIds) || empty($employeeIds) || empty($deductionType) || $deductionAmount <= 0 || empty($deductionDate)) {
            sendJsonResponse(false, 'Missing required fields or invalid amount for bulk deduction.');
        }
        try {
            $employeeManager->bulkAddTransaction($employeeIds, 'deduction', $deductionType, $deductionAmount, $deductionDate, $remarks);
            sendJsonResponse(true, 'Bulk deduction added for ' . count($employeeIds) . ' employee(s) successfully.');
        } catch (Exception $e) {
            sendJsonResponse(false, 'Failed to perform bulk deduction: ' . $e->getMessage());
        }
        break;

    case 'bulk_quick_edit_employees':
        $employeesData = $_POST['employees'] ?? []; // Expects associative array like ['employees' => ['id' => [...fields]]]
        if (!is_array($employeesData) || empty($employeesData)) {
            sendJsonResponse(false, 'No employee data provided for quick edit.');
        }
        try {
            $updatedCount = $employeeManager->bulkQuickEditEmployees($employeesData);
            sendJsonResponse(true, $updatedCount . ' employee(s) updated via quick edit.');
        } catch (Exception $e) {
            sendJsonResponse(false, 'Failed to perform bulk quick edit: ' . $e->getMessage());
        }
        break;

    // --- Reporting ---
    case 'generate_report':
        $reportType = sanitizeInput($_POST['report_type'] ?? '');
        $params = $_POST['params'] ?? []; // Params for the specific report, might need specific sanitization based on type
        if (is_array($params)) {
             $params = array_map('sanitizeInput', $params);
        }

        if (empty($reportType)) {
            sendJsonResponse(false, 'Report type is required.');
        }
        try {
            $reportData = $employeeManager->generateReport($reportType, $params);
            if ($reportData !== false) {
                sendJsonResponse(true, 'Report generated successfully.', ['report_data' => $reportData]);
            } else {
                sendJsonResponse(false, 'Report generation failed or no data found.');
            }
        } catch (Exception $e) {
            sendJsonResponse(false, 'Error generating report: ' . $e->getMessage());
        }
        break;


    // --- Default / Fallback ---
    default:
        $response['message'] = 'Unknown API action.';
        echo json_encode($response);
        break;
}

// Ensure you have a proper error reporting setup for development
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

class EmployeeManager {
    private $pdo; // PDO database connection object
    private $uploadDir = __DIR__ . '/uploads/avatars/'; // Example upload directory for avatars
    private $documentsDir = __DIR__ . '/uploads/documents/'; // Example upload directory for documents

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        // Ensure upload directories exist
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0775, true);
        }
        if (!is_dir($this->documentsDir)) {
            mkdir($this->documentsDir, 0775, true);
        }
    }

    // --- Core Employee Data Management ---
    public function getEmployees($nameFilter = '', $departmentIds = null, $statusFilter = null) {
        // Implement SQL query to fetch employees based on filters
        // Example: SELECT e.*, d.department_name FROM employees e JOIN departments d ON e.department_id = d.id WHERE ...
        // Use prepared statements with LIKE for name, and IN clause for departmentIds/statusFilter (if not 'all')
        // Ensure avatar_url is correctly retrieved or generated.
        // Return structured array of employee data.
        // Placeholder data for demonstration:
        return [
            // Example employee structure expected by frontend
            [
                'id' => 1, 'employee_number' => 'EMP001', 'full_name' => 'Alice Smith',
                'designation' => 'Senior Developer', 'department_name' => 'IT',
                'employee_status' => 'active', 'date_of_joining' => '2020-01-15',
                'basic_salary' => 65000.00, 'currency' => 'PKR',
                'avatar_url' => 'https://via.placeholder.com/30x30/4CAF50/ffffff?text=AS',
                'contact_email' => 'alice@example.com', 'contact_mobile' => '03001234567',
                'identity_card_number' => '3520212345678', 'tax_payer_id' => '1234567-8',
                'increment_percentage' => 5.00, 'bank_name' => 'Bank A', 'bank_iban' => 'PK1234567890',
                'overtime_rate_multiplier' => 1.5, 'address' => '123 Tech St', 'country' => 'PK',
                'confirmed_salary' => 65000.00, // Or null if not on probation
                'emergency_contact_name' => 'Bob Smith', 'emergency_contact_relationship' => 'Husband', 'emergency_contact_phone' => '03009876543',
                'dependent1_name' => 'Child A', 'dependent1_relationship' => 'Son', 'dependent1_dob' => '2018-05-01'
            ],
            // Add more placeholder employees or query your database
        ];
    }

    public function getEmployeeCounts($nameFilter = '', $departmentIds = null, $statusFilter = null) {
        // Implement SQL queries to get counts for total, active, on_leave, resigned, terminated
        // based on the same filters as getEmployees.
        // Example: SELECT COUNT(*) AS total, SUM(CASE WHEN employee_status = 'active' THEN 1 ELSE 0 END) AS active ... FROM employees ...
        return [
            'total' => 20, 'active' => 15, 'on_leave' => 2, 'resigned' => 1, 'terminated' => 2
        ]; // Placeholder
    }

    public function getEmployeesByIds(array $ids) {
        // Fetches a list of employees by their IDs. Used for bulk actions modals.
        // Ensure you use PDO placeholders for the IN clause (e.g., implode(',', array_fill(0, count($ids), '?')))
        // Return only essential fields needed for the quick edit modal (id, full_name, employee_number, designation, department_name, basic_salary, increment_percentage, employee_status, confirmed_salary).
        return [
            // Placeholder for selected employees
            ['id' => 1, 'full_name' => 'Alice Smith', 'employee_number' => 'EMP001', 'designation' => 'Sr. Dev', 'department_name' => 'IT', 'basic_salary' => 65000, 'increment_percentage' => 5, 'employee_status' => 'active', 'confirmed_salary' => 65000],
            ['id' => 2, 'full_name' => 'Bob Johnson', 'employee_number' => 'EMP002', 'designation' => 'Designer', 'department_name' => 'Creative', 'basic_salary' => 40000, 'increment_percentage' => 0, 'employee_status' => 'probation', 'confirmed_salary' => 45000],
        ];
    }

    public function getEmployeeFullProfile($employeeId) {
        // Fetches all details for a single employee, including nested data like documents, notes, financial summary.
        // This will involve multiple queries or complex joins.
        $employee = $this->getEmployeeBasicInfo($employeeId);
        if ($employee) {
            $employee['documents'] = $this->getEmployeeDocuments($employeeId);
            $employee['notes'] = $this->getEmployeeNotes($employeeId);
            $financialSummary = $this->getEmployeeFinancialSummary($employeeId);
            $employee = array_merge($employee, $financialSummary); // Merge financial data directly into employee array
        }
        return $employee;
    }

    public function addEmployee(array $data) {
        // Validate inputs (e.g., uniqueness of email/mobile/ID card)
        // Insert into 'employees' table.
        // Generate employee_number (e.g., EMP-YYYY-XXXX)
        // Return the new employee's ID from the database.
        return 123; // Placeholder for new ID
    }

    public function updateEmployee($employeeId, array $data, $statusChangeReason = null, $leaveStartDate = null, $leaveEndDate = null, $leaveRemarks = null, $autoReactivate = 0) {
        // Validate inputs and perform uniqueness checks if relevant fields are updated.
        // Update 'employees' table.
        // Handle status changes: if status is changed, log it in a 'employee_status_history' table
        // with reason/dates. If status is 'on_leave', record leave details in a 'employee_leaves' table.
        return true; // Placeholder for success
    }

    public function deleteEmployee($employeeId) {
        // Perform cascading deletes or manual deletion from all related tables:
        // employees, employee_financials, employee_documents, employee_notes, employee_status_history, etc.
        // Also delete associated avatar and document files from the server.
        return true; // Placeholder for success
    }

    public function getDepartments() {
        // Fetch from 'departments' table
        return [['id' => 1, 'department_name' => 'IT'], ['id' => 2, 'department_name' => 'HR']]; // Placeholder
    }

    public function getTaxSlabs() {
        // Fetch from 'tax_slabs' table (using the schema you provided)
        $stmt = $this->pdo->prepare("SELECT * FROM tax_slabs ORDER BY minimum_amount ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- Avatar & Document Handling ---
    public function uploadAvatar($employeeId, array $file) {
        // Validate file type and size.
        // Generate a unique filename.
        // Move uploaded file to $this->uploadDir.
        // Update employee's 'avatar_url' in the database.
        // Handle deletion of old avatar if it exists.
        return true; // Placeholder
    }

    public function removeAvatar($employeeId) {
        // Delete avatar file from server.
        // Clear 'avatar_url' in the database for the employee.
        return true; // Placeholder
    }

    public function addDocument($employeeId, $category, $description, $isMandatory, array $file) {
        // Validate file type/size.
        // Generate unique filename and move to $this->documentsDir.
        // Insert document metadata into 'employee_documents' table.
        return true; // Placeholder
    }

    public function updateDocument($documentId, $employeeId, $category, $description, $isMandatory, array $newFile = null) {
        // Update document metadata.
        // If $newFile is provided, replace old file and update file_path.
        return true; // Placeholder
    }

    public function updateDocumentMetadata($documentId, $employeeId, $category, $description, $isMandatory) {
        // Update document metadata without changing the file.
        return true; // Placeholder
    }

    public function deleteDocument($documentId) {
        // Delete document record from DB and file from $this->documentsDir.
        return true; // Placeholder
    }

    // --- Notes Management ---
    public function getEmployeeNotes($employeeId) {
        // Fetch notes from 'employee_notes' table for specific employee, ordered by pinned then date.
        // Include author info if available.
        return [
            ['id' => 1, 'note_text' => 'Performance review scheduled.', 'created_at' => '2024-07-20 10:00:00', 'updated_at' => '2024-07-20 10:00:00', 'is_pinned' => 1],
            ['id' => 2, 'note_text' => 'Discussed project deadlines.', 'created_at' => '2024-07-25 14:30:00', 'updated_at' => '2024-07-25 14:30:00', 'is_pinned' => 0],
        ]; // Placeholder
    }

    public function addEmployeeNote($employeeId, $noteText, $authorId) {
        // Insert new note into 'employee_notes' table.
        // Return new note ID.
        return 1; // Placeholder
    }

    public function updateEmployeeNote($noteId, $noteText) {
        // Update existing note in 'employee_notes' table.
        return true; // Placeholder
    }

    public function toggleNotePinStatus($noteId, $isPinned) {
        // Update 'is_pinned' status in 'employee_notes' table.
        return true; // Placeholder
    }

    public function deleteEmployeeNote($noteId) {
        // Delete note from 'employee_notes' table.
        return true; // Placeholder
    }

    // --- Financial Management ---
    public function getEmployeeFinancialSummary($employeeId) {
        // Fetch overall financial data, loans, advances, payments, deductions, and tax info.
        // This method will likely call other private methods to get specific financial records.
        $loans = $this->getEmployeeLoans($employeeId);
        $advances = $this->getEmployeeAdvances($employeeId);
        $additionalPayments = $this->getEmployeeAdditionalPayments($employeeId);
        $additionalDeductions = $this->getEmployeeAdditionalDeductions($employeeId);

        // Example calculation of outstanding loans
        $outstandingLoansTotal = array_reduce($loans, function($sum, $loan) {
            return $sum + ($loan['remaining_balance'] ?? $loan['loan_amount']);
        }, 0);

        // Example tax calculation (integrating the function you gave previously)
        $employeeBasicInfo = $this->getEmployeeBasicInfo($employeeId); // Need basic info for salary/currency
        $annualIncome = ($employeeBasicInfo['basic_salary'] ?? 0) * 12; // Basic calculation
        $taxCalculations = $this->calculateEstimatedYearlyTax($annualIncome, $employeeBasicInfo['country'] ?? 'PK');

        // Placeholder for YTD deductions and remaining tax
        $totalDeductedYTD = 0; // Fetch from payroll records for actual deductions
        $remainingTaxToDeduct = max(0, $taxCalculations['estimated_tax'] - $totalDeductedYTD);
        $monthlyTaxDeductions = []; // Populate with actual monthly deductions from payroll

        return [
            'outstanding_loans' => $outstandingLoansTotal,
            'loans' => $loans,
            'advances' => $advances,
            'additional_payments' => $additionalPayments,
            'additional_deductions' => $additionalDeductions,
            'tax_info' => [
                'estimated_yearly_tax' => $taxCalculations['estimated_tax'],
                'tax_slab_name' => $taxCalculations['tax_slab_name'],
                'tax_slab_rate' => $taxCalculations['rate_percentage'] ?? 0, // Need to get this from slab
                'total_deducted_ytd' => $totalDeductedYTD,
                'remaining_tax_to_deduct' => $remainingTaxToDeduct,
                'monthly_deductions' => $monthlyTaxDeductions,
                'currency' => $employeeBasicInfo['currency'] ?? 'PKR'
            ]
        ];
    }

    public function calculateEstimatedYearlyTax($annualIncome, $countryCode = 'PK') {
        $stmt = $this->pdo->prepare("SELECT * FROM tax_slabs WHERE country_code = :country_code ORDER BY minimum_amount ASC");
        $stmt->execute([':country_code' => $countryCode]);
        $taxSlabs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $estimatedTax = 0.00;
        $applicableSlab = null;

        foreach ($taxSlabs as $slab) {
            $min = (float) $slab['minimum_amount'];
            $max = (float) ($slab['maximum_amount'] === null ? PHP_FLOAT_MAX : $slab['maximum_amount']); // Handle NULL for max
            $fixed = (float) $slab['fixed_amount'];
            $rate = (float) $slab['rate_percentage'];

            if ($annualIncome >= $min && $annualIncome <= $max) {
                $applicableSlab = $slab;
                $taxableAmountInSlab = $annualIncome - $min;
                // Ensure taxableAmountInSlab is not negative for the first slab if income is below min but still in range
                $taxableAmountInSlab = max(0, $taxableAmountInSlab);
                $estimatedTax = $fixed + ($taxableAmountInSlab * ($rate / 100));
                break;
            }
        }

        return [
            'estimated_tax' => round($estimatedTax, 2),
            'tax_slab_name' => $applicableSlab ? $applicableSlab['slab_name'] : 'N/A',
            'rate_percentage' => $applicableSlab ? $applicableSlab['rate_percentage'] : 0
        ];
    }

    public function getEmployeeLoans($employeeId) {
        // Fetch loans from 'employee_financial_transactions' where type = 'Loan'
        return [
            // Placeholder: ['loan_amount' => 1000, 'monthly_deduction_amount' => 100, 'remaining_balance' => 300, 'start_date' => '2023-01-01', 'status' => 'Active', 'notes' => 'Home loan']
        ];
    }

    public function getEmployeeAdvances($employeeId) {
        // Fetch advances from 'employee_financial_transactions' where type = 'Advance'
        return [
            // Placeholder: ['type' => 'Emergency', 'amount' => 500, 'deducted_amount' => 200, 'remaining_balance' => 300, 'date' => '2024-03-01', 'remarks' => 'Family emergency']
        ];
    }
    public function getEmployeeAdditionalPayments($employeeId) {
        // Fetch payments from 'employee_financial_transactions' where transaction_type = 'earning'
        return [
            // Placeholder: ['type' => 'Bonus', 'amount' => 1000, 'date' => '2023-12-25', 'remarks' => 'Annual bonus']
        ];
    }
    public function getEmployeeAdditionalDeductions($employeeId) {
        // Fetch deductions from 'employee_financial_transactions' where transaction_type = 'deduction' AND type NOT IN ('Loan', 'Advance')
        return [
            // Placeholder: ['type' => 'Fine', 'amount' => 50, 'date' => '2024-01-05', 'remarks' => 'Late submission penalty']
        ];
    }

    public function addLoanAdvance($employeeId, $type, $amount, $monthlyDeduction, $startDate, $notes) {
        // Insert into 'employee_financial_transactions' table with type 'Loan' or 'Advance'
        return true;
    }

    public function addAdditionalTransaction($employeeId, $transactionType, $type, $amount, $date, $remarks) {
        // Insert into 'employee_financial_transactions' table with transaction_type ('earning' or 'deduction')
        return true;
    }

    // --- Bulk Actions ---
    public function bulkDeleteEmployees(array $employeeIds) {
        $this->pdo->beginTransaction();
        try {
            foreach ($employeeIds as $id) {
                $this->deleteEmployee($id); // Reuse single delete logic
            }
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Bulk deletion failed: " . $e->getMessage());
        }
    }

    public function bulkAddTransaction(array $employeeIds, $transactionType, $type, $amount, $date, $remarks) {
        $this->pdo->beginTransaction();
        try {
            foreach ($employeeIds as $id) {
                // Determine if it's a loan/advance or a regular payment/deduction for the individual method call
                if ($type === 'Loan' || $type === 'Advance') {
                    $this->addLoanAdvance($id, $type, $amount, 0, $date, $remarks); // Monthly deduction might be zero for bulk
                } else {
                    $this->addAdditionalTransaction($id, $transactionType, $type, $amount, $date, $remarks);
                }
            }
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Bulk transaction failed: " . $e->getMessage());
        }
    }

    public function bulkQuickEditEmployees(array $employeesData) {
        $updatedCount = 0;
        $this->pdo->beginTransaction();
        try {
            foreach ($employeesData as $employee) {
                $id = $employee['id'];
                // Only update fields provided in the bulk edit for each employee
                $dataToUpdate = [
                    'basic_salary' => $employee['basic_salary'],
                    'increment_percentage' => $employee['increment_percentage'],
                    'employee_status' => $employee['employee_status'],
                    'confirmed_salary' => $employee['confirmed_salary'] ?? null // Include if present
                ];
                // Call a dedicated update method that handles only these specific fields
                $success = $this->updatePartialEmployeeDetails($id, $dataToUpdate);
                if ($success) {
                    $updatedCount++;
                }
            }
            $this->pdo->commit();
            return $updatedCount;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Bulk quick edit failed: " . $e->getMessage());
        }
    }

    private function updatePartialEmployeeDetails($employeeId, array $data) {
        // This is a new, simplified update method for bulk quick edit
        // Only updates the specific fields in $data, logs status changes if detected
        $setClauses = [];
        $params = [':employee_id' => $employeeId];

        if (isset($data['basic_salary'])) {
            $setClauses[] = 'basic_salary = :basic_salary';
            $params[':basic_salary'] = $data['basic_salary'];
        }
        if (isset($data['increment_percentage'])) {
            $setClauses[] = 'increment_percentage = :increment_percentage';
            $params[':increment_percentage'] = $data['increment_percentage'];
        }

        // Handle status change explicitly to log history if needed
        if (isset($data['employee_status'])) {
            // Fetch old status to detect change
            $oldStatusStmt = $this->pdo->prepare("SELECT employee_status FROM employees WHERE id = :employee_id");
            $oldStatusStmt->execute([':employee_id' => $employeeId]);
            $oldStatus = $oldStatusStmt->fetchColumn();

            if ($oldStatus !== $data['employee_status']) {
                $setClauses[] = 'employee_status = :employee_status';
                $params[':employee_status'] = $data['employee_status'];
                // Log status change:
                $this->logEmployeeStatusChange($employeeId, $oldStatus, $data['employee_status'], 'Bulk Quick Edit', null, null, null);
            }
        }

        if (isset($data['confirmed_salary'])) {
            $setClauses[] = 'confirmed_salary = :confirmed_salary';
            $params[':confirmed_salary'] = $data['confirmed_salary'];
        }


        if (empty($setClauses)) {
            return true; // Nothing to update
        }

        $sql = "UPDATE employees SET " . implode(', ', $setClauses) . " WHERE id = :employee_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    private function logEmployeeStatusChange($employeeId, $oldStatus, $newStatus, $reason, $leaveStartDate = null, $leaveEndDate = null, $leaveRemarks = null) {
        // Implement insertion into 'employee_status_history' table
        $sql = "INSERT INTO employee_status_history (employee_id, old_status, new_status, reason, change_date, leave_start_date, leave_end_date, leave_remarks)
                VALUES (:employee_id, :old_status, :new_status, :reason, NOW(), :leave_start_date, :leave_end_date, :leave_remarks)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':employee_id' => $employeeId,
            ':old_status' => $oldStatus,
            ':new_status' => $newStatus,
            ':reason' => $reason,
            ':leave_start_date' => $leaveStartDate,
            ':leave_end_date' => $leaveEndDate,
            ':leave_remarks' => $leaveRemarks
        ]);
    }


    // --- Reporting ---
    public function generateReport($reportType, array $params) {
        // This is a complex method. You'll need a switch/if-else for each report type
        // and build specific SQL queries/data processing for each.
        switch ($reportType) {
            case 'by_department':
                // Example: SELECT department_name, COUNT(*) as employee_count FROM employees JOIN departments ON employees.department_id = departments.id GROUP BY department_name;
                // If params['department_id'] is set, filter by that department.
                return [ /* Report data here */ ]; // Return array of associative arrays
            case 'by_hiring_date':
                // Example: SELECT * FROM employees WHERE date_of_joining BETWEEN :start_date AND :end_date;
                return [ /* Report data here */ ];
            case 'by_salary_range':
                // Example: SELECT * FROM employees WHERE basic_salary BETWEEN :min_salary AND :max_salary;
                return [ /* Report data here */ ];
            case 'by_service_year':
                // Example: SELECT *, TIMESTAMPDIFF(YEAR, date_of_joining, CURDATE()) as service_years FROM employees WHERE service_years BETWEEN :min_years AND :max_years;
                return [ /* Report data here */ ];
            case 'full_employee_data':
                // Example: SELECT * FROM employees JOIN departments ON ... etc. (all data)
                return [ /* Comprehensive employee data */ ];
            case 'by_status':
                 // Example: SELECT employee_status, COUNT(*) as employee_count FROM employees GROUP BY employee_status;
                 // If params['status'] is set, filter by that status.
                return [ /* Report data here */ ];
            case 'increment_due_report':
                // Logic to find employees whose last increment was X months/years ago or next increment is due by date
                return [ /* Report data here */ ];
            case 'birthday_notification':
                // Logic to find employees with birthdays in a specific month
                return [ /* Report data here */ ];
            case 'employee_advances_loans_payments_deductions':
                // Complex query to combine all financial transactions, possibly filtering by type and date range
                return [ /* Report data here */ ];
            default:
                throw new Exception("Unknown report type: " . $reportType);
        }
    }
}
?>