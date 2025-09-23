<?php
/**
 * FNF-specific database functions
 */

function getEmployeesForSettlement() {
    global $pdo;
    
    $sql = "SELECT e.*, d.department_name, 
                   s.id as settlement_id, s.status as settlement_status,
                   CASE WHEN s.id IS NOT NULL THEN 1 ELSE 0 END as has_pending_settlement
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN fnf_settlements s ON s.employee_id = e.id AND s.status != 'COMPLETED'
            WHERE e.employee_status IN ('RESIGNED', 'LEFT', 'TERMINATED')
            AND (s.id IS NULL OR s.status != 'COMPLETED')
            ORDER BY e.termination_date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function initiateSettlement($employeeId, $initiatedBy) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Check if employee exists and is eligible
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE id = ? AND employee_status IN ('RESIGNED', 'LEFT', 'TERMINATED')");
        $stmt->execute([$employeeId]);
        if (!$stmt->fetch()) {
            throw new Exception("Employee not eligible for settlement");
        }
        
        // Create settlement record
        $sql = "INSERT INTO fnf_settlements 
                (employee_id, initiated_by, status, created_at) 
                VALUES (?, ?, 'PENDING_DEPT_APPROVAL', NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$employeeId, $initiatedBy]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Settlement initiation failed: " . $e->getMessage());
        return false;
    }
}

function approveSettlement($settlementId, $approvedBy) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Verify settlement exists and is in correct status
        $stmt = $pdo->prepare("SELECT id FROM fnf_settlements WHERE id = ? AND status = 'PENDING_DEPT_APPROVAL'");
        $stmt->execute([$settlementId]);
        if (!$stmt->fetch()) {
            throw new Exception("Settlement not found or already processed");
        }
        
        // Update status
        $sql = "UPDATE fnf_settlements 
                SET status = 'PENDING_HR_PROCESSING', 
                    dept_approved_by = ?,
                    dept_approved_at = NOW()
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$approvedBy, $settlementId]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Settlement approval failed: " . $e->getMessage());
        return false;
    }
}

// Similar functions for processSettlement() and completeSettlement()
?>
