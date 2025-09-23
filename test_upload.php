<?php
// test_upload.php
// This script is for diagnosing basic file upload functionality on your server.
// It should be placed in your web-accessible directory (e.g., finlaberp/).

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Handle POST request (file upload) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "--- PHP Test Upload Received ---\n";
    echo "Raw _FILES Array:\n";
    print_r($_FILES); // Dump the raw $_FILES superglobal

    // Check if the specific file input 'test_file' was received and had no errors
    if (isset($_FILES['test_file']) && $_FILES['test_file']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/test_uploads/'; // Target directory for this test script
        
        // Try to create the directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            $mkdir_success = mkdir($uploadDir, 0777, true); // Use 0777 for testing permissions
            echo "mkdir result for {$uploadDir}: " . ($mkdir_success ? 'SUCCESS' : 'FAIL') . "\n";
            if (!$mkdir_success) {
                echo "Error: Could not create upload directory. Check permissions for {$uploadDir} (should be 0777 for this test).\n";
                exit; // Stop execution if directory can't be created
            }
        } else {
            echo "Directory {$uploadDir} already exists.\n";
        }

        $targetPath = $uploadDir . basename($_FILES['test_file']['name']);
        echo "Attempting to move '{$_FILES['test_file']['tmp_name']}' to '{$targetPath}'\n";

        // Attempt to move the uploaded file
        $move_success = move_uploaded_file($_FILES['test_file']['tmp_name'], $targetPath);
        echo "move_uploaded_file result: " . ($move_success ? 'SUCCESS' : 'FAIL') . "\n";

        if ($move_success) {
            echo "File uploaded successfully to: " . $targetPath . "\n";
        } else {
            echo "File upload FAILED. PHP Error Code: " . $_FILES['test_file']['error'] . "\n";
            $last_error = error_get_last(); // Get the last PHP error (useful for warnings)
            if ($last_error) {
                echo "Last PHP error: " " . print_r($last_error, true) . "\n"; // Corrected concatenation
            }
        }
    } else {
        echo "No file uploaded or an error occurred during upload.\n";
        if (isset($_FILES['test_file'])) {
            echo "Error code for 'test_file': " . $_FILES['test_file']['error'] . "\n";
        } else {
            echo "No 'test_file' input found in _FILES array.\n";
        }
    }
    echo "--- Test Upload Finished ---\n";
}
// --- Handle GET request (display form) ---
else {
    echo '<h3>Simple PHP File Upload Test</h3>';
    echo '<p>Select a small file (e.g., image or PDF) and click "Upload Test File".</p>';
    echo '<form method="POST" enctype="multipart/form-data">';
    echo '  <input type="file" name="test_file" required><br><br>'; // 'required' for basic frontend validation
    echo '  <input type="submit" value="Upload Test File">';
    echo '</form>';
}
?>