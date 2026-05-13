 <?php
/**
 * logout.php
 * Destroys the session and redirects to the login page.
 */
session_start();

// Clear all session data
$_SESSION = [];

// Destroy the session cookie with matching parameters
if (isset($_COOKIE[session_name()])) {
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
             || (int)($_SERVER['SERVER_PORT'] ?? 80) === 443;
    setcookie(session_name(), '', time() - 3600, '/', '', $is_https, true);
}

// Destroy the session
session_destroy();

// Redirect to index.php
header('Location: index.php');
exit();