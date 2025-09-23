<?php
/**
 * csrf-test.php - Debug script to test CSRF token generation
 */

require_once 'config.php';

header('Content-Type: application/json');

$debug_info = [
    'session_status' => session_status(),
    'session_id' => session_id(),
    'csrf_token_exists' => isset($_SESSION['csrf_token']),
    'csrf_token' => $_SESSION['csrf_token'] ?? 'NOT SET',
    'csrf_token_length' => isset($_SESSION['csrf_token']) ? strlen($_SESSION['csrf_token']) : 0,
    'post_data' => $_POST,
    'method' => $_SERVER['REQUEST_METHOD']
];

// If this is a POST request, also test the validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $received_token = $_POST['csrf_token'] ?? '';
    $stored_token = $_SESSION['csrf_token'] ?? '';
    
    $debug_info['received_token'] = $received_token;
    $debug_info['tokens_match'] = hash_equals($stored_token, $received_token);
    $debug_info['hash_equals_test'] = [
        'stored_length' => strlen($stored_token),
        'received_length' => strlen($received_token),
        'stored_first_10' => substr($stored_token, 0, 10),
        'received_first_10' => substr($received_token, 0, 10)
    ];
}

echo json_encode($debug_info, JSON_PRETTY_PRINT);
?>