<?php
require_once 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = 1; // Replace with actual user ID from session/login
    $expense_date = $_POST['expense_date'];
    $expense_type = $_POST['expense_type'];
    $expense_location = $_POST['expense_location'];
    $amount = $_POST['amount'];

    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["attachment"]["name"]);
    move_uploaded_file($_FILES["attachment"]["tmp_name"], $target_file);

    $sql = "INSERT INTO reimbursements (user_id, expense_date, expense_type, expense_location, attachment_path, amount, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssds", $user_id, $expense_date, $expense_type, $expense_location, $target_file, $amount);

    if ($stmt->execute()) {
        echo "Reimbursement submitted successfully!";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    $stmt->close();
    $conn->close();
}

/**
 * [Original page description, e.g., contacts.php]
 * - Added Authentication Check: Redirects to index.php if user not logged in.
 * ... other comments ...
 */

// --- Authentication Check ---
// Ensure session is started BEFORE checking session variables
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user ID session variable is set (which logout.php destroys).
// Redirect to login if not set.
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=login_required');
    exit; // Stop script execution immediately
}
// --- If we reach here, the user is logged in ---
// --- END Authentication Check ---


// --- Original PHP code for the page starts below ---
require_once 'config.php';
// ... rest of the PHP code for that specific page ...



?>