<?php
// classes/ReportGenerator.php

class ReportGenerator {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function generateEmployeeReport($reportType, $params) {
        try {
            $report_data = [];
            $sql = "";
            $queryParams = [];

            switch ($reportType) {
                case 'by_department':
                    $sql = "SELECT 
                                e.id, 
                                e.employee_number, 
                                e.full_name, 
                                e.designation, 
                                d.department_name, 
                                e.employee_status, 
                                e.date_of_joining, 
                                e.basic_salary,
                                e.currency
                            FROM hr_employees e
                            LEFT JOIN departments d ON e.department_id = d.id";
                    
                    if (!empty($params['department_id'])) {
                        $sql .= " WHERE e.department_id = :department_id";
                        $queryParams[':department_id'] = $params['department_id'];
                    }
                    $sql .= " ORDER BY d.department_name, e.full_name";
                    break;

                case 'by_hiring_date':
                    $sql = "SELECT 
                                e.id, 
                                e.employee_number, 
                                e.full_name, 
                                e.designation, 
                                d.department_name, 
                                e.date_of_joining, 
                                e.basic_salary,
                                e.currency
                            FROM hr_employees e
                            LEFT JOIN departments d ON e.department_id = d.id
                            WHERE 1=1";
                    
                    if (!empty($params['hiring_month'])) {
                        $sql .= " AND DATE_FORMAT(e.date_of_joining, '%Y-%m') = :hiring_month";
                        $queryParams[':hiring_month'] = $params['hiring_month'];
                    }
                    if (!empty($params['hiring_year'])) {
                        $sql .= " AND YEAR(e.date_of_joining) = :hiring_year";
                        $queryParams[':hiring_year'] = $params['hiring_year'];
                    }
                    $sql .= " ORDER BY e.date_of_joining ASC";
                    break;

                case 'by_salary_range':
                    $sql = "SELECT 
                                e.id, 
                                e.employee_number, 
                                e.full_name, 
                                e.designation, 
                                d.department_name, 
                                e.basic_salary,
                                e.currency,
                                e.employee_status
                            FROM hr_employees e
                            LEFT JOIN departments d ON e.department_id = d.id
                            WHERE 1=1";
                    
                    if (isset($params['min_salary']) && is_numeric($params['min_salary'])) {
                        $sql .= " AND e.basic_salary >= :min_salary";
                        $queryParams[':min_salary'] = $params['min_salary'];
                    }
                    if (isset($params['max_salary']) && is_numeric($params['max_salary'])) {
                        $sql .= " AND e.basic_salary <= :max_salary";
                        $queryParams[':max_salary'] = $params['max_salary'];
                    }
                    $sql .= " ORDER BY e.basic_salary DESC";
                    break;

                case 'by_service_year':
                    $sql = "SELECT 
                                e.id, 
                                e.employee_number, 
                                e.full_name, 
                                e.designation, 
                                d.department_name, 
                                e.date_of_joining, 
                                e.basic_salary,
                                e.currency,
                                TIMESTAMPDIFF(YEAR, e.date_of_joining, CURDATE()) AS service_years
                            FROM hr_employees e
                            LEFT JOIN departments d ON e.department_id = d.id
                            WHERE TIMESTAMPDIFF(YEAR, e.date_of_joining, CURDATE()) >= :service_years
                            ORDER BY service_years DESC";
                    $queryParams[':service_years'] = $params['service_years'] ?? 0;
                    break;

                case 'by_status':
                    $sql = "SELECT 
                                e.id, 
                                e.employee_number, 
                                e.full_name, 
                                e.designation, 
                                d.department_name, 
                                e.employee_status, 
                                e.date_of_joining
                            FROM hr_employees e
                            LEFT JOIN departments d ON e.department_id = d.id
                            WHERE 1=1";
                    
                    if (!empty($params['employee_status'])) {
                        $sql .= " AND e.employee_status = :employee_status";
                        $queryParams[':employee_status'] = $params['employee_status'];
                    }
                    $sql .= " ORDER BY e.employee_status, e.full_name";
                    break;

                case 'full_employee_data':
                    $sql = "SELECT
                                e.id,
                                e.employee_number,
                                e.full_name,
                                e.identity_card_number,
                                e.date_of_joining,
                                e.date_of_birth,
                                e.contact_email,
                                e.contact_mobile,
                                e.address,
                                e.country,
                                e.bank_name,
                                e.bank_iban,
                                d.department_name,
                                e.designation,
                                e.basic_salary,
                                e.currency,
                                e.tax_payer_id,
                                e.overtime_rate_multiplier,
                                e.employee_status,
                                e.avatar_url,
                                e.created_at
                            FROM hr_employees e
                            LEFT JOIN departments d ON e.department_id = d.id
                            ORDER BY e.full_name";
                    break;

                case 'increment_due_report':
                    $sql = "SELECT
                                e.id,
                                e.full_name,
                                e.designation,
                                d.department_name,
                                e.date_of_joining,
                                e.basic_salary,
                                e.currency,
                                p.new_basic_salary AS last_increment_salary,
                                p.effective_date AS last_increment_date
                            FROM hr_employees e
                            LEFT JOIN departments d ON e.department_id = d.id
                            LEFT JOIN employee_promotions p ON e.id = p.employee_id
                            WHERE e.employee_status = 'active'
                            AND (
                                p.effective_date IS NULL OR
                                p.effective_date = (
                                    SELECT MAX(p2.effective_date) 
                                    FROM employee_promotions p2 
                                    WHERE p2.employee_id = e.id
                                )
                            )
                            ORDER BY e.full_name";
                    break;

                case 'birthday_notification':
                    $currentMonth = date('m');
                    $nextThreeMonths = [
                        date('m'),
                        date('m', strtotime('+1 month')),
                        date('m', strtotime('+2 months'))
                    ];
                    $nextThreeMonths = array_unique($nextThreeMonths);

                    $sql = "SELECT
                                e.id,
                                e.full_name,
                                e.date_of_birth,
                                e.contact_email,
                                e.contact_mobile,
                                d.department_name
                            FROM hr_employees e
                            LEFT JOIN departments d ON e.department_id = d.id
                            WHERE e.employee_status = 'active'
                            AND MONTH(e.date_of_birth) IN (" . implode(',', $nextThreeMonths) . ")
                            ORDER BY MONTH(e.date_of_birth), DAY(e.date_of_birth)";
                    break;

                case 'employee_advances':
                    $sql = "SELECT
                                e.id,
                                e.full_name,
                                l.loan_amount,
                                l.monthly_deduction_amount,
                                l.outstanding_balance,
                                l.start_date,
                                l.end_date,
                                l.status AS loan_status,
                                d.department_name
                            FROM hr_employees e
                            JOIN employee_loans l ON e.id = l.employee_id
                            LEFT JOIN departments d ON e.department_id = d.id
                            WHERE l.status = 'Active'
                            ORDER BY e.full_name, l.start_date DESC";
                    break;

                default:
                    throw new Exception("Unknown report type: " . $reportType);
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($queryParams);
            $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $report_data;
        } catch (Exception $e) {
            error_log("ReportGenerator Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function generateAndSaveReportFile($reportType, $params, $format = 'xls') {
        try {
            $reportData = $this->generateEmployeeReport($reportType, $params);

            if (empty($reportData)) {
                throw new Exception('No data to generate report.');
            }

            // Generate a unique filename
            $filename = 'reports/' . uniqid('report_') . '.' . ($format === 'xls' ? 'xlsx' : 'pdf');
            $fullPath = __DIR__ . '/../' . $filename;

            // Ensure reports directory exists
            if (!is_dir(__DIR__ . '/../reports')) {
                mkdir(__DIR__ . '/../reports', 0777, true);
            }

            if ($format === 'xls') {
                $this->generateExcelFile($reportData, $fullPath);
            } elseif ($format === 'pdf') {
                $this->generatePdfFile($reportData, $fullPath);
            } else {
                throw new Exception('Unsupported format: ' . $format);
            }

            return $filename;
        } catch (Exception $e) {
            error_log("Error generating report file: " . $e->getMessage());
            throw $e;
        }
    }

    private function generateExcelFile($data, $filepath) {
        try {
            // Check if PhpSpreadsheet is available
            if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                throw new Exception('PhpSpreadsheet library not available.');
            }

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Write headers
            $headers = array_keys($data[0]);
            $colLetter = 'A';
            foreach ($headers as $colIndex => $header) {
                $sheet->setCellValue($colLetter . '1', $header);
                $colLetter++;
            }

            // Write data rows
            $rowNum = 2;
            foreach ($data as $row) {
                $colLetter = 'A';
                foreach ($row as $cell) {
                    $sheet->setCellValue($colLetter . $rowNum, $cell);
                    $colLetter++;
                }
                $rowNum++;
            }

            // Save the Excel file
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($filepath);
        } catch (Exception $e) {
            error_log("Error generating Excel file: " . $e->getMessage());
            throw $e;
        }
    }

    private function generatePdfFile($data, $filepath) {
        try {
            // Check if TCPDF is available
            if (!class_exists('TCPDF')) {
                throw new Exception('TCPDF library not available.');
            }

            $pdf = new TCPDF();
            $pdf->SetCreator('FinLab ERP');
            $pdf->SetAuthor('FinLab ERP');
            $pdf->SetTitle('Employee Report');
            $pdf->AddPage();

            // Basic table header
            $headers = array_keys($data[0]);
            $html = '<table border="1" cellpadding="4"><tr>';
            foreach ($headers as $header) {
                $html .= '<th>' . htmlspecialchars($header) . '</th>';
            }
            $html .= '</tr>';

            // Data rows
            foreach ($data as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $html .= '<td>' . htmlspecialchars($cell) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</table>';

            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->Output($filepath, 'F');
        } catch (Exception $e) {
            error_log("Error generating PDF file: " . $e->getMessage());
            throw $e;
        }
    }
}