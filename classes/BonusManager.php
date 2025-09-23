<?php
// classes/BonusManager.php

// Ensure class is not redefined if included multiple times
if (!class_exists('BonusManager')) {

/**
 * BonusManager Class
 *
 * Manages all business logic and database interactions for employee bonuses.
 * Bonuses are typically a type of 'earning' in the 'employee_financial_transactions' table.
 */
class BonusManager {
    private $pdo; // PDO database connection object

    /**
     * Constructor for BonusManager.
     *
     * @param PDO $pdo The PDO database connection object.
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Retrieves all bonus records for a specific employee, or all bonuses if employeeId is null.
     *
     * @param int|null $employeeId Optional. The ID of the employee to fetch bonuses for.
     * @return array Array of bonus records.
     * @throws Exception On database query failure.
     */
    public function getBonuses($employeeId = null) {
        try {
            $sql = "SELECT eft.*, e.full_name, e.employee_number, e.currency
                    FROM employee_financial_transactions eft
                    JOIN employees e ON eft.employee_id = e.id
                    WHERE eft.transaction_type = 'earning' AND eft.type = 'Bonus'"; // Filter by bonus type

            $params = [];
            if ($employeeId !== null) {
                $sql .= " AND eft.employee_id = :employee_id";
                $params[':employee_id'] = $employeeId;
            }

            $sql .= " ORDER BY eft.transaction_date DESC, e.full_name ASC";
            error_log("DEBUG SQL (BonusManager->getBonuses): " . $sql); // DEBUG LOG

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ERROR: BonusManager->getBonuses failed: " . $e->getMessage());
            throw new Exception("Could not retrieve bonus list: " . $e->getMessage());
        }
    }

    /**
     * Adds a new bonus record for an employee.
     *
     * @param int $employeeId The ID of the employee receiving the bonus.
     * @param float $amount The bonus amount.
     * @param string $date The date the bonus was given.
     * @param string|null $remarks Optional remarks for the bonus.
     * @return int The ID of the newly inserted bonus record.
     * @throws Exception On validation errors or database insert failure.
     */
    public function addBonus($employeeId, $amount, $date, $remarks = null) {
        if (empty($employeeId) || $amount <= 0 || empty($date)) {
            throw new Exception("Employee ID, a positive amount, and date are required to add a bonus.");
        }

        try {
            $sql = "INSERT INTO employee_financial_transactions
                        (employee_id, transaction_type, type, amount, transaction_date, remarks, created_at, updated_at)
                    VALUES (:employee_id, 'earning', 'Bonus', :amount, :transaction_date, :remarks, NOW(), NOW())";
            error_log("DEBUG SQL (BonusManager->addBonus): " . $sql); // DEBUG LOG
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':employee_id' => $employeeId,
                ':amount' => $amount,
                ':transaction_date' => $date,
                ':remarks' => $remarks
            ]);
            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("ERROR: BonusManager->addBonus failed: " . $e->getMessage());
            throw new Exception("Failed to add bonus: " . $e->getMessage());
        }
    }

    /**
     * Updates an existing bonus record.
     *
     * @param int $bonusId The ID of the bonus record to update.
     * @param float $amount The new bonus amount.
     * @param string $date The new date of the bonus.
     * @param string|null $remarks New remarks for the bonus.
     * @return bool True on success.
     * @throws Exception On validation errors or database update failure.
     */
    public function updateBonus($bonusId, $amount, $date, $remarks = null) {
        if (empty($bonusId) || $amount <= 0 || empty($date)) {
            throw new Exception("Bonus ID, a positive amount, and date are required to update a bonus.");
        }

        try {
            $sql = "UPDATE employee_financial_transactions
                    SET amount = :amount, transaction_date = :transaction_date, remarks = :remarks, updated_at = NOW()
                    WHERE id = :id AND transaction_type = 'earning' AND type = 'Bonus'"; // Ensure we only update bonus records
            error_log("DEBUG SQL (BonusManager->updateBonus): " . $sql); // DEBUG LOG
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':amount' => $amount,
                ':transaction_date' => $date,
                ':remarks' => $remarks,
                ':id' => $bonusId
            ]);
        } catch (Exception $e) {
            error_log("ERROR: BonusManager->updateBonus failed: " . $e->getMessage());
            throw new Exception("Failed to update bonus: " . $e->getMessage());
        }
    }

    /**
     * Deletes a bonus record by its ID.
     *
     * @param int $bonusId The ID of the bonus record to delete.
     * @return bool True on success.
     * @throws Exception On database delete failure.
     */
    public function deleteBonus($bonusId) {
        if (empty($bonusId)) {
            throw new Exception("Bonus ID is required for deletion.");
        }

        try {
            $sql = "DELETE FROM employee_financial_transactions WHERE id = :id AND transaction_type = 'earning' AND type = 'Bonus'"; // Ensure only bonus is deleted
            error_log("DEBUG SQL (BonusManager->deleteBonus): " . $sql); // DEBUG LOG
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id' => $bonusId]);
        } catch (Exception $e) {
            error_log("ERROR: BonusManager->deleteBonus failed: " . $e->getMessage());
            throw new Exception("Failed to delete bonus: " . $e->getMessage());
        }
    }
}
} // End if(!class_exists('BonusManager'))
?>