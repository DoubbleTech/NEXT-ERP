<?php
// classes/ReimbursementManager.php - CORRECTED AND UPDATED CONTENT (FOR ReimbursementManager CLASS ONLY)
// Added dependency: PayrollManager for unlinking when status reverts from Approved.
// Assuming PayrollManager is in classes/PayrollManager.php
// If it's not needed directly in this file's namespace, then the 'use' statement isn't strictly required
// but it's good for clarity if its methods are called.
// For the constructor, type-hinting 'PayrollManager' will pull it in.


class ReimbursementManager {
    private $pdo;
    private $uploadDir = 'uploads/reimbursements/';
    private $payrollManager; // New: Dependency for PayrollManager

    /**
     * Constructor for ReimbursementManager.
     * @param PDO $pdo The PDO database connection object.
     * @param PayrollManager $payrollManager The PayrollManager instance.
     */
    public function __construct(PDO $pdo, PayrollManager $payrollManager) {
        $this->pdo = $pdo;
        $this->payrollManager = $payrollManager; // Store the instance
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    /**
     * Get all reimbursement claims with summary and pagination.
     *
     * @param int $offset The starting offset for pagination.
     * @param int $limit The number of claims to retrieve.
     * @param string $searchQuery Optional search query for title or employee name.
     * @param string $statusFilter Optional status to filter by (e.g., 'Approved', 'Pending').
     * @return array Contains 'summary' of all claims and 'data' for the paginated subset, and 'total_filtered_claims'.
     */
    public function getClaims($offset = 0, $limit = 30, $searchQuery = null, $statusFilter = null) {
        try {
            // --- Conditions and Parameters for both total_filtered_claims and actual data ---
            $commonConditions = [];
            $commonParams = [];

            if ($searchQuery) {
                $searchParts = [];
                $searchPartParams = [];

                // 1. Search by Claim Title or Employee Full Name
                $searchParts[] = "(r.claim_title LIKE ? OR he.full_name LIKE ?)";
                $searchPartParams[] = '%' . $searchQuery . '%';
                $searchPartParams[] = '%' . $searchQuery . '%';

                // 2. Search by Claim ID (handle "CLAIM-XXXX" or just "XXXX")
                $claimIdToSearch = null;
                if (preg_match('/^CLAIM-(\d+)$/i', $searchQuery, $matches)) {
                    $claimIdToSearch = (int)$matches[1];
                } elseif (is_numeric($searchQuery)) {
                    $claimIdToSearch = (int)$searchQuery;
                }

                if ($claimIdToSearch !== null && $claimIdToSearch > 0) {
                    $searchParts[] = "r.id = ?";
                    $searchPartParams[] = $claimIdToSearch;
                }
                
                // Combine all search parts with OR
                if (!empty($searchParts)) {
                    $commonConditions[] = "(" . implode(' OR ', $searchParts) . ")";
                    $commonParams = array_merge($commonParams, $searchPartParams);
                }
            }
            if ($statusFilter) { // Apply status filter to the table data query
                $commonConditions[] = "r.status = ?";
                $commonParams[] = $statusFilter;
            }

            // Get total count of filtered claims (for "View More" logic)
            $countSql = "SELECT COUNT(r.id) FROM employee_reimbursements r JOIN hr_employees he ON r.employee_id = he.id";
            if (!empty($commonConditions)) {
                $countSql .= " WHERE " . implode(' AND ', $commonConditions);
            }
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute($commonParams); // Execute with full filters
            $totalFilteredClaims = $countStmt->fetchColumn();


            // --- Logic for 'summary' totals (total_claims, approved, pending, rejected counts/amounts) ---
            // This summary *typically* reflects the overall state, possibly filtered by search,
            // but usually *not* by the specific status filter of the table display itself.
            // This is now handled by the separate getSummaryDataInternal method.
            
            // Then, get the paginated subset of data (with all filters applied, including status)
            $dataSql = "SELECT r.id, r.employee_id, r.claim_title, r.claim_date,
                               r.total_amount, r.status, he.full_name AS employee_name,
                               r.submission_date
                        FROM employee_reimbursements r
                        JOIN hr_employees he ON r.employee_id = he.id";
            
            $dataSqlParams = $commonParams;

            if (!empty($commonConditions)) {
                $dataSql .= " WHERE " . implode(' AND ', $commonConditions);
            }

            $dataSql .= " ORDER BY r.claim_date DESC LIMIT ?, ?";
            $dataSqlParams[] = $offset;
            $dataSqlParams[] = $limit;

            $dataStmt = $this->pdo->prepare($dataSql);
            $dataStmt->execute($dataSqlParams);
            $paginatedData = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

            // Note: The 'summary' data in this return is now just a placeholder or could be removed if getSummary is always used.
            // For now, I'll return a minimal summary, as the frontend uses updateSummaryCards().
            return [
                'summary' => $this->getSummaryDataInternal($searchQuery), // Call internal helper for summary
                'data' => $paginatedData,
                'total_filtered_claims' => $totalFilteredClaims
            ];
        } catch (Exception $e) {
            error_log("Error in getClaims: " . $e->getMessage());
            return [
                'summary' => $this->getSummaryDataInternal(null), // On error, return empty summary
                'data' => [],
                'total_filtered_claims' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Internal helper to get summary data.
     * Can be called by getClaims or the new dedicated API endpoint.
     */
    private function getSummaryDataInternal($searchQuery = null) {
        $summarySql = "SELECT r.total_amount, r.status
                       FROM employee_reimbursements r
                       JOIN hr_employees he ON r.employee_id = he.id";
        $summaryParams = [];
        $summaryConditions = [];

        if ($searchQuery) {
            $searchParts = [];
            $searchPartParams = [];

            // 1. Search by Claim Title or Employee Full Name
            $searchParts[] = "(r.claim_title LIKE ? OR he.full_name LIKE ?)";
            $searchPartParams[] = '%' . $searchQuery . '%';
            $searchPartParams[] = '%' . $searchQuery . '%';

            // 2. Search by Claim ID (handle "CLAIM-XXXX" or just "XXXX")
            $claimIdToSearch = null;
            if (preg_match('/^CLAIM-(\d+)$/i', $searchQuery, $matches)) {
                $claimIdToSearch = (int)$matches[1];
            } elseif (is_numeric($searchQuery)) {
                $claimIdToSearch = (int)$searchQuery;
            }

            if ($claimIdToSearch !== null && $claimIdToSearch > 0) {
                $searchParts[] = "r.id = ?";
                $searchPartParams[] = $claimIdToSearch;
            }
            
            // Combine all search parts with OR
            if (!empty($searchParts)) {
                $summaryConditions[] = "(" . implode(' OR ', $searchParts) . ")";
                $summaryParams = array_merge($summaryParams, $searchPartParams);
            }
        }
        
        if (!empty($summaryConditions)) {
            $summarySql .= " WHERE " . implode(' AND ', $summaryConditions);
        }
        $summaryStmt = $this->pdo->prepare($summarySql);
        $summaryStmt->execute($summaryParams);
        $allClaimsForSummary = $summaryStmt->fetchAll(PDO::FETCH_ASSOC);

        $summary = [
            'total_claims' => count($allClaimsForSummary),
            'total_amount' => 0,
            'pending' => 0,
            'pending_amount' => 0,
            'approved' => 0,
            'approved_amount' => 0,
            'rejected' => 0,
            'rejected_amount' => 0,
            'needs_correction' => 0,
            'needs_correction_amount' => 0
        ];

        foreach ($allClaimsForSummary as $row) {
            $claimAmount = (float)$row['total_amount'];
            $summary['total_amount'] += $claimAmount;

            if ($row['status'] === 'Pending') {
                $summary['pending']++;
                $summary['pending_amount'] += $claimAmount;
            } elseif ($row['status'] === 'Approved') {
                $summary['approved']++;
                $summary['approved_amount'] += $claimAmount;
            } elseif ($row['status'] === 'Rejected') {
                $summary['rejected']++;
                $summary['rejected_amount'] += $claimAmount;
            } elseif ($row['status'] === 'Needs Correction') {
                $summary['needs_correction']++;
                $summary['needs_correction_amount'] += $claimAmount;
            }
        }
        return $summary;
    }


    /**
     * Get reimbursement categories.
     */
    public function getCategories() {
        try {
            $stmt = $this->pdo->query("SELECT id, name FROM reimbursement_categories WHERE name != 'Meal / Food' ORDER BY name ASC");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($categories)) {
                   return [
                       ['id' => 1, 'name' => 'Travel'],
                       ['id' => 3, 'name' => 'Lodging'],
                       ['id' => 4, 'name' => 'Medical'],
                       ['id' => 5, 'name' => 'Miscellaneous']
                   ];
            }
            return $categories;
        } catch (Exception $e) {
            error_log("Error in getCategories: " . $e->getMessage());
            return [
                ['id' => 1, 'name' => 'Travel'],
                ['id' => 3, 'name' => 'Lodging'],
                ['id' => 4, 'name' => 'Medical'],
                ['id' => 5, 'name' => 'Miscellaneous']
            ];
        }
    }

    /**
     * Get single reimbursement details by ID, including its associated line items.
     */
    public function getReimbursementById($id) {
        $this->pdo->beginTransaction();

        try {
            $sql = "SELECT r.id, r.employee_id, r.claim_title, r.claim_date, r.currency, r.total_amount,
                               r.status, r.supervisor_notes, r.processed_by, r.processed_date, r.submission_date,
                               he.full_name as employee_name,
                               appr.full_name as processed_by_name,
                               r.payroll_processed_by_payroll_detail_id AS linked_payroll_detail_id -- New: Fetch linked payroll detail ID
                        FROM employee_reimbursements r
                        JOIN hr_employees he ON r.employee_id = he.id
                        LEFT JOIN hr_employees appr ON r.processed_by = appr.id
                        WHERE r.id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            $claim = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$claim) {
                $this->pdo->rollBack();
                return null;
            }

            $lineItemsSql = "SELECT li.id, li.expense_date, li.category_id, li.description, li.amount, li.attachment_path,
                                 rc.name AS category_name
                                 FROM reimbursement_line_items li
                                 JOIN reimbursement_categories rc ON li.category_id = rc.id
                                 WHERE li.reimbursement_id = ?";
            $lineItemsStmt = $this->pdo->prepare($lineItemsSql);
            $lineItemsStmt->execute([$id]);
            
            $claim['line_items'] = $lineItemsStmt->fetchAll(PDO::FETCH_ASSOC);

            $this->pdo->commit();
            return $claim;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error in getReimbursementById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Add a new reimbursement claim with its associated multiple line items and their attachments.
     */
    public function addReimbursementWithLineItems(array $claimData, array $lineItemsData, array $attachmentsData) {
        $this->pdo->beginTransaction();

        try {
            $mainClaimSql = "INSERT INTO employee_reimbursements
                                 (employee_id, claim_title, claim_date, currency, total_amount, status, submission_date)
                                 VALUES (?, ?, ?, ?, ?, 'Pending', NOW())";
            $mainClaimStmt = $this->pdo->prepare($mainClaimSql);
            $mainClaimSuccess = $mainClaimStmt->execute([
                $claimData['employee_id'],
                $claimData['claim_title'],
                $claimData['claim_date'],
                $claimData['currency'],
                $claimData['total_amount']
            ]);

            if (!$mainClaimSuccess) {
                throw new Exception("Failed to insert main reimbursement claim.");
            }
            $reimbursementId = $this->pdo->lastInsertId();

            $lineItemSql = "INSERT INTO reimbursement_line_items
                                 (reimbursement_id, expense_date, category_id, description, amount, attachment_path)
                                 VALUES (?, ?, ?, ?, ?, ?)";
            $lineItemStmt = $this->pdo->prepare($lineItemSql);

            foreach ($lineItemsData as $index => $lineItem) {
                $attachmentPath = null;
                $file = $attachmentsData[$index] ?? null;

                if ($file && isset($file['error']) && $file['error'] === UPLOAD_ERR_OK) {
                    $fileName = uniqid() . '-' . basename($file["name"]);
                    $targetSubDir = date('Y/m/');
                    $targetDir = $this->uploadDir . $targetSubDir;

                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0777, true);
                    }
                    $targetFile = $targetDir . $fileName;

                    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
                        $attachmentPath = $targetFile;
                    } else {
                        error_log("Failed to move uploaded file for line item index {$index} of claim ID {$reimbursementId}. Error: " . ($file['error'] ?? 'Unknown'));
                    }
                }

                $lineItemSuccess = $lineItemStmt->execute([
                    $reimbursementId,
                    $lineItem['expense_date'],
                    $lineItem['category_id'],
                    $lineItem['description'],
                    $lineItem['amount'],
                    $attachmentPath
                ]);

                if (!$lineItemSuccess) {
                    throw new Exception("Failed to insert line item at index " . $index . " for claim ID " . $reimbursementId . ".");
                }
            }

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error in addReimbursementWithLineItems: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing reimbursement claim with its line items and handle attachments.
     */
    public function updateReimbursementWithLineItems(int $reimbursementId, array $claimData, array $lineItemsData, array $attachmentsData) {
        $this->pdo->beginTransaction();

        try {
            // 1. Update Main Reimbursement Claim
            $mainClaimSql = "UPDATE employee_reimbursements
                                 SET employee_id = ?, claim_title = ?, claim_date = ?, currency = ?, total_amount = ?
                                 WHERE id = ?";
            $mainClaimStmt = $this->pdo->prepare($mainClaimSql);
            $mainClaimSuccess = $mainClaimStmt->execute([
                $claimData['employee_id'],
                $claimData['claim_title'],
                $claimData['claim_date'],
                $claimData['currency'],
                $claimData['total_amount'],
                $reimbursementId
            ]);

            if (!$mainClaimSuccess) {
                throw new Exception("Failed to update main reimbursement claim.");
            }

            // 2. Process Line Items: Delete, Update, Add
            $existingLineItemIdsQuery = $this->pdo->prepare("SELECT id, attachment_path FROM reimbursement_line_items WHERE reimbursement_id = ?");
            $existingLineItemIdsQuery->execute([$reimbursementId]);
            $currentLineItems = $existingLineItemIdsQuery->fetchAll(PDO::FETCH_KEY_PAIR);

            $processedLineItemIds = [];

            $insertLineItemSql = "INSERT INTO reimbursement_line_items
                                     (reimbursement_id, expense_date, category_id, description, amount, attachment_path)
                                     VALUES (?, ?, ?, ?, ?, ?)";
            $updateLineItemSql = "UPDATE reimbursement_line_items
                                     SET expense_date = ?, category_id = ?, description = ?, amount = ?, attachment_path = ?
                                     WHERE id = ? AND reimbursement_id = ?";

            foreach ($lineItemsData as $index => $lineItem) {
                $attachmentPathToStore = $lineItem['attachment_path_existing'] ?? null;

                $file = $attachmentsData[$index] ?? null;

                if ($file && isset($file['error']) && $file['error'] === UPLOAD_ERR_OK) {
                    $fileName = uniqid() . '-' . basename($file["name"]);
                    $targetSubDir = date('Y/m/');
                    $targetDir = $this->uploadDir . $targetSubDir;
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0777, true);
                    }
                    $targetFile = $targetDir . $fileName;

                    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
                        $attachmentPathToStore = $targetFile;
                        if (isset($lineItem['id']) && isset($currentLineItems[$lineItem['id']]) && !empty($currentLineItems[$lineItem['id']])) {
                            $this->deleteFile($currentLineItems[$lineItem['id']]);
                        }
                    } else {
                        error_log("Failed to move new uploaded file for line item update (ID: " . ($lineItem['id'] ?? 'new') . ", Claim ID: {$reimbursementId}). PHP Error: " . ($file['error'] ?? 'Unknown'));
                        if (isset($lineItem['delete_attachment']) && $lineItem['delete_attachment']) {
                             $attachmentPathToStore = null;
                        }
                    }
                } elseif (isset($lineItem['delete_attachment']) && $lineItem['delete_attachment']) {
                    if (isset($lineItem['id']) && isset($currentLineItems[$lineItem['id']]) && !empty($currentLineItems[$lineItem['id']])) {
                        $this->deleteFile($currentLineItems[$lineItem['id']]);
                    }
                    $attachmentPathToStore = null;
                }

                if (!empty($lineItem['id'])) {
                    $updateStmt = $this->pdo->prepare($updateLineItemSql);
                    $updateSuccess = $updateStmt->execute([
                        $lineItem['expense_date'],
                        $lineItem['category_id'],
                        $lineItem['description'],
                        $lineItem['amount'],
                        $attachmentPathToStore,
                        $lineItem['id'],
                        $reimbursementId
                    ]);
                    if (!$updateSuccess) {
                        throw new Exception("Failed to update line item ID " . $lineItem['id'] . ".");
                    }
                    $processedLineItemIds[] = $lineItem['id'];
                } else {
                    $insertStmt = $this->pdo->prepare($insertLineItemSql);
                    $insertSuccess = $insertStmt->execute([
                        $reimbursementId,
                        $lineItem['expense_date'],
                        $lineItem['category_id'],
                        $lineItem['description'],
                        $lineItem['amount'],
                        $attachmentPathToStore
                    ]);
                    if (!$insertSuccess) {
                        throw new Exception("Failed to insert new line item for claim ID " . $reimbursementId . ".");
                    }
                    $processedLineItemIds[] = $this->pdo->lastInsertId();
                }
            }

            // 3. Delete any line items that were removed in the frontend
            $lineItemsToDelete = array_diff(array_keys($currentLineItems), $processedLineItemIds);
            if (!empty($lineItemsToDelete)) {
                $placeholders = implode(',', array_fill(0, count($lineItemsToDelete), '?'));
                $deleteSql = "DELETE FROM reimbursement_line_items WHERE id IN ($placeholders) AND reimbursement_id = ?";
                $deleteStmt = $this->pdo->prepare($deleteSql);
                $deleteSuccess = $deleteStmt->execute(array_merge($lineItemsToDelete, [$reimbursementId]));

                if (!$deleteSuccess) {
                    throw new Exception("Failed to delete old line items.");
                }
                foreach ($lineItemsToDelete as $deletedId) {
                    if (isset($currentLineItems[$deletedId]) && !empty($currentLineItems[$deletedId])) {
                        $this->deleteFile($currentLineItems[$deletedId]);
                    }
                }
            }

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error in addReimbursementWithLineItems: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update the status of a reimbursement claim.
     * If status changes from Approved back to Pending/Needs Review, unlink it from payroll.
     * @param int $id Reimbursement ID.
     * @param string $status New status ('Approved', 'Rejected', 'Pending', 'Needs Correction').
     * @param string $notes Supervisor notes.
     * @param int $approverId User ID who processed it.
     * @return bool True on success, false on failure.
     */
    public function updateStatus($id, $status, $notes, $approverId) {
        $this->pdo->beginTransaction();
        try {
            // Get current status and linked payroll detail ID before update
            $currentClaimStmt = $this->pdo->prepare("SELECT status, payroll_processed_by_payroll_detail_id FROM employee_reimbursements WHERE id = ?");
            $currentClaimStmt->execute([$id]);
            $currentClaim = $currentClaimStmt->fetch(PDO::FETCH_ASSOC);

            $oldStatus = $currentClaim['status'];
            $linkedPayrollDetailId = $currentClaim['payroll_processed_by_payroll_detail_id'];

            $sql = "UPDATE employee_reimbursements
                     SET status = ?, supervisor_notes = ?, processed_by = ?, processed_date = NOW()
                     WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute([$status, $notes, $approverId, $id]);

            if ($success && $oldStatus === 'Approved' && ($status === 'Pending' || $status === 'Needs Correction')) {
                // If an approved claim is being reverted to pending or needs review,
                // and it was linked to a payroll, unlink it.
                if ($linkedPayrollDetailId) {
                    $this->unlinkReimbursementFromPayrollDetail($id);
                }
            }

            $this->pdo->commit();
            return $success;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error in updateStatus: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unlinks a specific reimbursement claim from a payroll_details entry.
     * This is called when a previously processed reimbursement is reverted from 'Approved'.
     * @param int $reimbursementId The ID of the reimbursement claim to unlink.
     * @return bool True on success, false on failure.
     */
    private function unlinkReimbursementFromPayrollDetail(int $reimbursementId): bool {
        try {
            // Get the payroll_detail_id it was linked to
            $stmt = $this->pdo->prepare("SELECT payroll_processed_by_payroll_detail_id FROM employee_reimbursements WHERE id = ?");
            $stmt->execute([$reimbursementId]);
            $payrollDetailId = $stmt->fetchColumn();

            if ($payrollDetailId) {
                // Now, update the payroll_details entry's breakdown JSON to remove this reimbursement from its list.
                // This is a complex operation as it involves JSON manipulation in SQL or fetching, modifying, and updating.
                // For simplicity, we'll just set the payroll_processed_by_payroll_detail_id to NULL.
                // A more robust solution would be to recalculate the payroll_detail amounts if this reimbursement was significant.
                $unlinkReimbursementSql = "UPDATE employee_reimbursements
                                         SET payroll_processed_by_payroll_detail_id = NULL, payroll_processed_date = NULL
                                         WHERE id = ?";
                $unlinkStmt = $this->pdo->prepare($unlinkReimbursementSql);
                $unlinkSuccess = $unlinkStmt->execute([$reimbursementId]);

                if ($unlinkSuccess) {
                    // Optionally, trigger a recalculation/review for the affected payroll_detail
                    // This is complex and might involve marking the specific payslip for review or regenerating the payroll.
                    // For now, we'll just mark the payslip as 'Needs Review' if it exists.
                    // Call the PayrollManager to update the payslip status.
                    // Ensure PayrollManager is initialized and available in this context.
                    if ($this->payrollManager) {
                        $this->payrollManager->updatePayslipStatus($payrollDetailId, 'Needs Review');
                        error_log("Reimbursement ID " . $reimbursementId . " unlinked. Associated payslip " . $payrollDetailId . " marked 'Needs Review'.");
                    }
                }
                return $unlinkSuccess;
            }
            return false; // No linked payroll detail found
        } catch (Exception $e) {
            error_log("Error in unlinkReimbursementFromPayrollDetail: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Fetches all approved reimbursements for a given employee and period that have NOT been marked as processed by payroll.
     * @param int $employeeId The ID of the employee.
     * @param string $payrollPeriodMonth A string in 'YYYY-MM' format for the payroll month.
     * @return array An array of approved reimbursement claims (id, total_amount, claim_title).
     */
    public function getApprovedUnprocessedReimbursements(int $employeeId, string $payrollPeriodMonth): array {
        try {
            // Retrieve reimbursements approved within or before the payroll period and not yet processed
            $sql = "SELECT id, total_amount, claim_title, currency
                     FROM employee_reimbursements
                     WHERE employee_id = ?
                     AND status = 'Approved'
                     AND DATE_FORMAT(processed_date, '%Y-%m') <= ? -- Approved ON or BEFORE the payroll period
                     AND (payroll_processed_by_payroll_detail_id IS NULL OR payroll_processed_by_payroll_detail_id = 0)
                     ORDER BY processed_date ASC"; // Process older ones first if multiple
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$employeeId, $payrollPeriodMonth]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getApprovedUnprocessedReimbursements: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Marks a list of reimbursement claims as processed by a specific payroll detail.
     * @param array $reimbursementIds An array of reimbursement claim IDs.
     * @param int $payrollDetailId The ID of the payroll_details entry that processed them.
     * @return bool True on success, false on failure.
     */
    public function markReimbursementsAsProcessedByPayroll(array $reimbursementIds, int $payrollDetailId): bool {
        if (empty($reimbursementIds)) {
            return true; // Nothing to mark
        }

        try {
            $placeholders = implode(',', array_fill(0, count($reimbursementIds), '?'));
            $sql = "UPDATE employee_reimbursements
                     SET payroll_processed_date = NOW(),
                         payroll_processed_by_payroll_detail_id = ?
                     WHERE id IN ($placeholders)";
            
            $stmt = $this->pdo->prepare($sql);
            // Prepend payrollDetailId to the array of IDs
            $params = array_merge([$payrollDetailId], $reimbursementIds);
            return $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Error in markReimbursementsAsProcessedByPayroll: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper to safely delete a file from the server.
     */
    private function deleteFile($filePath) {
        if (file_exists($filePath) && is_file($filePath)) {
            if (strpos($filePath, $this->uploadDir) === 0) {
                if (!unlink($filePath)) {
                    error_log("Failed to unlink file: " . $filePath);
                }
            } else {
                error_log("SECURITY ALERT: Attempted to delete file outside designated upload directory: " . $filePath);
            }
        }
    }
}