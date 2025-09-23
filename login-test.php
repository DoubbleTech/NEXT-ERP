<?php
/**
 * login-test.php - Simplified login script for debugging
 */

require_once 'config.php';

header('Content-Type: application/json');

$response = ['debug' => true];

// Debug session and CSRF
$response['session_debug'] = [
    'session_status' => session_status(),
    'session_id' => session_id(),
    'csrf_exists' => isset($_SESSION['csrf_token']),
    'csrf_length' => isset($_SESSION['csrf_token']) ? strlen($_SESSION['csrf_token']) : 0
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response['post_debug'] = [
        'received_csrf' => $_POST['csrf_token'] ?? 'NOT RECEIVED',
        'received_csrf_length' => isset($_POST['csrf_token']) ? strlen($_POST['csrf_token']) : 0,
        'stored_csrf' => $_SESSION['csrf_token'] ?? 'NOT STORED',
        'post_data_keys' => array_keys($_POST)
    ];
    
    // Simple CSRF validation
    $received_token = $_POST['csrf_token'] ?? '';
    $stored_token = $_SESSION['csrf_token'] ?? '';
    
    if (empty($stored_token)) {
        $response['csrf_result'] = 'FAIL - No stored token';
        $response['success'] = false;
        $response['message'] = 'Session error - please refresh page';
        http_response_code(403);
    } elseif (empty($received_token)) {
        $response['csrf_result'] = 'FAIL - No received token';
        $response['success'] = false;
        $response['message'] = 'Security token missing';
        http_response_code(403);
    } elseif (!hash_equals($stored_token, $received_token)) {
        $response['csrf_result'] = 'FAIL - Tokens do not match';
        $response['token_comparison'] = [
            'stored_first_10' => substr($stored_token, 0, 10),
            'received_first_10' => substr($received_token, 0, 10),
            'lengths_match' => strlen($stored_token) === strlen($received_token)
        ];
        $response['success'] = false;
        $response['message'] = 'Security token invalid';
        http_response_code(403);
    } else {
        $response['csrf_result'] = 'PASS - Tokens match';
        $response['success'] = true;
        $response['message'] = 'CSRF validation successful';
        
        // Continue with login logic here...
        $response['next_step'] = 'Would proceed with authentication';
    }
} else {
    $response['success'] = false;
    $response['message'] = 'Only POST requests allowed';
    http_response_code(405);
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>