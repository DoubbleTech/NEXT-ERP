<?php
// Start session and load dependencies
session_start();
require_once 'config.php';
require_once 'functions.php';
require_once 'classes/UserManager.php';

header('Content-Type: application/json');

// Get the current user's role from the session
$currentUserRole = $_SESSION['user_role'] ?? 'user';
$response = ['success' => false, 'message' => '', 'errors' => []];

// Check if a user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Authentication required.';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

// Handle POST only
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Define allowed roles based on the current user's role
    $allowedRoles = [];
    if ($currentUserRole === 'super-admin') {
        $allowedRoles = ['admin', 'user'];
    } elseif ($currentUserRole === 'admin') {
        $allowedRoles = ['user'];
    }

    $formData = $_POST;
    $newUserRole = $formData['new_user_role'] ?? 'user';
    $email = trim($formData['new_user_email'] ?? '');
    $password = $formData['new_user_password'] ?? '';

    // Validate the requested role
    if (!in_array($newUserRole, $allowedRoles)) {
        $response['message'] = 'You do not have permission to create this type of user.';
        http_response_code(403);
        echo json_encode($response);
        exit;
    }

    // Basic form validation
    if (empty($email)) {
        $response['errors']['new_user_email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['errors']['new_user_email'] = 'Invalid email format.';
    }
    if (empty($password)) {
        $response['errors']['new_user_password'] = 'Password is required.';
    }

    if (empty($response['errors'])) {
        try {
            $pdo = connect_db();
            $userManager = new UserManager($pdo);

            // Check if email already exists
            if ($userManager->getUserByEmail($email)) {
                $response['errors']['new_user_email'] = 'This email is already registered.';
            } else {
                // Prepare data for the createUser function
                $userData = [
                    'email' => $email,
                    'password' => $password,
                    'role' => $newUserRole,
                    // Default values for other required fields
                    'name' => 'New User',
                    'first_name' => 'New',
                    'last_name' => 'User',
                    'country' => 'PK',
                    'business_name' => '',
                    'business_type' => '',
                    'business_reg' => '',
                    'business_country' => 'PK',
                    'terms_agreed' => true,
                    'newsletter_subscribed' => false,
                    'permissions' => []
                ];
                
                $success = $userManager->createUser($userData);

                if ($success) {
                    $response['success'] = true;
                    $response['message'] = 'User created successfully!';
                } else {
                    $response['message'] = 'User creation failed. Please try again.';
                }
            }
        } catch (Throwable $e) {
            error_log("User Creation Error: " . $e->getMessage());
            $response['message'] = 'An internal error occurred. Please try again later.';
            http_response_code(500);
        }
    } else {
        // Send a 422 Unprocessable Entity status code for validation errors
        http_response_code(422);
    }
    
    echo json_encode($response);
    exit;
} else {
    // Send a 405 Method Not Allowed status code for non-POST requests
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}