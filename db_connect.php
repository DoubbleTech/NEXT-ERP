<?php
// db_connect.php

$dbHost = "localhost"; // Or your specific host if different
$dbUser = "root";      // Default XAMPP username (change if you set a password)
$dbPass = "";          // Default XAMPP password (change if you set one)
$dbName = "company_db"; // The database name you created (make sure this exists)

// Create connection using MySQLi (object-oriented style)
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

// Check connection
if ($conn->connect_error) {
    // In a real application, log this error and potentially return a JSON error if used in API
    error_log("Database Connection Failed: " . $conn->connect_error);
    // For API context, you might want to die with JSON
    // header('Content-Type: application/json');
    // echo json_encode(['success' => false, 'message' => 'Database connection error.']);
    // die();
    die("Database Connection Failed: " . $conn->connect_error); // Simple die for now
}

// Set character set to utf8mb4 for broader character support (recommended)
if (!$conn->set_charset("utf8mb4")) {
    // Log this error in a real application
    error_log(sprintf("Error loading character set utf8mb4: %s\n", $conn->error));
}

// The $conn object will be included and used by api.php
?>
