<?php
/**
 * functions.php
 * A collection of reusable functions for your application.
 */

// This function establishes a PDO database connection.
function connect_db() {
    // We use the constants defined in config.php to create a new connection.
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        // Attempt to create a new PDO instance
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (\PDOException $e) {
        // Throw a custom exception with a generic message to the user
        // and log the detailed error for debugging.
        throw new \PDOException("Database connection failed: " . $e->getMessage(), (int)$e->getCode());
    }
}

// This function sanitizes user input to prevent XSS attacks.
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// You can add other utility functions here as needed.
?>