<?php
/**
 * logout.php
 * Destroys the current session and redirects the user to the login page.
 */

// 1. Start the session (must be started to access/destroy it)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Unset all session variables
$_SESSION = array();

// 3. If using session cookies, delete the cookie as well.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, // Set expiry in the past
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Finally, destroy the session.
session_destroy();

// 5. Redirect to the login page (index.php)
// Optionally add a status message
header("Location: index.php?status=logged_out");
exit; // Ensure no further code executes

?>
