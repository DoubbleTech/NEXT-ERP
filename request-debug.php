<?php
/**
 * request-debug.php - Debug what data is being received
 */
require_once 'config.php';

header('Content-Type: application/json');

$response = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'received_post_data' => $_POST,
    'received_post_keys' => array_keys($_POST),
    'raw_input' => file_get_contents('php://input'),
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
    'csrf_in_session' => $_SESSION['csrf_token'] ?? 'not set',
    'csrf_in_post' => $_POST['csrf_token'] ?? 'not set',
    'all_headers' => getallheaders()
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>