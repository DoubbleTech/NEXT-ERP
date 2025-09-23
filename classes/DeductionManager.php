<?php
// classes/DeductionManager.php

// Ensure class is not redefined if included multiple times
if (!class_exists('DeductionManager')) {

/**
 * DeductionManager Class
 *
 * Manages all business logic and database interactions for employee deductions,
 * excluding loans and advances which are typically handled by EmployeeManager
 * or a more specific Loan/Advance Manager.
 * Deductions are typically a type of 'deduction' in the 'employee_financial_transactions' table.
 */
class DeductionManager {
    private $pdo; // PDO database connection object

    /**
     * Constructor for DeductionManager.
     *
     * @param PDO $pdo The PDO database connection object.
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Retrieves all general deduction records for a specific employee, or all if employeeId is null.
     * Excludes 'Loan' and 'Advance' types, as they might be managed differently.
     *
     * @param int|null $employeeId Optional. The ID of the employee to fetch deductions for.
     * @return array Array of deduction records.
     * @throws Exception On database query failure.
     */
    public function getDeductions($employeeId = null) {
        try {
            $sql = "SELECT eft.*, e.full_name, e.employee_number, e.currency
                    FROM employee_financial_transactions eft
                    JOIN employees e ON eft.employee_id = e.id
                    WHERE eft.transaction_type = 'deduction' AND eft.type NOT IN ('Loan', 'Advance')"; // Filter by general deduction type

            $params = [];
            if ($employeeId !== null) {
                $sql .= " AND eft.employee_id = :employee_id";
                $params[':employee_id'] = $employeeId;
            }

            $sql .= " ORDER BY eft.transaction_date DESC, e.full_name ASC";
            error_log("DEBUG SQL (DeductionManager->getDeductions): " . $sql); // DEBUG LOG

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ERROR: DeductionManager->getDeductions failed: " . $e->getMessage());
            throw new Exception("Could not retrieve deduction list: " . $e->getMessage());
        }
    }

    /**
     * Adds a new general deduction record for an employee.
     * This method is for deductions that are NOT classified as a 'Loan' or 'Advance'.
     *
     * @param int $employeeId The ID of the employee receiving the deduction.
     * @param string $type The specific type of deduction (e.g., 'Fine', 'General Deduction', 'Other').
     * @param float $amount The deduction amount.
     * @param string $date The date the deduction was applied.
     * @param string|null $remarks Optional remarks for the deduction.
     * @return int The ID of the newly inserted deduction record.
     * @throws Exception On validation errors or database insert failure.
     */
    public function addDeduction($employeeId, $type, $amount, $date, $remarks = null) {
        if (empty($employeeId) || empty($type) || $amount <= 0 || empty($date)) {
            throw new Exception("Employee ID, type, positive amount, and date are required to add a deduction.");
        }
        if ($type === 'Loan' || $type === 'Advance') {
            throw new Exception("Use the dedicated Loan/Advance methods for Loan or Advance transactions.");
        }

        try {
            $sql = "INSERT INTO employee_financial_transactions
                        (employee_id, transaction_type, type, amount, transaction_date, remarks, created_at, updated_at)
                    VALUES (:employee_id, 'deduction', :type, :amount, :transaction_date, :remarks, NOW(), NOW())";
            error_log("DEBUG SQL (DeductionManager->addDeduction): " . $sql); // DEBUG LOG
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':employee_id' => $employeeId,
                ':type' => $type,
                ':amount' => $amount,
                ':transaction_date' => $date,
                ':remarks' => $remarks
            ]);
            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("ERROR: DeductionManager->addDeduction failed: " . $e->getMessage());
            throw new Exception("Failed to add deduction: " . $e->getMessage());
        }
    }

    /**
     * Updates an existing general deduction record.
     *
     * @param int $deductionId The ID of the deduction record to update.
     * @param string $type The new specific type of deduction.
     * @param float $amount The new deduction amount.
     * @param string $date The new date of the deduction.
     * @param string|null $remarks New remarks for the deduction.
     * @return bool True on success.
     * @throws Exception On validation errors or database update failure.
     */
    public function updateDeduction($deductionId, $type, $amount, $date, $remarks = null) {
        if (empty($deductionId) || empty($type) || $amount <= 0 || empty($date)) {
            throw new Exception("Deduction ID, type, positive amount, and date are required to update a deduction.");
        }
        if ($type === 'Loan' || $type === 'Advance') {
            throw new Exception("Cannot update general deduction to a Loan or Advance type.");
        }

        try {
            $sql = "UPDATE employee_financial_transactions
                    SET type = :type, amount = :amount, transaction_date = :transaction_date, remarks = :remarks, updated_at = NOW()
                    WHERE id = :id AND transaction_type = 'deduction' AND type NOT IN ('Loan', 'Advance')"; // Ensure we only update general deduction records
            error_log("DEBUG SQL (DeductionManager->updateDeduction): " . $sql); // DEBUG LOG
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':type' => $type,
                ':amount' => $amount,
                ':transaction_date' => $date,
                ':remarks' => $remarks,
                ':id' => $deductionId
            ]);
        } catch (Exception $e) {
            error_log("ERROR: DeductionManager->updateDeduction failed: " . $e->getMessage());
            throw new Exception("Failed to update deduction: " . $e->getMessage());
        }
    }

    /**
     * Deletes a general deduction record by its ID.
     *
     * @param int $deductionId The ID of the deduction record to delete.
     * @return bool True on success.
     * @throws Exception On database delete failure.
     */
    public function deleteDeduction($deductionId) {
        if (empty($deductionId)) {
            throw new Exception("Deduction ID is required for deletion.");
        }

        try {
            $sql = "DELETE FROM employee_financial_transactions WHERE id = :id AND transaction_type = 'deduction' AND type NOT IN ('Loan', 'Advance')"; // Ensure only general deduction is deleted
            error_log("DEBUG SQL (DeductionManager->deleteDeduction): " . $sql); // DEBUG LOG
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id' => $deductionId]);
        } catch (Exception $e) {
            error_log("ERROR: DeductionManager->deleteDeduction failed: " . $e->getMessage());
            throw new Exception("Failed to delete deduction: " . $e->getMessage());
        }
    }
}
} // End if(!class_exists('DeductionManager'))
?>