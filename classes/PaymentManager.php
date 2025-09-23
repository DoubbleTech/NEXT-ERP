<?php
// In classes/PaymentManager.php

class PaymentManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function addPayment($data) {
        $paymentMonth = date('Y-m', strtotime($data['payment_date']));

        $sql = "INSERT INTO one_time_payments (employee_id, amount, payment_month, remarks, created_at) 
                VALUES (:employee_id, :amount, :payment_month, :remarks, NOW())";
            
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':employee_id' => $data['employee_id'],
                ':amount' => $data['amount'],
                ':payment_month' => $paymentMonth,
                ':remarks' => $data['remarks']
            ]);
        } catch (PDOException $e) {
            // MODIFICATION: Re-throw the exception for better error reporting in the API.
            throw new Exception("Database error adding payment: " . $e->getMessage());
        }
    }
}