<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'business_contacts');
define('DB_USER', 'root');
define('DB_PASS', '');

// Uploads directory configuration
define('UPLOAD_DIR', 'uploads/');
define('ATTACHMENT_DIR', 'uploads/attachments/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['application/pdf', 'application/vnd.ms-excel', 'text/csv', 
                         'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                         'image/jpeg', 'image/png', 'image/gif']);

header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if (!isset($_POST['contact_id'])) {
        throw new Exception('Contact ID is required');
    }
    
    if (!isset($_POST['contact_type'])) {
        throw new Exception('Contact type is required');
    }
    
    if (!isset($_POST['name'])) {
        throw new Exception('Attachment name is required');
    }
    
    if (!isset($_FILES['attachments'])) {
        throw new Exception('No files uploaded');
    }
    
    $contactId = $_POST['contact_id'];
    $contactType = $_POST['contact_type'];
    $name = $_POST['name'];
    
    // Create directory if it doesn't exist
    if (!file_exists(ATTACHMENT_DIR)) {
        mkdir(ATTACHMENT_DIR, 0777, true);
    }
    
    $uploadedFiles = [];
    
    foreach ($_FILES['attachments']['tmp_name'] as $key => $tmpName) {
        $fileName = $_FILES['attachments']['name'][$key];
        $fileSize = $_FILES['attachments']['size'][$key];
        $fileType = $_FILES['attachments']['type'][$key];
        
        // Check file size
        if ($fileSize > MAX_UPLOAD_SIZE) {
            throw new Exception("File $fileName is too large. Max size: 5MB");
        }
        
        // Check file type
        if (!in_array($fileType, ALLOWED_TYPES)) {
            throw new Exception("Invalid file type for $fileName. Allowed: PDF, XLS, CSV, JPG, PNG, GIF");
        }
        
        // Generate unique filename
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFilename = uniqid() . '.' . $ext;
        $destination = ATTACHMENT_DIR . $newFilename;
        
        // Move the file
        if (move_uploaded_file($tmpName, $destination)) {
            // Save to database
            $stmt = $pdo->prepare("INSERT INTO contact_attachments (contact_id, contact_type, name, filename) VALUES (?, ?, ?, ?)");
            $stmt->execute([$contactId, $contactType, $name, $newFilename]);
            
            $uploadedFiles[] = [
                'name' => $name,
                'filename' => $newFilename
            ];
        } else {
            throw new Exception("Failed to upload $fileName");
        }
    }
    
    echo json_encode(['success' => true, 'files' => $uploadedFiles]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>