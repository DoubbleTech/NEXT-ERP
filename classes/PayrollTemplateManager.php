<?php
// classes/PayrollTemplateManager.php



class PayrollTemplateManager {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Saves a new payroll template or updates an existing one.
     * @param string $templateName The name of the template.
     * @param array $departmentIds An array of department IDs.
     * @return bool True on success, false on failure.
     * @throws Exception If the template name already exists (for new templates).
     */
    public function saveTemplate(string $templateName, array $departmentIds): bool {
        $this->pdo->beginTransaction();
        try {
            // Check if template name already exists (for unique constraint violation, before attempting insert/update)
            $stmt = $this->pdo->prepare("SELECT id FROM payroll_templates WHERE template_name = ?");
            $stmt->execute([$templateName]);
            $existingTemplate = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingTemplate) {
                // If template name already exists, update the existing template
                $sql = "UPDATE payroll_templates SET department_ids = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $success = $stmt->execute([json_encode($departmentIds), $existingTemplate['id']]);
            } else {
                // Otherwise, insert new template
                $sql = "INSERT INTO payroll_templates (template_name, department_ids, created_at, updated_at) VALUES (?, ?, NOW(), NOW())";
                $stmt = $this->pdo->prepare($sql);
                $success = $stmt->execute([$templateName, json_encode($departmentIds)]);
            }

            if (!$success) {
                // This general exception might be thrown if there's a problem with the query itself
                throw new Exception("Failed to save payroll template due to database operation error.");
            }

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            // Specifically catch unique constraint violation (SQLSTATE 23000) for template_name if it was a new insert
            // This is crucial for user-friendly error messages if the name is truly unique
            if ($e->getCode() == 23000 && strpos($e->getMessage(), 'template_name') !== false) {
                 throw new Exception("Template name '{$templateName}' already exists. Please choose a different name or update the existing template.");
            }
            error_log("Error in saveTemplate (PDO): " . $e->getMessage());
            throw new Exception("Database error while saving template: " . $e->getMessage());
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error in saveTemplate (General): " . $e->getMessage());
            throw $e; // Re-throw the more specific exception
        }
    }

    /**
     * Gets all saved payroll templates.
     * @return array List of templates.
     */
    public function getTemplates(): array {
        try {
            $stmt = $this->pdo->query("SELECT id, template_name, department_ids FROM payroll_templates ORDER BY template_name ASC");
            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Decode department_ids JSON for each template
            foreach ($templates as &$template) {
                $template['department_ids'] = json_decode($template['department_ids'], true);
                if ($template['department_ids'] === null && json_last_error() !== JSON_ERROR_NONE) {
                    $template['department_ids'] = []; // Handle JSON decode errors
                    error_log("JSON decode error for template ID " . $template['id'] . ": " . json_last_error_msg());
                }
            }
            unset($template); // Break reference to the last element

            return $templates;
        } catch (Exception $e) {
            error_log("Error in getTemplates: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Deletes a payroll template.
     * @param int $templateId The ID of the template to delete.
     * @return bool True on success, false on failure.
     */
    public function deleteTemplate(int $templateId): bool {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM payroll_templates WHERE id = ?");
            $success = $stmt->execute([$templateId]);
            return $success;
        } catch (Exception $e) {
            error_log("Error in deleteTemplate: " . $e->getMessage());
            return false;
        }
    }
}