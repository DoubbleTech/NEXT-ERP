<?php // classes/MedicalClaimManager.php
class MedicalClaimManager {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getMedicalClaimsByEmployee($employeeId) {
        $stmt = $this->pdo->prepare("SELECT id, amount, date, description, status FROM employee_medical_claims WHERE employee_id = ? ORDER BY date DESC");
        $stmt->execute([$employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMedicalClaimById($id, $employeeId) {
        $stmt = $this->pdo->prepare("SELECT id, amount, date, description, status FROM employee_medical_claims WHERE id = ? AND employee_id = ? LIMIT 1");
        $stmt->execute([$id, $employeeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function addMedicalClaim($employeeId, $amount, $date, $description, $status = 'Pending') {
        $stmt = $this->pdo->prepare("INSERT INTO employee_medical_claims (employee_id, amount, date, description, status) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$employeeId, $amount, $date, $description, $status]);
    }

    public function updateMedicalClaim($id, $employeeId, $amount, $date, $description, $status) {
        $stmt = $this->pdo->prepare("UPDATE employee_medical_claims SET amount = ?, date = ?, description = ?, status = ?, updated_at = NOW() WHERE id = ? AND employee_id = ?");
        return $stmt->execute([$amount, $date, $description, $status, $id, $employeeId]);
    }

    public function deleteMedicalClaim($id, $employeeId) {
        $stmt = $this->pdo->prepare("DELETE FROM employee_medical_claims WHERE id = ? AND employee_id = ?");
        return $stmt->execute([$id, $employeeId]);
    }
}
