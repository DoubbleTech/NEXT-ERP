<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure the headers are sent for plain text output
header('Content-Type: text/plain');

// Fix: Include both config.php and functions.php as connect_db() is likely in functions.php
require_once 'config.php'; 
require_once 'functions.php'; // <--- THIS LINE IS THE FIX

echo "Current Time (Server): " . date('Y-m-d H:i:s') . "\n";
echo "Attempting database connection...\n";

try {
    $pdo = connect_db();
    echo "Database connection successful!\n\n";

    // Test query for 'departments' table to select 'name'
    echo "Attempting to query 'departments' table...\n";
    $stmt = $pdo->query("SELECT id, name, description FROM departments LIMIT 5"); // Try to select 'name' and 'description'
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($results) {
        echo "Successfully queried 'departments' table and selected 'name' column:\n";
        print_r($results);
    } else {
        echo "Query to 'departments' table successful, but no rows returned or 'name' column still not found.\n";
        echo "Attempting a 'SHOW COLUMNS' query to confirm 'name' column exists:\n";
        $stmt_columns = $pdo->query("SHOW COLUMNS FROM departments LIKE 'name'");
        $column_exists = $stmt_columns->fetch(PDO::FETCH_ASSOC);
        if ($column_exists) {
            echo "CONFIRMATION: 'name' column IS listed in SHOW COLUMNS FROM departments. Details:\n";
            print_r($column_exists);
        } else {
            echo "ERROR: 'name' column is NOT listed in SHOW COLUMNS FROM departments. Something is severely wrong with the database schema or caching.\n";
        }
    }

} catch (PDOException $e) {
    echo "Database Error (PDOException):\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
} catch (Exception $e) {
    echo "General Error:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>