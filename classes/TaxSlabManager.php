<?php
// classes/TaxSlabManager.php

if (!class_exists('TaxSlabManager')) {

    class TaxSlabManager {
        private $pdo;

        public function __construct(PDO $pdo) {
            $this->pdo = $pdo;
        }

        /**
         * Fetches all tax slabs from the database.
         * @return array|false An array of tax slab data, or false on failure.
         */
        public function getTaxSlabs() {
            try {
                // Corrected SQL query based on the database schema provided
                $sql = "SELECT id, slab_name, min_annual_income, max_annual_income, base_tax_amount, excess_tax_percentage, country_code FROM tax_slabs ORDER BY min_annual_income ASC";
                $stmt = $this->pdo->query($sql);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("Error fetching tax slabs from database: " . $e->getMessage());
                return false;
            }
        }
        
        /**
         * Calculates the estimated yearly tax based on annual income and country.
         * @param float $annualIncome The employee's total yearly income.
         * @param string $countryCode The employee's country code (e.g., 'PK').
         * @return array An array containing the estimated tax, applicable slab name, and rate.
         * @throws Exception If no tax slabs are found for the given country.
         */
        public function calculateEstimatedYearlyTax($annualIncome, $countryCode = 'PK') {
            try {
                // Corrected SQL query to match the database schema provided
                $sql = "SELECT id, slab_name, min_annual_income, max_annual_income, base_tax_amount, excess_tax_percentage FROM tax_slabs WHERE country_code = :country_code ORDER BY min_annual_income ASC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([':country_code' => $countryCode]);
                $taxSlabs = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $estimatedTax = 0.00;
                $applicableSlab = null;

                if (empty($taxSlabs)) {
                    throw new Exception("No tax slabs found for country code: {$countryCode}.");
                }

                foreach ($taxSlabs as $slab) {
                    $min = (float) $slab['min_annual_income'];
                    $max = (float) ($slab['max_annual_income'] === null ? PHP_FLOAT_MAX : $slab['max_annual_income']);
                    $fixed = (float) $slab['base_tax_amount'];
                    $rate = (float) $slab['excess_tax_percentage'];

                    if ($annualIncome >= $min && $annualIncome <= $max) {
                        $applicableSlab = $slab;
                        $taxableAmountInSlab = max(0, $annualIncome - $min);
                        $estimatedTax = $fixed + ($taxableAmountInSlab * ($rate / 100));
                        break;
                    }
                }

                return [
                    'estimated_tax' => $estimatedTax,
                    'tax_slab_name' => $applicableSlab ? $applicableSlab['slab_name'] : 'N/A',
                    'rate_percentage' => $applicableSlab ? $applicableSlab['excess_tax_percentage'] : 0,
                    'currency' => 'PKR' // Assuming PKR for Pakistan, or this could be dynamic
                ];
            } catch (Exception $e) {
                error_log("Error calculating tax details: " . $e->getMessage());
                return [
                    'estimated_tax' => 0,
                    'tax_slab_name' => 'Error',
                    'rate_percentage' => 0,
                    'currency' => 'PKR'
                ];
            }
        }
    }
}