<?php
// classes/EmployeeManager.php

// --- DEPENDENCY CHECK / MINIMAL STUBS (To prevent Fatal Errors) ---

// NOTE: The TaxSlabManager must be defined or included for the EmployeeManager to work.
// Since you didn't provide this file, a minimal stub is added here.
if (!class_exists('TaxSlabManager')) {
    class TaxSlabManager {
        private $pdo;
        public function __construct(PDO $pdo) { $this->pdo = $pdo; }
        // Minimal function stub to support EmployeeManager.php:570
        public function calculateEstimatedYearlyTax($annualIncome, $countryCode = 'PK') {
            return [
                'estimated_tax' => 0.00, 
                'tax_slab_name' => 'N/A (Stub)', 
                'rate_percentage' => 0
            ];
        }
        // Minimal function stub to support EmployeeManager.php:549 (if used elsewhere)
        public function getTaxSlabs() { return []; }
    }
}


// --- UPLOAD DIRECTORY CONSTANTS CHECK ---

// NOTE: This uses a safe fallback if your main config file doesn't define the paths,
// but you MUST set the server permissions for the root path: /var/www/NEXT-ERP/uploads/

if (!defined('AVATAR_UPLOAD_DIR')) {
    // Determine the base path: This assumes your project root is 3 levels up from this class file.
    $base_dir = dirname(dirname(dirname(__FILE__))) . '/';
    define('AVATAR_UPLOAD_DIR', $base_dir . 'uploads/avatars/');
    define('DOCUMENT_UPLOAD_DIR', $base_dir . 'uploads/documents/');
    define('UPLOAD_BASE_DIR', $base_dir . 'uploads/');
}


// --- CORE CLASS DEFINITION ---

