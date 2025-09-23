<?php
// classes/DepartmentManager.php

// Ensure class is not redefined if included multiple times
if (!class_exists('DepartmentManager')) {

/**
 * DepartmentManager Class
 *
 * Manages all business logic and database interactions for departments.
 */
class DepartmentManager {
    private $pdo; // PDO database connection object

    /**
     * Constructor for DepartmentManager.
     *
     * @param PDO $pdo The PDO database connection object.
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Retrieves all departments from the database.
     * This method correctly aliases the 'name' column to 'department_name'
     * for consistency with the frontend expectations.
     *
     * @return array Array of department data (id, department_name, description).
     * @throws Exception On database query failure.
     */
    public function getDepartments() {
        try {
            // SQL query to select departments.
            // Aliasing 'name' as 'department_name' to match frontend's expectation from previous issues.
            $sql = "SELECT id, name AS department_name, description, created_at, updated_at FROM departments ORDER BY name ASC";
            error_log("DEBUG SQL (DepartmentManager->getDepartments): " . $sql); // DEBUG LOG
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ERROR: DepartmentManager->getDepartments failed: " . $e->getMessage());
            throw new Exception("Could not retrieve department list: " . $e->getMessage());
        }
    }

    /**
     * Adds a new department to the database.
     *
     * @param string $departmentName The name of the new department.
     * @param string|null $description An optional description for the department.
     * @return int The ID of the newly inserted department.
     * @throws Exception On validation errors or database insert failure.
     */
    public function addDepartment($departmentName, $description = null) {
        if (empty($departmentName)) {
            throw new Exception("Department name cannot be empty.");
        }
        // Optional: Add uniqueness check for department name
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM departments WHERE name = :name");
        $stmt->execute([':name' => $departmentName]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Department '{$departmentName}' already exists.");
        }

        try {
            $sql = "INSERT INTO departments (name, description, created_at, updated_at) VALUES (:name, :description, NOW(), NOW())";
            error_log("DEBUG SQL (DepartmentManager->addDepartment): " . $sql); // DEBUG LOG
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':name' => $departmentName,
                ':description' => $description
            ]);
            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("ERROR: DepartmentManager->addDepartment failed: " . $e->getMessage());
            throw new Exception("Failed to add department: " . $e->getMessage());
        }
    }

    /**
     * Updates an existing department.
     *
     * @param int $departmentId The ID of the department to update.
     * @param string $departmentName The new name of the department.
     * @param string|null $description The new description for the department.
     * @return bool True on success.
     * @throws Exception On validation errors or database update failure.
     */
    public function updateDepartment($departmentId, $departmentName, $description = null) {
        if (empty($departmentId)) {
            throw new Exception("Department ID is required for update.");
        }
        if (empty($departmentName)) {
            throw new Exception("Department name cannot be empty.");
        }
        // Optional: Check for uniqueness if name is changed
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM departments WHERE name = :name AND id != :id");
        $stmt->execute([':name' => $departmentName, ':id' => $departmentId]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Department name '{$departmentName}' already exists for another department.");
        }

        try {
            $sql = "UPDATE departments SET name = :name, description = :description, updated_at = NOW() WHERE id = :id";
            error_log("DEBUG SQL (DepartmentManager->updateDepartment): " . $sql); // DEBUG LOG
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':name' => $departmentName,
                ':description' => $description,
                ':id' => $departmentId
            ]);
        } catch (Exception $e) {
            error_log("ERROR: DepartmentManager->updateDepartment failed: " . $e->getMessage());
            throw new Exception("Failed to update department: " . $e->getMessage());
        }
    }

    /**
     * Deletes a department by its ID.
     *
     * @param int $departmentId The ID of the department to delete.
     * @return bool True on success.
     * @throws Exception On database delete failure (e.g., foreign key constraints).
     */
    public function deleteDepartment($departmentId) {
        if (empty($departmentId)) {
            throw new Exception("Department ID is required for deletion.");
        }
        // Check for associated employees before deletion to prevent orphan records or FK errors
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM employees WHERE department_id = :id");
        $stmt->execute([':id' => $departmentId]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Cannot delete department with existing employees. Please reassign employees first.");
        }

        try {
            $sql = "DELETE FROM departments WHERE id = :id";
            error_log("DEBUG SQL (DepartmentManager->deleteDepartment): " . $sql); // DEBUG LOG
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':id' => $departmentId]);
        } catch (Exception $e) {
            error_log("ERROR: DepartmentManager->deleteDepartment failed: " . $e->getMessage());
            throw new Exception("Failed to delete department: " . $e->getMessage());
        }
    }

    /**
     * Retrieves department names by a list of department IDs.
     * * @param array $ids Array of department IDs.
     * @return array Associative array of id => name.
     */
    public function getDepartmentNamesByIds(array $ids): array {
        if (empty($ids)) {
            return [];
        }
        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "SELECT id, name FROM departments WHERE id IN ($placeholders) ORDER BY name ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($ids);
            
            $result = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $result[$row['id']] = $row['name'];
            }
            return $result;
        } catch (Exception $e) {
            error_log("ERROR: DepartmentManager->getDepartmentNamesByIds failed: " . $e->getMessage());
            return [];
        }
    }
}
} // End if(!class_exists('DepartmentManager'))