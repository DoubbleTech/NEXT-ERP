<?php
/**
 * config.php
 * Defines constants for database connection parameters.
 */

// --- Database Credentials ---
// Using define() for constants is good practice for configuration values.
define('DB_HOST', 'localhost');
// CORRECTED: Removed the trailing space from the username.
define('DB_USER', 'u587956043_NEXTERP');
// NOTE TO USER: Please replace 'YOUR_ACTUAL_DB_PASSWORD' with the actual password for the MySQL user shown in your cPanel.
// The password for the database user is often different from your vPanel login password.
define('DB_PASS', 'KNsMfkvL#1f');

// --- Database Name ---
// UPDATED: Pointing back to the main ERP database where the 'users' table should reside.
// Ensure tables like 'users', 'employees' exist in this database.
define('DB_NAME', 'u587956043_NEXTERP');

// --- Session and CSRF Token Management ---
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate or retrieve CSRF token for security
// This token is used to protect against Cross-Site Request Forgery attacks.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generates a random 64-character hex string
}

// --- Global Constants (Optional, but useful) ---
// Define paths for file uploads, outside the web root if possible for security
// Adjust these paths based on your actual server directory structure.
// This example assumes 'uploads' is a sibling directory to 'finlaberp'.
define('UPLOAD_BASE_DIR', __DIR__ . '/../uploads/');
define('AVATAR_UPLOAD_DIR', UPLOAD_BASE_DIR . 'avatars/');
define('DOCUMENT_UPLOAD_DIR', UPLOAD_BASE_DIR . 'documents/');

// You can add other global configurations here
// define('APP_NAME', 'FinLab ERP');
// define('ADMIN_EMAIL', 'admin@finlab.com');
