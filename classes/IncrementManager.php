<?php // classes/IncrementManager.php
class IncrementManager {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getIncrementsByEmployee($employeeId) {
        $stmt = $this->pdo->prepare("SELECT id, amount, effective_date as date, reason FROM employee_increments WHERE employee_id = ? ORDER BY effective_date DESC");
        $stmt->execute([$employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getIncrementById($id, $employeeId) {
        $stmt = $this->pdo->prepare("SELECT id, amount, effective_date as date, reason FROM employee_increments WHERE id = ? AND employee_id = ? LIMIT 1");
        $stmt->execute([$id, $employeeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function addIncrement($employeeId, $amount, $effectiveDate, $reason) {
        $stmt = $this->pdo->prepare("INSERT INTO employee_increments (employee_id, amount, effective_date, reason) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$employeeId, $amount, $effectiveDate, $reason]);
    }

    public function updateIncrement($id, $employeeId, $amount, $effectiveDate, $reason) {
        $stmt = $this->pdo->prepare("UPDATE employee_increments SET amount = ?, effective_date = ?, reason = ?, updated_at = NOW() WHERE id = ? AND employee_id = ?");
        return $stmt->execute([$amount, $effectiveDate, $reason, $id, $employeeId]);
    }

    public function deleteIncrement($id, $employeeId) {
        $stmt = $this->pdo->prepare("DELETE FROM employee_increments WHERE id = ? AND employee_id = ?");
        return $stmt->execute([$id, $employeeId]);
    }
}
