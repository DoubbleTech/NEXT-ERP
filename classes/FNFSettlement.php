<?php
// classes/FNFSettlement.php

class FNFSettlement {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get employees who are marked for settlement (e.g., status 'Resigned', 'Terminated').
     * In a real app, this queries 'employees' and 'settlements' tables.
     *
     * @return array List of employees needing settlement.
     */
    public function getEmployeesForSettlement(): array {
        // --- DEMO LOGIC ---
        // Simulate data for employees needing settlement
        return [
            [
                'id' => 103,
                'full_name' => 'Carol Davis',
                'employee_number' => 'EMP003',
                'department_name' => 'IT',
                'department_id' => 1, // Added for demo of no-dues
                'termination_date' => '2025-07-31',
                'employee_status' => 'Resigned',
                'avatar_url' => '', // Placeholder
                'has_pending_settlement' => true, // Carol has a pending settlement in demo
                'settlement_id' => 1, // Demo settlement ID
                'settlement_status' => 'PENDING_DEPT_APPROVAL', // Initial status for demo
                'no_dues_status' => 'PENDING' // Added for no-dues demo
            ],
            [
                'id' => 401,
                'full_name' => 'Mark White',
                'employee_number' => 'EMP004',
                'department_name' => 'Marketing',
                'department_id' => 2,
                'termination_date' => '2025-08-15',
                'employee_status' => 'Resigned',
                'avatar_url' => '', // Placeholder
                'has_pending_settlement' => false, // Mark needs initiation
                'settlement_id' => null,
                'settlement_status' => null,
                'no_dues_status' => 'NOT_STARTED'
            ]
        ];
        // --- END DEMO LOGIC ---

        /*
        // --- REAL IMPLEMENTATION (Conceptual) ---
        // try {
        //     $stmt = $this->pdo->prepare("
        //         SELECT e.id, e.full_name, e.employee_number, d.name as department_name, e.termination_date, e.status as employee_status,
        //                s.id as settlement_id, s.status as settlement_status, s.no_dues_status
        //         FROM employees e
        //         LEFT JOIN departments d ON e.department_id = d.id
        //         LEFT JOIN settlements s ON e.id = s.employee_id AND s.status != 'COMPLETED'
        //         WHERE e.status IN ('Resigned', 'Terminated', 'Retired')
        //         ORDER BY e.termination_date DESC
        //     ");
        //     $stmt->execute();
        //     return $stmt->fetchAll(PDO::FETCH_ASSOC);
        // } catch (PDOException $e) {
        //     error_log("FNFSettlement Error (getEmployeesForSettlement): " . $e->getMessage());
        //     return [];
        // }
        // --- END REAL IMPLEMENTATION ---
        */
    }

