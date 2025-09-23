<?php
header('Content-Type: application/json');

// Debugging - log raw input
$input = file_get_contents('php://input');
file_put_contents('input.log', date('Y-m-d H:i:s') . "\n" . $input . "\n\n", FILE_APPEND);

// Parse JSON if content-type is application/json
if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $_POST = json_decode($input, true) ?? [];
}

require_once 'config.php';
require_once 'functions.php';

$response = ['success' => false, 'message' => '', 'errors' => []];

// Get all possible field names (both hyphen and underscore variants)
$rawFields = [
    'first_name' => $_POST['signup-first-name'] ?? $_POST['signup_first_name'] ?? '',
    'last_name' => $_POST['signup-last-name'] ?? $_POST['signup_last_name'] ?? '',
    'email' => $_POST['signup_email'] ?? '',
    'password' => $_POST['signup_password'] ?? '',
    'confirm_password' => $_POST['signup_confirm_password'] ?? $_POST['signup-confirm-password'] ?? '',
    'country' => $_POST['signup-country'] ?? $_POST['signup_country'] ?? '',
    'business_name' => $_POST['signup-business-name'] ?? $_POST['signup_business_name'] ?? '',
    'business_type' => $_POST['signup-business-type'] ?? $_POST['signup_business_type'] ?? '',
    'business_reg' => $_POST['signup-business-reg'] ?? $_POST['signup_business_reg'] ?? '',
    'business_country' => $_POST['signup-business-country'] ?? $_POST['signup_business_country'] ?? '',
    'terms_agreed' => $_POST['signup-terms'] ?? $_POST['signup_terms'] ?? '',
    'newsletter_subscribed' => isset($_POST['signup-newsletter']) || isset($_POST['signup_newsletter']) ? 1 : 0
];

// Trim all string values
$formData = array_map(function($value) {
    return is_string($value) ? trim($value) : $value;
}, $rawFields);

// Validate required fields
$required = [
    'first_name' => 'First name',
    'last_name' => 'Last name',
    'email' => 'Email',
    'password' => 'Password',
    'confirm_password' => 'Confirm Password',
    'country' => 'Country',
    'business_name' => 'Business name',
    'business_type' => 'Business type',
    'business_country' => 'Business country',
    'terms_agreed' => 'Terms agreement'
];

foreach ($required as $field => $name) {
    if (empty($formData[$field])) {
        // Map back to the original field name for error display
        $errorField = 'signup-' . str_replace('_', '-', $field);
        $response['errors'][$errorField] = "$name is required";
    }
}

// Additional validations
if (!empty($formData['email']) && !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
    $response['errors']['signup_email'] = 'Invalid email format';
}

if (!empty($formData['password']) && strlen($formData['password']) < 8) {
    $response['errors']['signup_password'] = 'Password must be at least 8 characters';
}

if ($formData['password'] !== $formData['confirm_password']) {
    $response['errors']['signup_confirm_password'] = 'Passwords do not match';
}

if (($formData['business_country'] === 'PK') && empty($formData['business_reg'])) {
    $response['errors']['signup-business-reg'] = 'Business registration is required for Pakistan';
}

// Proceed if no errors
if (empty($response['errors'])) {
    try {
        $pdo = connect_db();
        if (!$pdo) {
            throw new Exception('Database connection failed');
        }

        // Check if email exists in the users table
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$formData['email']]);
        if ($stmt->fetch()) {
            $response['errors']['signup_email'] = 'Email already registered';
        } else {
            // Insert new user into the users table
            $passwordHash = password_hash($formData['password'], PASSWORD_DEFAULT);
            $fullName = $formData['first_name'] . ' ' . $formData['last_name'];
            
            $stmt = $pdo->prepare("INSERT INTO users
                (name, email, password, first_name, last_name, country, 
                business_name, business_type, business_reg, business_country, terms_agreed, newsletter_subscribed) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $success = $stmt->execute([
                $fullName,
                $formData['email'],
                $passwordHash,
                $formData['first_name'],
                $formData['last_name'],
                $formData['country'],
                $formData['business_name'],
                $formData['business_type'],
                $formData['business_reg'],
                $formData['business_country'],
                !empty($formData['terms_agreed']), // Convert 'agreed' to a boolean
                $formData['newsletter_subscribed']
            ]);
            
            if ($success) {
                $response['success'] = true;
                $response['message'] = 'Registration successful!';
                
                // Optional: Auto-login the user here
            } else {
                throw new Exception('Database insert failed');
            }
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        file_put_contents('errors.log', date('Y-m-d H:i:s') . " - DB Error: " . $e->getMessage() . "\n", FILE_APPEND);
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
}

// Debug output
file_put_contents('response.log', date('Y-m-d H:i:s') . "\n" . print_r($response, true) . "\n\n", FILE_APPEND);

echo json_encode($response);
