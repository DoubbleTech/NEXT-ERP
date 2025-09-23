<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'business_contacts');
define('DB_USER', 'root');
define('DB_PASS', '');

header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if (!isset($_GET['contact_id'])) {
        throw new Exception('Contact ID parameter is required');
    }
    
    if (!isset($_GET['contact_type'])) {
        throw new Exception('Contact type parameter is required');
    }
    
    $contactId = $_GET['contact_id'];
    $contactType = $_GET['contact_type'];
    
    $stmt = $pdo->prepare("SELECT * FROM contact_attachments WHERE contact_id = ? AND contact_type = ?");
    $stmt->execute([$contactId, $contactType]);
    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($attachments);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>