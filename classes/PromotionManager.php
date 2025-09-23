<?php // classes/PromotionManager.php
class PromotionManager {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getPromotionsByEmployee($employeeId) {
        $stmt = $this->pdo->prepare("SELECT id, old_designation, new_designation, old_basic_salary, new_basic_salary, effective_date as date, reason FROM employee_promotions WHERE employee_id = ? ORDER BY effective_date DESC");
        $stmt->execute([$employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPromotionById($id, $employeeId) {
        $stmt = $this->pdo->prepare("SELECT id, old_designation, new_designation, old_basic_salary, new_basic_salary, effective_date as date, reason FROM employee_promotions WHERE id = ? AND employee_id = ? LIMIT 1");
        $stmt->execute([$id, $employeeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function addPromotion($employeeId, $newDesignation, $newBasicSalary, $effectiveDate, $reason) {
        // Optionally fetch old designation and salary from hr_employees if not provided
        $oldDesignation = null;
        $oldBasicSalary = null;
        $employeeStmt = $this->pdo->prepare("SELECT designation, basic_salary FROM hr_employees WHERE id = ? LIMIT 1");
        $employeeStmt->execute([$employeeId]);
        $employeeData = $employeeStmt->fetch(PDO::FETCH_ASSOC);
        if ($employeeData) {
            $oldDesignation = $employeeData['designation'];
            $oldBasicSalary = $employeeData['basic_salary'];
        }

        $stmt = $this->pdo->prepare("INSERT INTO employee_promotions (employee_id, old_designation, new_designation, old_basic_salary, new_basic_salary, effective_date, reason) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $success = $stmt->execute([$employeeId, $oldDesignation, $newDesignation, $oldBasicSalary, $newBasicSalary, $effectiveDate, $reason]);

        // IMPORTANT: If promotion affects basic_salary/designation, you might want to UPDATE hr_employees here too
        if ($success) {
            $updateEmployeeStmt = $this->pdo->prepare("UPDATE hr_employees SET designation = ?, basic_salary = ? WHERE id = ?");
            $updateEmployeeStmt->execute([$newDesignation, $newBasicSalary, $employeeId]);
        }
        return $success;
    }

    public function updatePromotion($id, $employeeId, $newDesignation, $newBasicSalary, $effectiveDate, $reason) {
        // Fetch old data just in case, though not used directly for update here
        $oldPromotionStmt = $this->pdo->prepare("SELECT old_designation, old_basic_salary FROM employee_promotions WHERE id = ? AND employee_id = ? LIMIT 1");
        $oldPromotionStmt->execute([$id, $employeeId]);
        $oldPromotionData = $oldPromotionStmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare("UPDATE employee_promotions SET new_designation = ?, new_basic_salary = ?, effective_date = ?, reason = ?, updated_at = NOW() WHERE id = ? AND employee_id = ?");
        $success = $stmt->execute([$newDesignation, $newBasicSalary, $effectiveDate, $reason, $id, $employeeId]);

        // IMPORTANT: If promotion affects basic_salary/designation, you might want to UPDATE hr_employees here too
        // This is complex as previous increments might override. For simplicity, we just update the promotion record itself.
        // A full system would re-evaluate the employee's current salary/designation based on all active promotions.
        if ($success && $newBasicSalary !== null) {
            $updateEmployeeStmt = $this->pdo->prepare("UPDATE hr_employees SET designation = ?, basic_salary = ? WHERE id = ?");
            $updateEmployeeStmt->execute([$newDesignation, $newBasicSalary, $employeeId]);
        }
        return $success;
    }

    public function deletePromotion($id, $employeeId) {
        $stmt = $this->pdo->prepare("DELETE FROM employee_promotions WHERE id = ? AND employee_id = ?");
        return $stmt->execute([$id, $employeeId]);
    }
}
