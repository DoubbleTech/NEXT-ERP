<?php
/**
 * UserManager.php
 * Handles all user-related database operations.
 */
class UserManager {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Authenticate user by email and password.
     */
    public function authenticateUser(string $email, string $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Optionally rehash if needed
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $update = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update->execute([$newHash, $user['id']]);
            }
            return $user;
        }
        return false;
    }

    /**
     * Creates a new user from the public signup form.
     */
    public function createUser(array $data): bool {
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        $fullName = ($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '');

        $stmt = $this->pdo->prepare("
            INSERT INTO users 
                (name, first_name, last_name, email, password, country, 
                 business_name, business_type, business_reg, business_country, 
                 terms_agreed, newsletter_subscribed, role, permissions)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $fullName,
            $data['first_name'] ?? '',
            $data['last_name'] ?? '',
            $data['email'],
            $passwordHash,
            $data['country'] ?? '',
            $data['business_name'] ?? '',
            $data['business_type'] ?? '',
            $data['business_reg'] ?? '',
            $data['business_country'] ?? '',
            (int)($data['terms_agreed'] ?? false),
            (int)($data['newsletter_subscribed'] ?? false),
            $data['role'] ?? 'user',
            json_encode($data['permissions'] ?? [])
        ]);
    }

    /**
     * NEW: Creates a new user for an existing employee from the admin panel.
     */
    public function createUserWithEmployeeId(int $employeeId, string $email, string $firstName, string $lastName, string $password, string $role, string $permissionsJson): bool {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $fullName = $firstName . ' ' . $lastName;

        $stmt = $this->pdo->prepare("
            INSERT INTO users 
                (employee_id, name, first_name, last_name, email, password, role, permissions)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $employeeId,
            $fullName,
            $firstName,
            $lastName,
            $email,
            $passwordHash,
            $role,
            $permissionsJson
        ]);
    }
    
    /**
     * Get user profile by ID.
     */
    public function getUserById(int $id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get user by email.
     */
    public function getUserByEmail(string $email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * NEW: Get user by employee ID.
     */
    public function getUserByEmployeeId(int $employeeId) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE employee_id = ? LIMIT 1");
        $stmt->execute([$employeeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * NEW: Update user profile data.
     */
    public function updateUserProfile(int $userId, array $userData): bool {
        $stmt = $this->pdo->prepare("
            UPDATE users SET
                name = ?,
                first_name = ?,
                last_name = ?,
                mobile_number = ?,
                business_name = ?,
                business_type = ?,
                business_reg = ?,
                business_country = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $userData['name'],
            $userData['first_name'],
            $userData['last_name'],
            $userData['mobile_number'],
            $userData['business_name'],
            $userData['business_type'],
            $userData['business_reg'],
            $userData['business_country'],
            $userId
        ]);
    }

    /**
     * NEW: Update user access (role and permissions).
     */
    public function updateUserAccess(int $userId, string $newRole, string $permissionsJson): bool {
        $stmt = $this->pdo->prepare("
            UPDATE users SET
                role = ?,
                permissions = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $newRole,
            $permissionsJson,
            $userId
        ]);
    }

    /**
     * NEW: Get all users with their roles.
     */
    public function getAllUsers() {
        $stmt = $this->pdo->query("SELECT id, name, email, role FROM users");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * NEW: Get all registered users for the access control page.
     */
    public function getRegisteredUsers() {
        $stmt = $this->pdo->query("SELECT id, name, email, role FROM users ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * NEW: Get a user's full profile including permissions.
     */
    public function getUserProfile(int $id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $user['permissions'] = json_decode($user['permissions'] ?? '[]', true);
            return $user;
        }
        return false;
    }

    /**
     * NEW: Refresh a user's session permissions after an update.
     */
    public function refreshUserSessionPermissions(int $userId): bool {
        $user = $this->getUserById($userId);
        if ($user) {
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_permissions'] = json_decode($user['permissions'], true);
            return true;
        }
        return false;
    }
}