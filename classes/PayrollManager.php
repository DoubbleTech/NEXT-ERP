<?php
// classes/PayrollManager.php

if (!class_exists('PayrollManager')) {

/**
 * PayrollManager Class
 *
 * Manages all payroll-related business logic and database interactions.
 */
class PayrollManager {
    private $pdo; // PDO database connection object

    /**
     * @param PDO $pdo The PDO database connection object.
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Fetches the month and year of the last finalized payroll.
     *
     * @return array|null An associative array with 'pay_period_month' and 'pay_period_year' or null if none found.
     */
    public function getLastFinalizedPayrollPeriod(): ?array {
        try {
            $sql = "SELECT pay_period_month, pay_period_year FROM payroll_history WHERE status = 'Finalized' ORDER BY pay_period_year DESC, pay_period_month DESC LIMIT 1";
            $stmt = $this->pdo->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("ERROR: PayrollManager->getLastFinalizedPayrollPeriod failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieves all saved payroll templates.
     *
     * @return array An array of template objects.
     */
    public function getPayrollTemplates(): array {
        try {
            $sql = "SELECT id, template_name, department_ids FROM payroll_templates ORDER BY template_name ASC";
            $stmt = $this->pdo->query($sql);
            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format department IDs for display
            foreach ($templates as &$template) {
                $template['department_ids'] = json_decode($template['department_ids'], true);
            }
            return $templates;
        } catch (PDOException $e) {
            error_log("ERROR: PayrollManager->getPayrollTemplates failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Saves a new payroll template.
     *
     * @param string $templateName The name of the template.
     * @param array $departmentIds The array of department IDs or ['all'].
     * @return int The ID of the newly saved template.
     * @throws Exception if template name already exists.
     */
    public function savePayrollTemplate(string $templateName, array $departmentIds): int {
        if (empty($templateName)) {
            throw new Exception("Template name cannot be empty.");
        }
        
        $jsonDepartmentIds = json_encode($departmentIds);

        try {
            $sql = "INSERT INTO payroll_templates (template_name, department_ids, created_at) VALUES (?, ?, NOW())";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$templateName, $jsonDepartmentIds]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') { // Check for unique constraint violation
                throw new Exception("A payroll area with this name already exists.");
            }
            error_log("ERROR: PayrollManager->savePayrollTemplate failed: " . $e->getMessage());
            throw new Exception("Failed to save payroll area: " . $e->getMessage());
        }
    }

    /**
     * Deletes a payroll template.
     *
     * @param int $templateId The ID of the template to delete.
     * @return bool True on success.
     */
    public function deletePayrollTemplate(int $templateId): bool {
        if (!$templateId) {
            return false;
        }
        try {
            $sql = "DELETE FROM payroll_templates WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$templateId]);
        } catch (PDOException $e) {
            error_log("ERROR: PayrollManager->deletePayrollTemplate failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generates a payroll preview without saving it to the database.
     *
     * @param int $month The payroll month.
     * @param int $year The payroll year.
     * @param array $departmentIds Optional. Array of department IDs to filter by.
     * @return array An array containing a summary and detailed breakdown of the payroll.
     */
    public function generatePayrollPreview(int $month, int $year, array $departmentIds = []): array {
        // Here you would implement your payroll calculation logic.
        // This is a placeholder for your business logic.
        // You would fetch employees, calculate salaries, taxes, deductions, etc.
        
        // This part needs to be customized to your specific business rules.
        // For a demonstration, this is a mock implementation.
        $employees = $this->getEmployeesForPayroll($month, $year, $departmentIds);
        $details = [];
        $summary = [
            'total_employees' => count($employees),
            'total_gross_pay' => 0,
            'total_deductions' => 0,
            'total_net_pay' => 0,
            'status' => 'Pending'
        ];
        
        foreach ($employees as $emp) {
            // Mock Calculation
            $grossPay = $emp['basic_salary'] + ($emp['housing_allowance'] ?? 0) + ($emp['transportation_allowance'] ?? 0);
            $deductions = ($grossPay * 0.1) + ($emp['loan_deduction'] ?? 0); // Mock 10% tax deduction + other deductions
            $netPay = $grossPay - $deductions;

            $details[] = [
                'employee_id' => $emp['id'],
                'employee_name' => $emp['full_name'],
                'designation' => $emp['designation'],
                'department' => $emp['department_name'],
                'gross_pay' => $grossPay,
                'additional_payments' => 0,
                'tax_deduction' => $deductions,
                'reimbursement_amount' => 0,
                'total_deductions' => $deductions,
                'net_pay' => $netPay,
                'status' => 'Pending'
            ];

            $summary['total_gross_pay'] += $grossPay;
            $summary['total_deductions'] += $deductions;
            $summary['total_net_pay'] += $netPay;
        }

        return ['summary' => $summary, 'details' => $details];
    }
    
    /**
     * Generates and saves a new payroll into the database.
     *
     * @param int $month The payroll month.
     * @param int $year The payroll year.
     * @param array $departmentIds Optional. Array of department IDs to filter by.
     * @return array An array containing the saved payroll summary and details.
     * @throws Exception if a payroll for the period already exists.
     */
    public function generateAndSavePayroll(int $month, int $year, array $departmentIds = []): array {
        // First, check if a payroll already exists for this period.
        $existingPayroll = $this->getPayrollHistoryByPeriod($month, $year);
        if ($existingPayroll) {
            throw new Exception("Payroll for this period already exists. View the existing draft or delete it to generate a new one.");
        }
    
        // Use the same logic as the preview to calculate, but this time, save everything.
        $payrollData = $this->generatePayrollPreview($month, $year, $departmentIds);
        $summary = $payrollData['summary'];
        $details = $payrollData['details'];

        $this->pdo->beginTransaction();
        try {
            // 1. Save payroll summary
            $sql = "INSERT INTO payroll_history (pay_period_month, pay_period_year, total_gross_pay, total_deductions, total_net_pay, status) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $month,
                $year,
                $summary['total_gross_pay'],
                $summary['total_deductions'],
                $summary['total_net_pay'],
                'Pending'
            ]);
            $payrollId = $this->pdo->lastInsertId();

            // 2. Save payroll details (payslips)
            $sql = "INSERT INTO payroll_details (payroll_id, employee_id, gross_pay, tax_deduction, total_deductions, net_pay, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            foreach ($details as $detail) {
                $stmt->execute([
                    $payrollId,
                    $detail['employee_id'],
                    $detail['gross_pay'],
                    $detail['tax_deduction'],
                    $detail['total_deductions'],
                    $detail['net_pay'],
                    'Pending'
                ]);
            }

            // 3. Update related data, e.g., mark reimbursements as processed for this period.
            // This is complex logic that needs to be implemented. For now, it's a placeholder.
            // $reimbursementManager->markReimbursementsAsProcessed($month, $year, $departmentIds);
            
            $this->pdo->commit();
            
            // Return the newly saved payroll data
            $savedPayrollData = $this->getPayrollDetails($month, $year, $departmentIds);
            $savedPayrollData['summary']['status'] = 'Pending'; // Ensure the status is correct
            return $savedPayrollData;

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("ERROR: PayrollManager->generateAndSavePayroll failed: " . $e->getMessage());
            throw new Exception("Failed to save payroll: " . $e->getMessage());
        }
    }
    
    /**
     * Retrieves the details of a generated payroll.
     *
     * @param int $month The payroll month.
     * @param int $year The payroll year.
     * @param array $departmentIds Optional. Array of department IDs to filter by.
     * @return array|null An array with summary and details or null if no payroll found.
     */
    public function getPayrollDetails(int $month, int $year, array $departmentIds = []): ?array {
        try {
            // Get the payroll summary
            $sql = "SELECT * FROM payroll_history WHERE pay_period_month = ? AND pay_period_year = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$month, $year]);
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$summary) {
                return null;
            }
            
            // Get the payroll details (payslips)
            $sql = "
                SELECT
                    pd.id, pd.employee_id, pd.gross_pay, pd.tax_deduction, pd.total_deductions, pd.net_pay, pd.status,
                    e.full_name AS employee_name,
                    e.designation,
                    d.name AS department_name
                FROM payroll_details pd
                JOIN employees e ON pd.employee_id = e.id
                JOIN departments d ON e.department_id = d.id
                WHERE pd.payroll_id = ?
            ";
            
            // Apply department filter if necessary
            if (!empty($departmentIds) && !in_array('all', $departmentIds, true)) {
                $placeholders = implode(',', array_fill(0, count($departmentIds), '?'));
                $sql .= " AND e.department_id IN ($placeholders)";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $params = [$summary['id']];
            if (!empty($departmentIds) && !in_array('all', $departmentIds, true)) {
                 $params = array_merge($params, $departmentIds);
            }
            
            $stmt->execute($params);
            $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['summary' => $summary, 'details' => $details];

        } catch (PDOException $e) {
            error_log("ERROR: PayrollManager->getPayrollDetails failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Updates the status of an individual payslip.
     *
     * @param int $payslipDetailId The ID of the payslip detail row.
     * @param string $newStatus The new status to set ('Pending', 'Needs Review', 'Approved').
     * @return bool True on success.
     */
    public function updatePayslipStatus(int $payslipDetailId, string $newStatus): bool {
        if (!$payslipDetailId) {
            return false;
        }
        try {
            $sql = "UPDATE payroll_details SET status = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$newStatus, $payslipDetailId]);
        } catch (PDOException $e) {
            error_log("ERROR: PayrollManager->updatePayslipStatus failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches all payroll records from history.
     *
     * @return array An array of payroll history records.
     */
    public function getPayrollHistory(): array {
        try {
            $sql = "SELECT id, pay_period_month, pay_period_year, total_net_pay, status, finalized_at FROM payroll_history ORDER BY pay_period_year DESC, pay_period_month DESC";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ERROR: PayrollManager->getPayrollHistory failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retrieves the full payslip details for a specific payroll detail ID.
     *
     * @param int $payrollDetailId The ID of the payroll detail record.
     * @return array|null The payslip data or null if not found.
     */
    public function getPayslipDetails(int $payrollDetailId): ?array {
        try {
            $sql = "
                SELECT
                    pd.*,
                    e.full_name AS employee_name,
                    e.employee_number,
                    e.designation,
                    e.date_of_joining,
                    e.bank_iban,
                    d.name AS department
                FROM payroll_details pd
                JOIN employees e ON pd.employee_id = e.id
                JOIN departments d ON e.department_id = d.id
                WHERE pd.id = ?
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$payrollDetailId]);
            $payslip = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payslip) {
                return null;
            }

            // Fetch deductions, earnings, and reimbursements
            // This is a placeholder. You need to implement methods to fetch these from your database.
            $payslip['earning_details'] = [
                ['type' => 'Basic Salary', 'remarks' => 'Monthly Salary', 'amount' => $payslip['gross_pay']],
                // Add other earnings here
            ];
            $payslip['deduction_details'] = [
                ['type' => 'Tax Deduction', 'remarks' => 'Monthly Tax', 'amount' => $payslip['tax_deduction']],
                // Add other deductions here
            ];
            $payslip['reimbursement_claims'] = []; // Fetch and add reimbursement data here.

            return $payslip;

        } catch (PDOException $e) {
            error_log("ERROR: PayrollManager->getPayslipDetails failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Finalizes a payroll, locking it for the period.
     *
     * @param int $month The payroll month.
     * @param int $year The payroll year.
     * @return bool True on success.
     */
    public function finalizePayroll(int $month, int $year): bool {
        try {
            $sql = "UPDATE payroll_history SET status = 'Finalized', finalized_at = NOW() WHERE pay_period_month = ? AND pay_period_year = ? AND status = 'Pending'";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$month, $year]);
        } catch (PDOException $e) {
            error_log("ERROR: PayrollManager->finalizePayroll failed: " . $e->getMessage());
            throw new Exception("Failed to finalize payroll: " . $e->getMessage());
        }
    }
    
    /**
     * Deletes a pending payroll draft.
     *
     * @param int $payrollId The ID of the payroll record in payroll_history.
     * @return bool True on success.
     */
    public function deletePayroll(int $payrollId): bool {
        if (!$payrollId) {
            return false;
        }
        
        $this->pdo->beginTransaction();
        try {
            // First, delete related payslips
            $sql = "DELETE FROM payroll_details WHERE payroll_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$payrollId]);
            
            // Then, delete the payroll history record
            $sql = "DELETE FROM payroll_history WHERE id = ? AND status = 'Pending'"; // Only delete pending drafts
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$payrollId]);
            
            $this->pdo->commit();
            return $result;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("ERROR: PayrollManager->deletePayroll failed: " . $e->getMessage());
            throw new Exception("Failed to delete payroll draft: " . $e->getMessage());
        }
    }
    
    /**
     * Regenerates a pending payroll. This deletes the existing draft and creates a new one.
     *
     * @param int $month The payroll month.
     * @param int $year The payroll year.
     * @return array The newly generated payroll data.
     * @throws Exception If regeneration fails.
     */
    public function regeneratePayroll(int $month, int $year): array {
        // Find the existing pending payroll
        $existingPayroll = $this->getPayrollHistoryByPeriod($month, $year);
        if (!$existingPayroll || $existingPayroll['status'] !== 'Pending') {
            throw new Exception("Cannot regenerate. No pending payroll draft found for this period.");
        }
    
        // Delete the existing payroll first
        $this->deletePayroll($existingPayroll['id']);
        
        // Now, generate and save a new one.
        // Note: You need to retrieve the department IDs from the template if this was generated from one.
        // This is a simplification for now.
        $newPayroll = $this->generateAndSavePayroll($month, $year, []); 
        return $newPayroll;
    }
    
    /**
     * A helper method to fetch a payroll record by period.
     *
     * @param int $month
     * @param int $year
     * @return array|null
     */
    private function getPayrollHistoryByPeriod(int $month, int $year): ?array {
        $sql = "SELECT id, status FROM payroll_history WHERE pay_period_month = ? AND pay_period_year = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$month, $year]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Fetches employees and their related data for payroll calculation.
     * Placeholder method that needs to be implemented.
     *
     * @param int $month
     * @param int $year
     * @param array $departmentIds
     * @return array
     */
    private function getEmployeesForPayroll(int $month, int $year, array $departmentIds = []): array {
        // This is where you would query your employees table along with other related tables
        // (e.g., allowances, deductions, reimbursements for the given period).
        // This is the most complex part of the payroll logic.
        // Here's a mock implementation:
        return [
            ['id' => 1, 'full_name' => 'John Doe', 'designation' => 'Manager', 'department_name' => 'HR', 'basic_salary' => 5000, 'housing_allowance' => 1000, 'transportation_allowance' => 500],
            ['id' => 2, 'full_name' => 'Jane Smith', 'designation' => 'Developer', 'department_name' => 'IT', 'basic_salary' => 6000, 'housing_allowance' => 1200, 'transportation_allowance' => 600],
        ];
    }
}
}