    /**
     * Get pending approvals for the current user's roles.
     *
     * @param int $currentUserId The ID of the logged-in user.
     * @return array List of settlements pending approval.
     */
    public function getPendingApprovals(int $currentUserId): array {
        // --- DEMO LOGIC ---
        // Simulate data for pending approvals
        $pending = [];
        // Assuming currentUserId 205 (Robert Brown - IT Head) can approve PENDING_DEPT_APPROVAL
        if ($currentUserId === 205) { // Is a Dept Head
            $pending[] = [
                'id' => 1,
                'employee_id' => 103,
                'employee_name' => 'Carol Davis',
                'department_name' => 'IT',
                'department_id' => 1, // For demoing department-specific no-dues
                'termination_date' => '2025-07-31',
                'net_amount' => 15500.00,
                'status' => 'PENDING_DEPT_APPROVAL',
                'no_dues_status' => 'PENDING', // IT needs to clear no-dues for Carol
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }
        // Assuming currentUserId 101 (John Doe - HR) can process PENDING_HR_PROCESSING
        if ($currentUserId === 101) { // Is HR
            $pending[] = [
                'id' => 2,
                'employee_id' => 401,
                'employee_name' => 'Mark White',
                'department_name' => 'Marketing',
                'department_id' => 2,
                'termination_date' => '2025-08-15',
                'net_amount' => 25000.00,
                'status' => 'PENDING_HR_PROCESSING',
                'no_dues_status' => 'CLEARED', // Assume no-dues cleared for Mark
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }
        // Assuming currentUserId 102 (Jane Smith - Finance) can complete PENDING_PAYMENT
        if ($currentUserId === 102) { // Is Finance
            $pending[] = [
                'id' => 3,
                'employee_id' => 103, // Another settlement for Carol, but at finance stage
                'employee_name' => 'Carol Davis',
                'department_name' => 'IT',
                'department_id' => 1,
                'termination_date' => '2025-07-31',
                'net_amount' => 15500.00,
                'status' => 'PENDING_PAYMENT',
                'no_dues_status' => 'CLEARED',
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }

        return $pending;
        // --- END DEMO LOGIC ---

        /*
        // --- REAL IMPLEMENTATION (Conceptual) ---
        // You'd build a complex query here that checks the current user's roles
        // and the status of the settlements to determine what's "pending approval" for them.
        // For example:
        // SELECT s.*, e.full_name as employee_name, d.name as department_name
        // FROM settlements s
        // JOIN employees e ON s.employee_id = e.id
        // JOIN departments d ON e.department_id = d.id
        // WHERE (s.status = 'PENDING_DEPT_APPROVAL' AND EXISTS (SELECT 1 FROM departments WHERE head_employee_id = :currentUserId AND id = e.department_id))
        // OR (s.status = 'PENDING_HR_PROCESSING' AND EXISTS (SELECT 1 FROM user_permissions WHERE employee_id = :currentUserId AND permission_key = 'hr_access'))
        // OR (s.status = 'PENDING_PAYMENT' AND EXISTS (SELECT 1 FROM user_permissions WHERE employee_id = :currentUserId AND permission_key = 'finance_access'))
        // ORDER BY s.updated_at DESC
        // --- END REAL IMPLEMENTATION ---
        */
    }

    /**
     * Get the settlement history.
     *
     * @return array List of past settlements.
     */
    public function getSettlementHistory(): array {
        // --- DEMO LOGIC ---
        return [
            [
                'id' => 4,
                'employee_id' => 501,
                'employee_name' => 'Michael Green',
                'termination_date' => '2024-12-31',
                'net_amount' => 18000.00,
                'status' => 'COMPLETED',
                'updated_at' => '2025-01-05 10:30:00'
            ],
            [
                'id' => 5,
                'employee_id' => 502,
                'employee_name' => 'Sophia Lee',
                'termination_date' => '2025-03-15',
                'net_amount' => 32000.00,
                'status' => 'COMPLETED',
                'updated_at' => '2025-03-20 14:00:00'
            ]
        ];
        // --- END DEMO LOGIC ---

        /*
        // --- REAL IMPLEMENTATION (Conceptual) ---
        // try {
        //     $stmt = $this->pdo->prepare("
        //         SELECT s.id, e.full_name as employee_name, e.termination_date, s.net_amount, s.status, s.updated_at
        //         FROM settlements s
        //         JOIN employees e ON s.employee_id = e.id
        //         WHERE s.status = 'COMPLETED' OR s.status = 'ARCHIVED'
        //         ORDER BY s.updated_at DESC LIMIT 20
        //     ");
        //     $stmt->execute();
        //     return $stmt->fetchAll(PDO::FETCH_ASSOC);
        // } catch (PDOException $e) {
        //     error_log("FNFSettlement Error (getSettlementHistory): " . $e->getMessage());
        //     return [];
        // }
        // --- END REAL IMPLEMENTATION ---
        */
    }

    /**
     * Initiates a new settlement for an employee.
     *
     * @param int $employeeId The ID of the employee to initiate settlement for.
     * @param int $initiatedByUserId The ID of the user initiating.
     * @return bool True on success, false on failure.
     */
    public function initiateSettlement(int $employeeId, int $initiatedByUserId): bool {
        // --- DEMO LOGIC ---
        error_log("DEMO: Initiating settlement for employee ID $employeeId by user $initiatedByUserId");
        // In a real app, insert a new record into the 'settlements' table
        // with status 'INITIATED' or 'PENDING_DEPT_APPROVAL' and initial calculated amounts.
        // Return true to simulate success.
        return true;
        // --- END DEMO LOGIC ---

        /*
        // --- REAL IMPLEMENTATION (Conceptual) ---
        // try {
        //     $this->pdo->beginTransaction();
        //     // First, check if settlement already exists for this employee (if not completed)
        //     $stmtCheck = $this->pdo->prepare("SELECT id FROM settlements WHERE employee_id = :emp_id AND status != 'COMPLETED'");
        //     $stmtCheck->execute([':emp_id' => $employeeId]);
        //     if ($stmtCheck->fetch()) {
        //         throw new Exception("Settlement already initiated for this employee.");
        //     }

        //     // Calculate initial FNF (e.g., unpaid salary, leave encashment, known deductions)
        //     $initialFNF = $this->calculateSettlement($employeeId); // Call a method to get initial values

        //     $stmt = $this->pdo->prepare("
        //         INSERT INTO settlements (employee_id, initiated_by, status, net_amount, created_at, updated_at)
        //         VALUES (:employee_id, :initiated_by, 'INITIATED', :net_amount, NOW(), NOW())
        //     ");
        //     $stmt->execute([
        //         ':employee_id' => $employeeId,
        //         ':initiated_by' => $initiatedByUserId,
        //         ':net_amount' => $initialFNF['net_amount'] ?? 0 // Use calculated amount or 0
        //     ]);
        //     $this->pdo->commit();
        //     return true;
        // } catch (Exception $e) {
        //     $this->pdo->rollBack();
        //     error_log("FNFSettlement Error (initiateSettlement): " . $e->getMessage());
        //     return false;
        // }
        // --- END REAL IMPLEMENTATION ---
        */
    }

    /**
     * Approves a settlement. (Department Head Approval)
     *
     * @param int $settlementId The ID of the settlement to approve.
     * @param int $approvedByUserId The ID of the user approving.
     * @return bool True on success, false on failure.
     */
    public function approveSettlement(int $settlementId, int $approvedByUserId): bool {
        // --- DEMO LOGIC ---
        error_log("DEMO: Approving settlement ID $settlementId by user $approvedByUserId (Dept Head)");
        // In a real app, update settlement status to 'PENDING_HR_PROCESSING'
        // and record the approval.
        return true;
        // --- END DEMO LOGIC ---

        /*
        // --- REAL IMPLEMENTATION (Conceptual) ---
        // try {
        //     $stmt = $this->pdo->prepare("
        //         UPDATE settlements SET status = 'PENDING_HR_PROCESSING', updated_at = NOW()
        //         WHERE id = :id AND status = 'PENDING_DEPT_APPROVAL'
        //     ");
        //     $stmt->execute([':id' => $settlementId]);
        //     return $stmt->rowCount() > 0;
        // } catch (PDOException $e) {
        //     error_log("FNFSettlement Error (approveSettlement): " . $e->getMessage());
        //     return false;
        // }
        // --- END REAL IMPLEMENTATION ---
        */
    }

    /**
     * Processes a settlement. (HR Processing)
     * This is where detailed calculations and document preparation happens.
     *
     * @param int $settlementId The ID of the settlement to process.
     * @param int $processedByUserId The ID of the user processing.
     * @return bool True on success, false on failure.
     */
    public function processSettlement(int $settlementId, int $processedByUserId): bool {
        // --- DEMO LOGIC ---
        error_log("DEMO: Processing settlement ID $settlementId by user $processedByUserId (HR)");
        // In a real app, perform final calculations, generate F&F statement,
        // and update status to 'PENDING_PAYMENT'.
        return true;
        // --- END DEMO LOGIC ---

        /*
        // --- REAL IMPLEMENTATION (Conceptual) ---
        // try {
        //     // Recalculate and finalize all FNF components (leave, bonus, gratuity, loans, assets etc.)
        //     $finalFNF = $this->calculateSettlement($settlementId); // Needs to be adapted to work with settlement ID too
        //
        //     $stmt = $this->pdo->prepare("
        //         UPDATE settlements
        //         SET status = 'PENDING_PAYMENT', net_amount = :net_amount, updated_at = NOW(),
        //             processed_by = :processed_by_user_id
        //         WHERE id = :id AND status = 'PENDING_HR_PROCESSING'
        //     ");
        //     $stmt->execute([
        //         ':id' => $settlementId,
        //         ':net_amount' => $finalFNF['net_amount'] ?? 0,
        //         ':processed_by_user_id' => $processedByUserId
        //     ]);
        //     return $stmt->rowCount() > 0;
        // } catch (PDOException $e) {
        //     error_log("FNFSettlement Error (processSettlement): " . $e->getMessage());
        //     return false;
        // }
        // --- END REAL IMPLEMENTATION ---
        */
    }

    /**
     * Completes a settlement. (Finance Payment)
     *
     * @param int $settlementId The ID of the settlement to complete.
     * @param int $completedByUserId The ID of the user completing.
     * @return bool True on success, false on failure.
     */
    public function completeSettlement(int $settlementId, int $completedByUserId): bool {
        // --- DEMO LOGIC ---
        error_log("DEMO: Completing settlement ID $settlementId by user $completedByUserId (Finance)");
        // In a real app, mark payment as done, update status to 'COMPLETED'.
        return true;
        // --- END DEMO LOGIC ---

        /*
        // --- REAL IMPLEMENTATION (Conceptual) ---
        // try {
        //     $stmt = $this->pdo->prepare("
        //         UPDATE settlements SET status = 'COMPLETED', updated_at = NOW(), completed_by = :completed_by_user_id
        //         WHERE id = :id AND status = 'PENDING_PAYMENT'
        //     ");
        //     $stmt->execute([
        //         ':id' => $settlementId,
        //         ':completed_by_user_id' => $completedByUserId
        //     ]);
        //     return $stmt->rowCount() > 0;
        // } catch (PDOException $e) {
        //     error_log("FNFSettlement Error (completeSettlement): " . $e->getMessage());
        //     return false;
        // }
        // --- END REAL IMPLEMENTATION ---
        */
    }

    /**
     * Calculates the final settlement amount for an employee.
     * This is a complex method that would pull data from various sources (leave, loans, payroll).
     *
     * @param int $employeeId The ID of the employee.
     * @return array Calculated settlement data.
     */
    public function calculateSettlement(int $employeeId): array {
        // --- DEMO LOGIC ---
        error_log("DEMO: Calculating settlement for employee ID $employeeId");
        // Simulate a detailed FNF calculation
        $unpaidSalary = 7500.00; // Example
        $leaveEncashment = 5000.00; // Example
        $gratuity = ($employeeId === 103) ? 10000.00 : 0.00; // Only Carol gets gratuity in demo
        $totalEarnings = $unpaidSalary + $leaveEncashment + $gratuity;

        $loanOutstanding = 1500.00; // Example
        $noticePeriodRecovery = 3000.00; // Example
        $totalDeductions = $loanOutstanding + $noticePeriodRecovery;

        $netAmount = $totalEarnings - $totalDeductions;

        return [
            'employee_id' => $employeeId,
            'unpaid_salary' => $unpaidSalary,
            'leave_encashment' => $leaveEncashment,
            'gratuity_amount' => $gratuity,
            'loan_outstanding' => $loanOutstanding,
            'notice_period_recovery' => $noticePeriodRecovery,
            'total_earnings' => $totalEarnings,
            'total_deductions' => $totalDeductions,
            'net_amount' => $netAmount,
            // Add other relevant details you might need to display
        ];
        // --- END DEMO LOGIC ---

        /*
        // --- REAL IMPLEMENTATION (Conceptual) ---
        // This method would be the most complex. It would:
        // 1. Fetch employee's last payroll data.
        // 2. Query leave balances and apply encashment policy.
        // 3. Query outstanding loans/advances.
        // 4. Calculate gratuity based on company policy (years of service, last drawn salary).
        // 5. Calculate notice period recovery/pay-in-lieu.
        // 6. Account for company assets, training bonds, etc.
        // 7. Sum up all earnings and deductions to get net amount.
        //
        // return [ ... calculated values ... ];
        // --- END REAL IMPLEMENTATION ---
        */
    }

    /**
     * Clears no dues for a specific department for a settlement.
     *
     * @param int $settlementId The ID of the settlement.
     * @param string $departmentName The name of the department (e.g., 'IT', 'Admin').
     * @param int $clearedByUserId The ID of the user clearing.
     * @return bool True on success, false on failure.
     */
    public function clearNoDues(int $settlementId, string $departmentName, int $clearedByUserId): bool {
        // --- DEMO LOGIC ---
        error_log("DEMO: Clearing no dues for settlement $settlementId by $departmentName from user $clearedByUserId");
        // Simulate updating a 'no_dues_status' in the settlements table or a dedicated 'no_dues_clearances' table.
        // For simplicity, let's assume `no_dues_status` in the settlement table can change to 'CLEARED'
        // when *any* department clears it for demo purposes.
        // In a real system, you'd likely have a separate table for department-specific clearances,
        // and only update the main settlement status to 'CLEARED' when ALL required clearances are met.
        return true;
        // --- END DEMO LOGIC ---

        /*
        // --- REAL IMPLEMENTATION (Conceptual) ---
        // try {
        //     // This would likely involve a join table like `settlement_no_dues`
        //     // INSERT INTO settlement_no_dues (settlement_id, department_id, cleared_by, cleared_at, status)
        //     // VALUES (:settlement_id, (SELECT id FROM departments WHERE name = :dept_name), :cleared_by, NOW(), 'CLEARED')
        //     // ON DUPLICATE KEY UPDATE cleared_by = VALUES(cleared_by), cleared_at = VALUES(cleared_at), status = VALUES(status)
        //
        //     // After clearing, check if ALL required department no-dues are cleared for this settlement.
        //     // If so, update the main `settlements.no_dues_status` to 'CLEARED'.
        //     $stmt = $this->pdo->prepare("UPDATE settlements SET no_dues_status = 'CLEARED', updated_at = NOW() WHERE id = :id");
        //     $stmt->execute([':id' => $settlementId]);
        //     return $stmt->rowCount() > 0;
        // } catch (PDOException $e) {
        //     error_log("FNFSettlement Error (clearNoDues): " . $e->getMessage());
        //     return false;
        // }
        // --- END REAL IMPLEMENTATION ---
        */
    }

    /**
     * Checks if a settlement has been digitally signed.
     * (Placeholder for Digital Signatures feature)
     *
     * @param int $settlementId
     * @return bool
     */
    public function hasSignedSettlement(int $settlementId): bool {
        // --- DEMO LOGIC ---
        // For demo, let's say settlement ID 5 has been signed.
        return ($settlementId === 5);
        // --- END DEMO LOGIC ---

        /*
        // --- REAL IMPLEMENTATION (Conceptual) ---
        // Query a `digital_signatures` table.
        // SELECT COUNT(*) FROM digital_signatures WHERE settlement_id = :id AND signature_type = 'HR_FINAL';
        // --- END REAL IMPLEMENTATION ---
        */
    }

    // You might also add methods like:
    // public function uploadDocument(int $settlementId, string $docType, string $filePath, int $uploadedByUserId): bool;
    // public function getDocuments(int $settlementId): array;
    // public function getNoDuesStatusForEmployee(int $employeeId): array; // For employee self-service modal
}