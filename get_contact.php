<?php
// Turn off all error reporting to prevent corrupting JSON
error_reporting(0);
ini_set('display_errors', 0);

// Set headers first
header('Content-Type: application/json');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'business_contacts');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Validate input parameters
    if (!isset($_GET['id'])) {
        throw new Exception('ID parameter is required');
    }
    
    if (!isset($_GET['type'])) {
        throw new Exception('Type parameter is required');
    }
    
    $id = $_GET['id'];
    $type = $_GET['type'];
    
    // Validate type
    if ($type !== 'customer' && $type !== 'vendor') {
        throw new Exception('Invalid type parameter');
    }
    
    // Prepare and execute query
    if ($type === 'customer') {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    }
    
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) {
        throw new Exception('Contact not found');
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
    exit;
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}