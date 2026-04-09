 <?php
/**
 * logout.php
 * Destroys the session and redirects to the login page.
 */
session_start();

// Clear all session data
$_SESSION = [];

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to index.php
header('Location: index.php');
exit();