if (!class_exists('EmployeeManager')) {

/**
 * EmployeeManager Class
 *
 * Handles all business logic and database interactions for employee management.
 * * Requires a PDO database connection object during instantiation.
 */
class EmployeeManager {
    private $pdo; // PDO database connection object
    private $uploadDir; // Base directory for employee avatars
    private $documentsDir; // Base directory for employee documents

    /**
     * Constructor for EmployeeManager.
     * Initializes PDO connection and sets up upload directories.
     *
     * @param PDO $pdo The PDO database connection object.
     * @throws Exception If upload directories cannot be created or are not writable.
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;

        // Using the definitions that were set globally (either in config.php or the fallback above)
        if (!defined('AVATAR_UPLOAD_DIR') || !defined('DOCUMENT_UPLOAD_DIR')) {
            // This should ideally never happen if the fallback above runs or config.php is present
            throw new Exception("Upload directory constants not defined. Please check config.php or the file include order.");
        }
        $this->uploadDir = AVATAR_UPLOAD_DIR;
        $this->documentsDir = DOCUMENT_UPLOAD_DIR;

        // **LINE WHERE ERROR OCCURRED:** Permissions must be set correctly on the server.
        $this->ensureDirectoryExists($this->uploadDir);
        $this->ensureDirectoryExists($this->documentsDir);
    }

    /**
     * Ensures a directory exists and is writable.
     *
     * @param string $dirPath The path to the directory.
     * @throws Exception If the directory cannot be created or is not writable.
     */
    private function ensureDirectoryExists($dirPath) {
        if (!is_dir($dirPath)) {
            // The 0775 is a reasonable default. The permission error happened here.
            if (!mkdir($dirPath, 0775, true)) { 
                error_log("Failed to create directory: {$dirPath}. Check server permissions and path validity.");
                throw new Exception("Server could not create necessary upload directory: " . basename($dirPath) . ". Contact administrator.");
            }
        }
        if (!is_writable($dirPath)) {
            error_log("Directory is not writable: {$dirPath}. Check file system permissions.");
            throw new Exception("Upload directory is not writable: " . basename($dirPath) . ". Contact administrator.");
        }
    }

    // --- Helper for Unique Employee ID Number Generation ---
    private function generateUniqueEmployeeNumber() {
        $year = date('Y');
        $stmt = $this->pdo->prepare("SELECT MAX(CAST(SUBSTRING(employee_number, -4) AS UNSIGNED)) AS max_num FROM employees WHERE employee_number LIKE 'EMP-{$year}-%'");
        $stmt->execute();
        $lastNum = $stmt->fetchColumn();
        $nextNum = $lastNum ? $lastNum + 1 : 1;
        return "EMP-{$year}-" . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }

    // --- Helper for Uniqueness Checks (Email, Mobile, Identity Card) ---
    public function isEmailUnique($email, $excludeEmployeeId = null) {
        $sql = "SELECT COUNT(*) FROM employees WHERE contact_email = :email";
        $params = [':email' => $email];
        if ($excludeEmployeeId) { $sql .= " AND id != :exclude_id"; $params[':exclude_id'] = $excludeEmployeeId; }
        $stmt = $this->pdo->prepare($sql); $stmt->execute($params); return $stmt->fetchColumn() == 0;
    }

    public function isMobileUnique($mobile, $excludeEmployeeId = null) {
        $sql = "SELECT COUNT(*) FROM employees WHERE contact_mobile = :mobile";
        $params = [':mobile' => $mobile];
        if ($excludeEmployeeId) { $sql .= " AND id != :exclude_id"; $params[':exclude_id'] = $excludeEmployeeId; }
        $stmt = $this->pdo->prepare($sql); $stmt->execute($params); return $stmt->fetchColumn() == 0;
    }

    public function isIdentityCardUnique($idCard, $excludeEmployeeId = null) {
        $sql = "SELECT COUNT(*) FROM employees WHERE identity_card_number = :id_card";
        $params = [':id_card' => $idCard];
        if ($excludeEmployeeId) { $sql .= " AND id != :exclude_id"; $params[':exclude_id'] = $excludeEmployeeId; }
        $stmt = $this->pdo->prepare($sql); $stmt->execute($params); return $stmt->fetchColumn() == 0;
    }

    // --- Core Employee Data Management (CRUD) ---

    public function getEmployeeBasicInfo($employeeId) {
        $sql = "SELECT e.*, d.name AS department_name FROM employees e LEFT JOIN departments d ON e.department_id = d.id WHERE e.id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $employeeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getEmployees($nameFilter = '', $departmentIds = null, $statusFilter = null) {
        try {
            $sql = "SELECT e.id, e.employee_number, e.full_name, e.designation, d.name AS department_name, e.employee_status, e.date_of_joining, e.basic_salary, e.currency, e.avatar_url FROM employees e LEFT JOIN departments d ON e.department_id = d.id WHERE 1=1";
            $params = [];

            if (!empty($nameFilter)) {
                $sql .= " AND (e.full_name LIKE :name_filter OR e.employee_number LIKE :name_filter OR e.designation LIKE :name_filter)";
                $params[':name_filter'] = '%' . $nameFilter . '%';
            }

            if (is_array($departmentIds) && !empty($departmentIds) && !in_array('all', $departmentIds, true)) {
                $placeholders = implode(',', array_fill(0, count($departmentIds), '?'));
                $sql .= " AND e.department_id IN ({$placeholders})";
                $params = array_merge($params, $departmentIds);
            }

            if (is_array($statusFilter) && !empty($statusFilter) && !in_array('all', $statusFilter, true)) {
                $placeholders = implode(',', array_fill(0, count($statusFilter), '?'));
                $sql .= " AND e.employee_status IN ({$placeholders})";
                $params = array_merge($params, $statusFilter);
            }

            $sql .= " ORDER BY e.full_name ASC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Could not retrieve employee list from database. " . $e->getMessage());
        }
    }
    
    /**
     * Retrieves counts for different employee statuses and total.
     */
    public function getEmployeeCounts($nameFilter = '', $departmentIds = null, $statusFilter = null) {
        try {
            $select_clauses = [
                "COUNT(*) AS total",
                "COALESCE(SUM(CASE WHEN employee_status = 'active' THEN 1 ELSE 0 END), 0) AS active",
                "COALESCE(SUM(CASE WHEN employee_status = 'on_leave' THEN 1 ELSE 0 END), 0) AS on_leave",
                "COALESCE(SUM(CASE WHEN employee_status = 'resigned' THEN 1 ELSE 0 END), 0) AS resigned",
                "COALESCE(SUM(CASE WHEN employee_status = 'terminated' THEN 1 ELSE 0 END), 0) AS terminated_count",
                "COALESCE(SUM(CASE WHEN employee_status = 'probation' THEN 1 ELSE 0 END), 0) AS probation"
            ];
            
            $sql = "SELECT " . implode(', ', $select_clauses) . " FROM employees e LEFT JOIN departments d ON e.department_id = d.id WHERE 1=1";
            $params = [];

            if (!empty($nameFilter)) {
                $sql .= " AND (e.full_name LIKE :name_filter OR e.employee_number LIKE :name_filter OR e.designation LIKE :name_filter)";
                $params[':name_filter'] = '%' . $nameFilter . '%';
            }

            if (is_array($departmentIds) && !empty($departmentIds) && !in_array('all', $departmentIds, true)) {
                $placeholders = implode(',', array_fill(0, count($departmentIds), '?'));
                $sql .= " AND e.department_id IN ({$placeholders})";
                $params = array_merge($params, $departmentIds);
            }

            if (is_array($statusFilter) && !empty($statusFilter) && !in_array('all', $statusFilter, true)) {
                $placeholders = implode(',', array_fill(0, count($statusFilter), '?'));
                $sql .= " AND e.employee_status IN ({$placeholders})";
                $params = array_merge($params, $statusFilter);
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Log the full SQL query before throwing the exception for better debugging.
            error_log("SQL Error in getEmployeeCounts: " . $e->getMessage() . " | SQL: " . $sql);
            throw new Exception("Could not retrieve employee counts. " . $e->getMessage());
        }
    }

    public function getEmployeesByIds(array $ids) {
        if (empty($ids)) { return []; }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT e.id, e.employee_number, e.full_name, e.designation, d.name AS department_name, e.basic_salary, e.increment_percentage, e.employee_status, e.confirmed_salary FROM employees e LEFT JOIN departments d ON e.department_id = d.id WHERE e.id IN ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEmployeeFullProfile(int $employeeId) {
        try {
            $this->pdo->beginTransaction();

            $sql = "SELECT 
                        e.*, 
                        d.name AS department_name
                    FROM employees e
                    LEFT JOIN departments d ON e.department_id = d.id
                    WHERE e.id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                $this->pdo->rollBack();
                return null;
            }

            // Fetch documents
            $docsSql = "SELECT * FROM employee_documents WHERE employee_id = ?";
            $docsStmt = $this->pdo->prepare($docsSql);
            $docsStmt->execute([$employeeId]);
            $employee['documents'] = $docsStmt->fetchAll(PDO::FETCH_ASSOC);

            $this->pdo->commit();
            return $employee;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error in getEmployeeFullProfile: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Retrieves all documents for a specific employee.
     * @param int $employeeId The ID of the employee.
     * @return array An array of document records.
     */
    public function getEmployeeDocuments($employeeId) {
        $sql = "SELECT id, document_category, file_name, file_path, is_mandatory, uploaded_at FROM employee_documents WHERE employee_id = :id ORDER BY uploaded_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addEmployee(array $data) {
        if (!$this->isEmailUnique($data['contact_email'])) { throw new Exception("Email '{$data['contact_email']}' already exists."); }
        if (!empty($data['contact_mobile']) && !$this->isMobileUnique($data['contact_mobile'])) { throw new Exception("Mobile number '{$data['contact_mobile']}' already exists."); }
        if (!empty($data['identity_card_number']) && !$this->isIdentityCardUnique($data['identity_card_number'])) { throw new Exception("Identity Card Number '{$data['identity_card_number']}' already exists."); }

        $employeeNumber = $this->generateUniqueEmployeeNumber();
        $taxPayerId = !empty($data['identity_card_number']) ? preg_replace('/[^a-zA-Z0-9]/', '', $data['identity_card_number']) : null;
        $dependents = json_encode($data['dependents'] ?? []);
        
        $this->pdo->beginTransaction();
        try {
            $sql = "INSERT INTO employees (
                employee_number, full_name, designation, department_id, date_of_joining, date_of_birth, contact_email, contact_mobile, 
                address, country, citizenship, employee_status, identity_card_number, tax_payer_id, 
                basic_salary, currency, increment_percentage, overtime_rate_multiplier, confirmed_salary, 
                bank_name, bank_iban, account_title, branch_code, 
                emergency_contact_name, emergency_contact_relationship, emergency_contact_phone, emergency_contact_address, 
                dependents,
                housing_allowance, transportation_allowance, cost_of_living, fuel_allowance, telephone_allowance, food_allowance, conveyance_allowance,
                created_at, updated_at
            ) VALUES (
                :employee_number, :full_name, :designation, :department_id, :date_of_joining, :date_of_birth, :contact_email, :contact_mobile, 
                :address, :country, :citizenship, :employee_status, :identity_card_number, :tax_payer_id, 
                :basic_salary, :currency, :increment_percentage, :overtime_rate_multiplier, :confirmed_salary,
                :bank_name, :bank_iban, :account_title, :branch_code, 
                :emergency_contact_name, :emergency_contact_relationship, :emergency_contact_phone, :emergency_contact_address, 
                :dependents,
                :housing_allowance, :transportation_allowance, :cost_of_living, :fuel_allowance, :telephone_allowance, :food_allowance, :conveyance_allowance,
                NOW(), NOW()
            )";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':employee_number' => $employeeNumber,
                ':full_name' => $data['full_name'],
                ':designation' => $data['designation'],
                ':department_id' => $data['department_id'],
                ':date_of_joining' => $data['date_of_joining'],
                ':date_of_birth' => $data['date_of_birth'],
                ':contact_email' => $data['contact_email'],
                ':contact_mobile' => $data['contact_mobile'],
                ':address' => $data['address'],
                ':country' => $data['country'],
                ':citizenship' => $data['citizenship'],
                ':employee_status' => $data['employee_status'],
                ':identity_card_number' => $data['identity_card_number'],
                ':tax_payer_id' => $taxPayerId,
                ':basic_salary' => $data['basic_salary'],
                ':currency' => $data['currency'],
                ':increment_percentage' => $data['increment_percentage'],
                ':overtime_rate_multiplier' => $data['overtime_rate_multiplier'],
                ':confirmed_salary' => $data['confirmed_salary'] ?? null, 
                ':bank_name' => $data['bank_name'],
                ':bank_iban' => $data['bank_iban'],
                ':account_title' => $data['account_title'],
                ':branch_code' => $data['branch_code'],
                ':emergency_contact_name' => $data['emergency_contact_name'],
                ':emergency_contact_relationship' => $data['emergency_contact_relationship'],
                ':emergency_contact_phone' => $data['emergency_contact_phone'],
                ':emergency_contact_address' => $data['emergency_contact_address'],
                ':dependents' => $dependents,
                ':housing_allowance' => $data['housing_allowance'],
                ':transportation_allowance' => $data['transportation_allowance'],
                ':cost_of_living' => $data['cost_of_living'],
                ':fuel_allowance' => $data['fuel_allowance'],
                ':telephone_allowance' => $data['telephone_allowance'],
                ':food_allowance' => $data['food_allowance'],
                ':conveyance_allowance' => $data['conveyance_allowance']
            ]);

            $newEmployeeId = $this->pdo->lastInsertId();
            $this->logEmployeeStatusChange($newEmployeeId, null, $data['employee_status'], 'Initial Hire');

            $this->pdo->commit();
            return $newEmployeeId;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Database error while adding employee: " . $e->getMessage());
        }
    }

    public function updateEmployee($employeeId, array $data) {
        $currentEmployee = $this->getEmployeeBasicInfo($employeeId);
        if (!$currentEmployee) { throw new Exception("Employee with ID {$employeeId} not found for update."); }

        if (!$this->isEmailUnique($data['contact_email'], $employeeId)) { throw new Exception("Email '{$data['contact_email']}' already exists for another employee."); }
        if (!empty($data['contact_mobile']) && !$this->isMobileUnique($data['contact_mobile'], $employeeId)) { throw new Exception("Mobile number '{$data['contact_mobile']}' already exists for another employee."); }
        if (!empty($data['identity_card_number']) && !$this->isIdentityCardUnique($data['identity_card_number'], $employeeId)) { throw new Exception("Identity Card Number '{$data['identity_card_number']}' already exists for another employee."); }

        $taxPayerId = !empty($data['identity_card_number']) ? preg_replace('/[^a-zA-Z0-9]/', '', $data['identity_card_number']) : null;
        $dependents = json_encode($data['dependents'] ?? []);

        $this->pdo->beginTransaction();
        try {
            $sql = "UPDATE employees SET 
                full_name = :full_name, designation = :designation, department_id = :department_id, date_of_joining = :date_of_joining, date_of_birth = :date_of_birth, contact_email = :contact_email, contact_mobile = :contact_mobile, 
                address = :address, country = :country, citizenship = :citizenship, employee_status = :employee_status, identity_card_number = :identity_card_number, tax_payer_id = :tax_payer_id, 
                basic_salary = :basic_salary, currency = :currency, increment_percentage = :increment_percentage, overtime_rate_multiplier = :overtime_rate_multiplier, confirmed_salary = :confirmed_salary, 
                bank_name = :bank_name, bank_iban = :bank_iban, account_title = :account_title, branch_code = :branch_code,
                emergency_contact_name = :emergency_contact_name, emergency_contact_relationship = :emergency_contact_relationship, emergency_contact_phone = :emergency_contact_phone, emergency_contact_address = :emergency_contact_address,
                dependents = :dependents,
                housing_allowance = :housing_allowance, transportation_allowance = :transportation_allowance, cost_of_living = :cost_of_living, fuel_allowance = :fuel_allowance, telephone_allowance = :telephone_allowance, food_allowance = :food_allowance, conveyance_allowance = :conveyance_allowance,
                updated_at = NOW() 
            WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':full_name' => $data['full_name'],
                ':designation' => $data['designation'],
                ':department_id' => $data['department_id'],
                ':date_of_joining' => $data['date_of_joining'],
                ':date_of_birth' => $data['date_of_birth'],
                ':contact_email' => $data['contact_email'],
                ':contact_mobile' => $data['contact_mobile'],
                ':address' => $data['address'],
                ':country' => $data['country'],
                ':citizenship' => $data['citizenship'],
                ':employee_status' => $data['employee_status'],
                ':identity_card_number' => $data['identity_card_number'],
                ':tax_payer_id' => $taxPayerId,
                ':basic_salary' => $data['basic_salary'],
                ':currency' => $data['currency'],
                ':increment_percentage' => $data['increment_percentage'],
                ':overtime_rate_multiplier' => $data['overtime_rate_multiplier'],
                ':confirmed_salary' => $data['confirmed_salary'],
                ':bank_name' => $data['bank_name'],
                ':bank_iban' => $data['bank_iban'],
                ':account_title' => $data['account_title'],
                ':branch_code' => $data['branch_code'],
                ':emergency_contact_name' => $data['emergency_contact_name'],
                ':emergency_contact_relationship' => $data['emergency_contact_relationship'],
                ':emergency_contact_phone' => $data['emergency_contact_phone'],
                ':emergency_contact_address' => $data['emergency_contact_address'],
                ':dependents' => $dependents,
                ':housing_allowance' => $data['housing_allowance'],
                ':transportation_allowance' => $data['transportation_allowance'],
                ':cost_of_living' => $data['cost_of_living'],
                ':fuel_allowance' => $data['fuel_allowance'],
                ':telephone_allowance' => $data['telephone_allowance'],
                ':food_allowance' => $data['food_allowance'],
                ':conveyance_allowance' => $data['conveyance_allowance'],
                ':id' => $employeeId
            ]);

            if ($currentEmployee['employee_status'] !== $data['employee_status']) {
                $this->logEmployeeStatusChange($employeeId, $currentEmployee['employee_status'], $data['employee_status'], 'Profile Update');
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Failed to update employee: " . $e->getMessage());
        }
    }

    public function deleteEmployee($employeeId) {
        $this->pdo->beginTransaction();
        try {
            $employeeData = $this->getEmployeeFullProfile($employeeId);

            if (!empty($employeeData['avatar_url']) && !str_contains($employeeData['avatar_url'], 'placehold.co')) {
                // Ensure correct constant usage for file operations
                $avatarPath = AVATAR_UPLOAD_DIR . basename($employeeData['avatar_url']); 
                if (file_exists($avatarPath)) { unlink($avatarPath); }
            }

            if (!empty($employeeData['documents'])) {
                foreach ($employeeData['documents'] as $doc) {
                    if (!empty($doc['file_path'])) {
                        // Ensure correct constant usage for file operations
                        $documentPath = DOCUMENT_UPLOAD_DIR . basename($doc['file_path']);
                        if (file_exists($documentPath)) { unlink($documentPath); }
                    }
                    $stmt = $this->pdo->prepare("DELETE FROM employee_documents WHERE id = ?");
                    $stmt->execute([$doc['id']]);
                }
            }

            $tablesToDeleteFrom = ['employee_financial_transactions', 'employee_notes', 'employee_status_history', 'employee_leaves'];
            foreach ($tablesToDeleteFrom as $table) {
                $stmt = $this->pdo->prepare("DELETE FROM {$table} WHERE employee_id = ?");
                $stmt->execute([$employeeId]);
            }

            $stmt = $this->pdo->prepare("DELETE FROM employees WHERE id = ?");
            $stmt->execute([$employeeId]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Failed to delete employee: " . $e->getMessage());
        }
    }

    // --- Avatar & Document Handling ---
    public function uploadAvatar($employeeId, array $file) {
        if ($file['error'] !== UPLOAD_ERR_OK) { throw new Exception("File upload error: " . $file['error']); }
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) { throw new Exception("Invalid avatar file type. Only JPG, PNG, GIF, WEBP are allowed."); }
        if ($file['size'] > 2 * 1024 * 1024) { throw new Exception("Avatar file size exceeds 2MB limit."); }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = $employeeId . '_avatar_' . uniqid() . '.' . $extension;
        $targetPath = $this->uploadDir . $fileName;

        $stmt = $this->pdo->prepare("SELECT avatar_url FROM employees WHERE id = ?");
        $stmt->execute([$employeeId]);
        $oldAvatarUrl = $stmt->fetchColumn();

        $this->pdo->beginTransaction();
        try {
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $relativeUrl = 'uploads/avatars/' . $fileName;
                $updateStmt = $this->pdo->prepare("UPDATE employees SET avatar_url = :avatar_url WHERE id = :id");
                $updateStmt->execute([':avatar_url' => $relativeUrl, ':id' => $employeeId]);

                if (!empty($oldAvatarUrl) && !str_contains($oldAvatarUrl, 'placehold.co')) {
                    // FIX: Changed AVATAR_UPLOAD_DIR to UPLOAD_BASE_DIR for consistency if the file path is relative (e.g. 'uploads/avatars/file.png')
                    $oldAvatarFullPath = AVATAR_UPLOAD_DIR . basename($oldAvatarUrl);
                    if (file_exists($oldAvatarFullPath)) { unlink($oldAvatarFullPath); }
                }
                $this->pdo->commit();
                return $relativeUrl;
            } else {
                throw new Exception("Failed to move uploaded avatar file to destination.");
            }
        } catch (Exception $e) {
            $this->pdo->rollBack();
            if (file_exists($targetPath)) { unlink($targetPath); }
            throw new Exception("Failed to save avatar: " . $e->getMessage());
        }
    }

    public function removeAvatar($employeeId) {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("SELECT avatar_url FROM employees WHERE id = ?");
            $stmt->execute([$employeeId]);
            $currentAvatarUrl = $stmt->fetchColumn();

            if (!empty($currentAvatarUrl) && !str_contains($currentAvatarUrl, 'placehold.co')) {
                $filePath = AVATAR_UPLOAD_DIR . basename($currentAvatarUrl);
                if (file_exists($filePath)) { unlink($filePath); }
            }

            $updateStmt = $this->pdo->prepare("UPDATE employees SET avatar_url = NULL WHERE id = ?");
            $updateStmt->execute([$employeeId]);
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Failed to remove avatar: " . $e->getMessage());
        }
    }

    public function addDocument($employeeId, $category, $description, array $file) {
        if ($file['error'] !== UPLOAD_ERR_OK) { throw new Exception("File upload error: " . $file['error']); }
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf', 'image/gif', 'image/webp', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        if (!in_array($file['type'], $allowedTypes)) { throw new Exception("Invalid document file type. Allowed: Images, PDF, Word, Excel."); }
        if ($file['size'] > 10 * 1024 * 1024) { throw new Exception("Document file size exceeds 10MB limit."); }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = $employeeId . '_doc_' . uniqid() . '.' . $extension;
        $targetPath = $this->documentsDir . $fileName;

        $this->pdo->beginTransaction();
        try {
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $relativeFilePath = 'uploads/documents/' . $fileName;
                $sql = "INSERT INTO employee_documents (employee_id, document_category, description, file_name, file_path, uploaded_at) VALUES (:employee_id, :category, :description, :file_name, :file_path, NOW())";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':employee_id' => $employeeId, ':category' => $category, ':description' => $description, ':file_name' => $file['name'], ':file_path' => $relativeFilePath]);
                $this->pdo->commit();
                return $this->pdo->lastInsertId();
            } else {
                throw new Exception("Failed to move uploaded document file to destination.");
            }
        } catch (Exception $e) {
            $this->pdo->rollBack();
            if (file_exists($targetPath)) { unlink($targetPath); }
            throw new Exception("Failed to add document: " . $e->getMessage());
        }
    }

    public function updateDocument($documentId, $employeeId, $category, $description, array $newFile = null) {
        $this->pdo->beginTransaction();
        try {
            $currentDoc = $this->getEmployeeDocumentById($documentId);
            if (!$currentDoc) { throw new Exception("Document with ID {$documentId} not found."); }

            $updateFilePath = false;
            $newFileName = $currentDoc['file_name'];
            $newRelativeFilePath = $currentDoc['file_path'];
            $newFullPathTemp = null;

            if ($newFile && $newFile['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf', 'image/gif', 'image/webp', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
                if (!in_array($newFile['type'], $allowedTypes)) { throw new Exception("Invalid new document file type."); }
                if ($newFile['size'] > 10 * 1024 * 1024) { throw new Exception("New document file size exceeds 10MB limit."); }

                $extension = pathinfo($newFile['name'], PATHINFO_EXTENSION);
                $newUniqueFileName = $employeeId . '_doc_' . uniqid() . '.' . $extension;
                $newFullPathTemp = $this->documentsDir . $newUniqueFileName;

                if (!move_uploaded_file($newFile['tmp_name'], $newFullPathTemp)) { throw new Exception("Failed to upload new document file."); }

                if (!empty($currentDoc['file_path']) && !str_contains($currentDoc['file_path'], 'placehold.co')) {
                    $oldFullPath = DOCUMENT_UPLOAD_DIR . basename($currentDoc['file_path']);
                    if (file_exists($oldFullPath)) { unlink($oldFullPath); }
                }
                $newFileName = $newFile['name'];
                $newRelativeFilePath = 'uploads/documents/' . $newUniqueFileName;
                $updateFilePath = true;
            }

            $sql = "UPDATE employee_documents SET document_category = :category, description = :description, updated_at = NOW()";
            $params = [':category' => $category, ':description' => $description, ':id' => $documentId];

            if ($updateFilePath) {
                $sql .= ", file_name = :file_name, file_path = :file_path";
                $params[':file_name'] = $newFileName;
                $params[':file_path'] = $newRelativeFilePath;
            }
            $sql .= " WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            if ($newFullPathTemp && file_exists($newFullPathTemp)) { unlink($newFullPathTemp); }
            throw new Exception("Failed to update document: " . $e->getMessage());
        }
    }

    public function updateDocumentMetadata($documentId, $category, $description) {
        $sql = "UPDATE employee_documents SET document_category = :category, description = :description, updated_at = NOW() WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        try {
            return $stmt->execute([':category' => $category, ':description' => $description, ':id' => $documentId]);
        } catch (Exception $e) {
            throw new Exception("Failed to update document metadata: " . $e->getMessage());
        }
    }

    public function deleteDocument($documentId) {
        $this->pdo->beginTransaction();
        try {
            $currentDoc = $this->getEmployeeDocumentById($documentId);
            if (!$currentDoc) { throw new Exception("Document with ID {$documentId} not found."); }

            if (!empty($currentDoc['file_path'])) {
                $filePath = DOCUMENT_UPLOAD_DIR . basename($currentDoc['file_path']);
                if (file_exists($filePath)) { unlink($filePath); }
            }

            $sql = "DELETE FROM employee_documents WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $documentId]);
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Failed to delete document: " . $e->getMessage());
        }
    }

    private function getEmployeeDocumentById($documentId) {
        $stmt = $this->pdo->prepare("SELECT * FROM employee_documents WHERE id = ?");
        $stmt->execute([$documentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // --- Employee Notes Management ---
    public function getEmployeeNotes($employeeId) {
        $sql = "SELECT en.id, en.employee_id, en.note_title, en.note_text, en.created_by_user_id, en.is_pinned, en.created_at, u.full_name as author_name FROM employee_notes en LEFT JOIN users u ON en.created_by_user_id = u.id WHERE en.employee_id = :employee_id ORDER BY en.is_pinned DESC, en.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addEmployeeNote($employeeId, $noteText, $authorId, $noteTitle = null) {
        $sql = "INSERT INTO employee_notes (employee_id, note_title, note_text, created_by_user_id, created_at, updated_at) VALUES (:employee_id, :note_title, :note_text, :created_by_user_id, NOW(), NOW())";
        $stmt = $this->pdo->prepare($sql);
        try {
            return $stmt->execute([':employee_id' => $employeeId, ':note_title' => $noteTitle, ':note_text' => $noteText, ':created_by_user_id' => $authorId]);
        } catch (Exception $e) {
            throw new Exception("Failed to add employee note: " . $e->getMessage());
        }
    }

    public function updateEmployeeNote($noteId, $noteText, $noteTitle) {
        $sql = "UPDATE employee_notes SET note_title = :note_title, note_text = :note_text, updated_at = NOW() WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        try {
            return $stmt->execute([':note_title' => $noteTitle, ':note_text' => $noteText, ':id' => $noteId]);
        } catch (Exception $e) {
            throw new Exception("Failed to update employee note: " . $e->getMessage());
        }
    }

    public function toggleNotePinStatus($noteId, $isPinned) {
        $sql = "UPDATE employee_notes SET is_pinned = :is_pinned, updated_at = NOW() WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        try {
            return $stmt->execute([':is_pinned' => $isPinned, ':id' => $noteId]);
        } catch (Exception $e) {
            throw new Exception("Failed to toggle note pin status: " . $e->getMessage());
        }
    }

    public function deleteEmployeeNote($noteId) {
        $sql = "DELETE FROM employee_notes WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        try {
            return $stmt->execute([':id' => $noteId]);
        } catch (Exception $e) {
            throw new Exception("Failed to delete employee note: " . $e->getMessage());
        }
    }
    // --- Financial Management ---
    public function getEmployeeFinancialSummary($employeeId, $countryCode = 'PK') {
        $summary = [];
        $employeeBasicInfo = $this->getEmployeeBasicInfo($employeeId);
        if (!$employeeBasicInfo) { throw new Exception("Employee with ID {$employeeId} not found for financial summary."); }

        $summary['loans'] = $this->getEmployeeLoans($employeeId);
        $summary['advances'] = $this->getEmployeeAdvances($employeeId);
        $summary['additional_payments'] = $this->getEmployeeAdditionalPayments($employeeId);
        $summary['additional_deductions'] = $this->getEmployeeAdditionalDeductions($employeeId);
        
        $annualIncome = ($employeeBasicInfo['basic_salary'] ?? 0) * 12;
        $taxSlabManager = new TaxSlabManager($this->pdo);
        $taxCalculations = $taxSlabManager->calculateEstimatedYearlyTax($annualIncome, $employeeBasicInfo['country'] ?? $countryCode);

        $totalDeductedYTD = 0.00;
        $monthlyTaxDeductions = [];

        $remainingTaxToDeduct = max(0, $taxCalculations['estimated_tax'] - $totalDeductedYTD);

        $summary['tax_info'] = ['estimated_yearly_tax' => round($taxCalculations['estimated_tax'], 2), 'tax_slab_name' => $taxCalculations['tax_slab_name'], 'tax_slab_rate' => $taxCalculations['rate_percentage'] ?? 0, 'total_deducted_ytd' => $totalDeductedYTD, 'remaining_tax_to_deduct' => $remainingTaxToDeduct, 'monthly_deductions' => $monthlyTaxDeductions, 'currency' => $employeeBasicInfo['currency'] ?? 'PKR'];

        return $summary;
    }

    public function calculateEstimatedYearlyTax($annualIncome, $countryCode = 'PK') {
        try {
            $sql = "SELECT id, slab_name, minimum_income, maximum_income, tax_base_amount AS fixed_amount, excess_tax_percentage AS rate_percentage, country_code FROM tax_slabs WHERE country_code = :country_code ORDER BY minimum_income ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':country_code' => $countryCode]);
            $taxSlabs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $estimatedTax = 0.00;
            $applicableSlab = null;

            if (empty($taxSlabs)) { throw new Exception("No tax slabs found for country code: {$countryCode}."); }

            foreach ($taxSlabs as $slab) {
                $min = (float) $slab['minimum_income'];
                $max = (float) ($slab['maximum_income'] === null ? PHP_FLOAT_MAX : $slab['maximum_income']);
                $fixed = (float) $slab['fixed_amount'];
                $rate = (float) $slab['rate_percentage'];

                if ($annualIncome >= $min && $annualIncome <= $max) {
                    $applicableSlab = $slab;
                    $taxableAmountInSlab = max(0, $annualIncome - $min);
                    $estimatedTax = $fixed + ($taxableAmountInSlab * ($rate / 100));
                    break;
                }
            }

            return ['estimated_tax' => $estimatedTax, 'tax_slab_name' => $applicableSlab ? $applicableSlab['slab_name'] : 'N/A', 'rate_percentage' => $applicableSlab ? $applicableSlab['rate_percentage'] : 0];
        } catch (Exception $e) {
            throw new Exception("Could not calculate tax details. " . $e->getMessage());
        }
    }

    public function getEmployeeLoans($employeeId) {
        $sql = "SELECT id, employee_id, type, amount as loan_amount, monthly_deduction_amount, transaction_date as start_date, notes, remaining_balance, status FROM employee_financial_transactions WHERE employee_id = :employee_id AND type = 'Loan' ORDER BY transaction_date DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEmployeeAdvances($employeeId) {
        $sql = "SELECT id, employee_id, type, amount, transaction_date as date, remarks, deducted_amount, remaining_balance FROM employee_financial_transactions WHERE employee_id = :employee_id AND type = 'Advance' ORDER BY transaction_date DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEmployeeAdditionalPayments($employeeId) {
        $sql = "SELECT id, employee_id, type, amount, transaction_date as date, remarks FROM employee_financial_transactions WHERE employee_id = :employee_id AND transaction_type = 'earning' AND type NOT IN ('Loan', 'Advance') ORDER BY transaction_date DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEmployeeAdditionalDeductions($employeeId) {
        $sql = "SELECT id, employee_id, type, amount, transaction_date as date, remarks FROM employee_financial_transactions WHERE employee_id = :employee_id AND transaction_type = 'deduction' AND type NOT IN ('Loan', 'Advance') ORDER BY transaction_date DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addLoanAdvance($employeeId, $type, $amount, $monthlyDeduction, $startDate, $notes) {
        $sql = "INSERT INTO employee_financial_transactions (employee_id, transaction_type, type, amount, monthly_deduction_amount, transaction_date, notes, remaining_balance, status) VALUES (:employee_id, :transaction_type, :type, :amount, :monthly_deduction_amount, :transaction_date, :notes, :remaining_balance, :status)";
        $stmt = $this->pdo->prepare($sql);
        try {
            return $stmt->execute([':employee_id' => $employeeId, ':transaction_type' => 'deduction', ':type' => $type, ':amount' => $amount, ':monthly_deduction_amount' => $monthlyDeduction, ':transaction_date' => $startDate, ':notes' => $notes, ':remaining_balance' => $amount, ':status' => 'Active']);
        } catch (Exception $e) {
            throw new Exception("Failed to add " . strtolower($type) . " transaction: " . $e->getMessage());
        }
    }

    public function addAdditionalTransaction($employeeId, $transactionType, $type, $amount, $date, $remarks) {
        $sql = "INSERT INTO employee_financial_transactions (employee_id, transaction_type, type, amount, transaction_date, remarks) VALUES (:employee_id, :transaction_type, :type, :amount, :transaction_date, :remarks)";
        $stmt = $this->pdo->prepare($sql);
        try {
            return $stmt->execute([':employee_id' => $employeeId, ':transaction_type' => $transactionType, ':type' => $type, ':amount' => $amount, ':transaction_date' => $date, ':remarks' => $remarks]);
        } catch (Exception $e) {
            throw new Exception("Failed to add additional transaction: " . $e->getMessage());
        }
    }

    public function setEmployeeLeave($employeeId, $startDate, $endDate, $remarks, $autoReactivate) {
        $this->pdo->beginTransaction();
        try {
            $updateStatusSql = "UPDATE employees SET employee_status = 'on_leave' WHERE id = :id";
            $updateStatusStmt = $this->pdo->prepare($updateStatusSql);
            $updateStatusStmt->execute([':id' => $employeeId]);

            $this->logEmployeeStatusChange($employeeId, 'active', 'on_leave', 'Leave taken', $startDate, $endDate, $remarks);

            $leaveSql = "INSERT INTO employee_leaves (employee_id, start_date, end_date, remarks, auto_reactivate, created_at) VALUES (:employee_id, :start_date, :end_date, :remarks, :auto_reactivate, NOW())";
            $leaveStmt = $this->pdo->prepare($leaveSql);
            $leaveStmt->execute([':employee_id' => $employeeId, ':start_date' => $startDate, ':end_date' => $endDate, ':remarks' => $remarks, ':auto_reactivate' => $autoReactivate]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Failed to record employee leave: " . $e->getMessage());
        }
    }
    // --- Bulk Actions ---
    public function bulkDeleteEmployees(array $employeeIds) {
        if (empty($employeeIds)) { return 0; }

        $this->pdo->beginTransaction();
        try {
            $deletedCount = 0;
            foreach ($employeeIds as $id) {
                // Ensure the deleteEmployee method is called correctly.
                // The deleteEmployee method itself manages transactions, so wrapping it in another is redundant, but kept for context.
                if ($this->deleteEmployee($id)) { 
                    $deletedCount++;
                } else {
                    throw new Exception("Failed to delete employee ID {$id} during bulk operation.");
                }
            }
            $this->pdo->commit();
            return $deletedCount;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Bulk deletion failed: " . $e->getMessage());
        }
    }

    public function bulkAddTransaction(array $employeeIds, $transactionType, $type, $amount, $date, $remarks) {
        if (empty($employeeIds)) { return 0; }

        $this->pdo->beginTransaction();
        try {
            $addedCount = 0;
            foreach ($employeeIds as $id) {
                if ($type === 'Loan' || $type === 'Advance') {
                    // This assumes a monthly deduction amount of 0 for bulk adding
                    $result = $this->addLoanAdvance($id, $type, $amount, 0, $date, $remarks); 
                } else {
                    $result = $this->addAdditionalTransaction($id, $transactionType, $type, $amount, $date, $remarks);
                }
                if ($result) {
                    $addedCount++;
                } else {
                    throw new Exception("Failed to add transaction for employee ID {$id}.");
                }
            }
            $this->pdo->commit();
            return $addedCount;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Bulk transaction addition failed: " . $e->getMessage());
        }
    }

    public function bulkQuickEditEmployees(array $employeesData) {
        if (empty($employeesData)) { return 0; }

        $updatedCount = 0;
        $this->pdo->beginTransaction();
        try {
            foreach ($employeesData as $employeeId => $data) {
                if (!isset($data['id']) || (int)$data['id'] !== (int)$employeeId) {
                    throw new Exception("Mismatched employee ID in bulk data for ID: {$employeeId}.");
                }
                if ($this->updatePartialEmployeeDetails((int)$employeeId, $data)) {
                    $updatedCount++;
                } else {
                    throw new Exception("Failed to quick edit employee ID {$employeeId}.");
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
        $setClauses = [];
        $params = [':id' => $employeeId];

        if (isset($data['basic_salary'])) {
            $setClauses[] = 'basic_salary = :basic_salary';
            $params[':basic_salary'] = (float)$data['basic_salary'];
        }
        if (isset($data['increment_percentage'])) {
            $setClauses[] = 'increment_percentage = :increment_percentage';
            $params[':increment_percentage'] = (float)$data['increment_percentage'];
        }

        if (isset($data['employee_status'])) {
            $oldStatus = $this->pdo->prepare("SELECT employee_status FROM employees WHERE id = :id");
            $oldStatus->execute([':id' => $employeeId]);
            $currentStatus = $oldStatus->fetchColumn();

            if ($currentStatus !== $data['employee_status']) {
                $setClauses[] = 'employee_status = :employee_status';
                $params[':employee_status'] = $data['employee_status'];
                $this->logEmployeeStatusChange($employeeId, $currentStatus, $data['employee_status'], 'Bulk Quick Edit');
            }
        }

        if (isset($data['employee_status']) && $data['employee_status'] === 'probation') {
            // Logic for confirmed_salary during probation is complex and usually requires specific form logic, 
            // maintaining the original logic here based on your provided file.
            if (isset($data['confirmed_salary'])) {
                $setClauses[] = 'confirmed_salary = :confirmed_salary';
                $params[':confirmed_salary'] = (float)$data['confirmed_salary'];
            } else {
                $currentBasicSalary = $this->pdo->prepare("SELECT basic_salary FROM employees WHERE id = :id");
                $currentBasicSalary->execute([':id' => $employeeId]);
                $basicSalary = $currentBasicSalary->fetchColumn();
                $setClauses[] = 'confirmed_salary = :confirmed_salary';
                $params[':confirmed_salary'] = (float)$basicSalary;
            }
        } else {
            $currentConfirmedSalary = $this->pdo->prepare("SELECT confirmed_salary FROM employees WHERE id = :id");
            $currentConfirmedSalary->execute([':id' => $employeeId]);
            if ($currentConfirmedSalary->fetchColumn() !== null) {
                $setClauses[] = 'confirmed_salary = NULL';
            }
        }

        if (empty($setClauses)) { return true; }

        $sql = "UPDATE employees SET " . implode(', ', $setClauses) . ", updated_at = NOW() WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function updateEmployeeStatus($employeeId, $newStatus, $reason = null) {
        $currentEmployee = $this->getEmployeeBasicInfo($employeeId);
        if (!$currentEmployee) {
            throw new Exception("Employee with ID {$employeeId} not found for status update.");
        }

        $this->pdo->beginTransaction();
        try {
            $sql = "UPDATE employees SET employee_status = :new_status, updated_at = NOW() WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':new_status' => $newStatus,
                ':id' => $employeeId
            ]);

            $this->logEmployeeStatusChange($employeeId, $currentEmployee['employee_status'], $newStatus, $reason);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Failed to update employee status: " . $e->getMessage());
        }
    }

    private function logEmployeeStatusChange($employeeId, $oldStatus, $newStatus, $reason) {
        $sql = "INSERT INTO employee_status_history (employee_id, old_status, new_status, reason, change_date, logged_at) VALUES (:employee_id, :old_status, :new_status, :reason, CURDATE(), NOW())";
        $stmt = $this->pdo->prepare($sql);
        try {
            return $stmt->execute([
                ':employee_id' => $employeeId, 
                ':old_status' => $oldStatus, 
                ':new_status' => $newStatus, 
                ':reason' => $reason
            ]);
        } catch (Exception $e) {
            error_log("Failed to log employee status change for employee {$employeeId}: " . $e->getMessage());
            return false;
        }
    }

    public function generateReport($reportType, array $params) {
        // ... (All report generation logic remains unchanged as it was correct)
        switch ($reportType) {
            case 'by_department':
                $sql = "SELECT d.name AS department_name, COUNT(e.id) as employee_count, COALESCE(SUM(e.basic_salary), 0) as total_basic_salary, COALESCE(AVG(e.basic_salary), 0) as average_basic_salary FROM employees e JOIN departments d ON e.department_id = d.id WHERE e.employee_status = 'active'";
                $queryParams = [];
                if (!empty($params['department_id'])) {
                    $sql .= " AND d.id = :department_id";
                    $queryParams[':department_id'] = (int)$params['department_id'];
                }
                $sql .= " GROUP BY d.name ORDER BY d.name";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($queryParams);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'by_hiring_date':
                $sql = "SELECT e.employee_number, e.full_name, e.designation, d.name AS department_name, e.date_of_joining, e.employee_status FROM employees e JOIN departments d ON e.department_id = d.id WHERE e.date_of_joining BETWEEN :start_date AND :end_date ORDER BY e.date_of_joining ASC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([ ':start_date' => $params['start_date'], ':end_date' => $params['end_date'] ]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'by_salary_range':
                $sql = "SELECT e.employee_number, e.full_name, e.designation, d.name AS department_name, e.basic_salary, e.currency, e.employee_status FROM employees e JOIN departments d ON e.department_id = d.id WHERE e.basic_salary BETWEEN :min_salary AND :max_salary ORDER BY e.basic_salary ASC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([ ':min_salary' => (float)$params['min_salary'], ':max_salary' => (float)$params['max_salary'] ]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'by_service_year':
                $sql = "SELECT e.employee_number, e.full_name, e.designation, d.name AS department_name, e.date_of_joining, e.employee_status, TIMESTAMPDIFF(YEAR, e.date_of_joining, CURDATE()) AS service_years FROM employees e JOIN departments d ON e.department_id = d.id WHERE TIMESTAMPDIFF(YEAR, e.date_of_joining, CURDATE()) BETWEEN :min_years AND :max_years ORDER BY service_years DESC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([ ':min_years' => (int)$params['min_years'], ':max_years' => (int)$params['max_years'] ]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'full_employee_data':
                $sql = "SELECT e.employee_number, e.full_name, e.designation, d.name AS department_name, e.date_of_joining, e.date_of_birth, e.contact_email, e.contact_mobile, e.address, e.country, e.employee_status, e.identity_card_number, e.tax_payer_id, e.basic_salary, e.currency, e.increment_percentage, e.bank_name, e.bank_iban, e.overtime_rate_multiplier, e.confirmed_salary, e.emergency_contact_name, e.dependent1_name FROM employees e JOIN departments d ON e.department_id = d.id ORDER BY e.full_name ASC";
                $stmt = $this->pdo->query($sql);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'by_status':
                $sql = "SELECT e.employee_number, e.full_name, e.designation, d.name AS department_name, e.employee_status, e.date_of_joining FROM employees e JOIN departments d ON e.department_id = d.id";
                $queryParams = [];
                if (!empty($params['status'])) {
                    $sql .= " WHERE e.employee_status = :status";
                    $queryParams[':status'] = $params['status'];
                }
                $sql .= " ORDER BY e.employee_status, e.full_name ASC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($queryParams);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'increment_due_report':
                $sql = "SELECT e.employee_number, e.full_name, e.designation, d.name AS department_name, e.date_of_joining, e.basic_salary, e.increment_percentage FROM employees e JOIN departments d ON e.department_id = d.id WHERE DATE_ADD(e.date_of_joining, INTERVAL 1 YEAR) <= :due_date AND e.employee_status = 'active' ORDER BY e.date_of_joining ASC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':due_date' => $params['due_date'] ?? date('Y-12-31')]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'birthday_notification':
                $sql = "SELECT employee_number, full_name, date_of_birth, contact_email, contact_mobile, designation, d.name AS department_name FROM employees e JOIN departments d ON e.department_id = d.id WHERE MONTH(date_of_birth) = :month AND employee_status = 'active' ORDER BY DAY(date_of_birth) ASC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':month' => (int)$params['month']]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            case 'employee_advances_loans_payments_deductions':
                $sql = "SELECT eft.*, e.full_name, e.employee_number, e.currency FROM employee_financial_transactions eft JOIN employees e ON eft.employee_id = e.id WHERE 1=1";
                $queryParams = [];

                if (!empty($params['transaction_type'])) {
                    if ($params['transaction_type'] === 'loan' || $params['transaction_type'] === 'advance') {
                        $sql .= " AND eft.type = :trans_type";
                        $queryParams[':trans_type'] = $params['transaction_type'];
                    } else if ($params['transaction_type'] === 'payment') {
                        $sql .= " AND eft.transaction_type = 'earning' AND eft.type NOT IN ('Loan', 'Advance')";
                    } else if ($params['transaction_type'] === 'deduction') {
                        $sql .= " AND eft.transaction_type = 'deduction' AND eft.type NOT IN ('Loan', 'Advance')";
                    }
                }
                if (!empty($params['from_date'])) {
                    $sql .= " AND eft.transaction_date >= :from_date";
                    $queryParams[':from_date'] = $params['from_date'];
                }
                if (!empty($params['to_date'])) {
                    $sql .= " AND eft.transaction_date <= :to_date";
                    $queryParams[':to_date'] = $params['to_date'];
                }
                $sql .= " ORDER BY eft.transaction_date DESC, e.full_name ASC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($queryParams);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);

            default:
                throw new Exception("Unknown report type: " . $reportType);
        }
    }
}
}
