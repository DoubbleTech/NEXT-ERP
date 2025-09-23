<?php
// classes/LoanManager.php

class LoanManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Adds a new loan to the database.
     *
     * @param array $data The loan data from the form.
     * @return bool True on success, false on failure.
     * @throws Exception
     */
    public function addLoan($data) {
        // FIX: Using unique placeholders to prevent parameter count errors.
        $sql = "INSERT INTO loans (
                    employee_id, 
                    loan_amount, 
                    monthly_deduction_amount, 
                    start_date, 
                    remaining_balance, 
                    status, 
                    notes, 
                    created_at
                ) VALUES (
                    :employee_id, 
                    :loan_amount, 
                    :monthly_deduction_amount, 
                    :start_date, 
                    :remaining_balance, 
                    'active', 
                    :notes, 
                    NOW()
                )";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            
            // FIX: Binding a value to the new :remaining_balance placeholder.
            return $stmt->execute([
                ':employee_id' => $data['employee_id'],
                ':loan_amount' => $data['loan_amount'],
                ':monthly_deduction_amount' => $data['monthly_deduction_amount'],
                ':start_date' => $data['start_date'],
                ':remaining_balance' => $data['loan_amount'], // Set initial balance to the full loan amount
                ':notes' => $data['notes']
            ]);
        } catch (PDOException $e) {
            // Re-throw the exception for better error reporting in the API.
            throw new Exception("Database error adding loan: " . $e->getMessage());
        }
    }
}