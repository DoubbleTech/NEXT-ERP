<?php
// test_api.php - A simple file to diagnose server environment issues.

// Show all errors directly on the screen.
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Server Environment Test</h1>";
echo "<p>This script checks for the two most common causes of 500 errors in this project.</p>";
echo "<hr>";

// --- Test 1: Check Autoloader Path ---
echo "<h3>Test 1: Composer Autoloader</h3>";
$path_to_autoloader = __DIR__ . '/../vendor/autoload.php';
echo "Attempting to load autoloader from: <strong>" . realpath($path_to_autoloader) . "</strong><br>";

if (file_exists($path_to_autoloader)) {
    echo "<p style='color:green; font-weight:bold;'>SUCCESS: The autoloader file was found.</p>";
    require_once $path_to_autoloader;
    
    if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        echo "<p style='color:green; font-weight:bold;'>SUCCESS: The 'PhpSpreadsheet' class is loaded correctly.</p>";
    } else {
        echo "<p style='color:red; font-weight:bold;'>ERROR: Autoloader was found, but the PhpSpreadsheet class is NOT available. Your 'composer install' might be incomplete or the vendor directory is corrupted.</p>";
    }

} else {
    // Let's try the other common path
    $path_to_autoloader_alt = __DIR__ . '/vendor/autoload.php';
    echo "<p style='color:orange; font-weight:bold;'>NOTICE: Autoloader not found at the first path. Checking alternative path...</p>";
    echo "Attempting to load autoloader from: <strong>" . realpath($path_to_autoloader_alt) . "</strong><br>";

    if (file_exists($path_to_autoloader_alt)) {
         echo "<p style='color:green; font-weight:bold;'>SUCCESS: The autoloader file was found at the alternative path.</p>";
         echo "<p style='font-weight:bold;'>ACTION: You should change the require_once line in api.php to use <code>__DIR__ . '/vendor/autoload.php'</code></p>";
    } else {
        echo "<p style='color:red; font-weight:bold;'>FATAL ERROR: Autoloader was NOT found in either common location. Please verify that the 'vendor' folder exists and check its location relative to your api.php file.</p>";
    }
}

// --- Test 2: Check Directory Permissions ---
echo "<hr><h3>Test 2: Directory Permissions</h3>";
$reports_dir = __DIR__ . '/../reports';
echo "Checking if the script can create a 'reports' directory at: <strong>" . realpath(__DIR__ . '/../') . "</strong><br>";

if (is_writable(__DIR__ . '/../')) {
    echo "<p style='color:green; font-weight:bold;'>SUCCESS: The parent directory is writable. The script can create the 'reports' folder.</p>";
} else {
    echo "<p style='color:red; font-weight:bold;'>ERROR: The server does NOT have permission to write to the parent directory. You must fix the folder permissions (e.g., set to 755 or 775) so that the ReportGenerator can create the 'reports' directory.</p>";
}

?>
