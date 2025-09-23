<?php
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales - Accounting ERP</title> <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="style.css">
    </head>
<body>

    <?php include 'navbar.php'; // Include the common navbar here ?>

    <div class="page-content"> <h1>Sales Application</h1>
        <p>This is where the content for the sales application will go.</p>
        </div>

    <script>
        // Sales page specific JavaScript
        $(document).ready(function() {
            console.log("Sales page loaded");
            // Add interactions for sales forms, tables etc.
        });
    </script>

</body>
</html>