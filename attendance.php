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
