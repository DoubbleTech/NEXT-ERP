<?php
/**
 * login.php - Handles user login requests
 * Returns JSON response
 */

// --- Debugging (disable in production) ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Initialization ---
define('ROOT_PATH', __DIR__);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// JSON header
header('Content-Type: application/json');

// Load dependencies
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/functions.php';
require_once ROOT_PATH . '/classes/UserManager.php';

// Default response
$response = ['success' => false, 'message' => 'Invalid request method.'];

// Handle POST only
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['login_email'] ?? '');
    $password = $_POST['login_password'] ?? '';

    $errors = [];

    // --- Validation ---
    if (empty($email)) {
        $errors['login_email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['login_email'] = 'Invalid email format.';
    }

    if (empty($password)) {
        $errors['login_password'] = 'Password is required.';
    }

    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    try {
        $pdo = connect_db();
        if (!$pdo) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database connection error.']);
            exit;
        }

        $userManager = new UserManager($pdo);
        $user = $userManager->authenticateUser($email, $password);

        if ($user) {
            // Session regeneration
            session_regenerate_id(true);

            // Save to session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'] ?? 'user';
            $_SESSION['user_permissions'] = $user['permissions'] ?? [];

            echo json_encode(['success' => true, 'redirect' => 'dashboard.php']);
            exit;
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
            exit;
        }
    } catch (Throwable $e) {
        // Log the error
        error_log("Login Error: " . $e->getMessage());

        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'An internal error occurred. Please try again later.']);
        exit;
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}