<?php
/**
 * process_login.php
 * Validates credentials and establishes a secure, authenticated session.
 */

// ── Secure Session Cookie Parameters ─────────────────────────────────────────
// Must be called BEFORE session_start().
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (int) ($_SERVER['SERVER_PORT'] ?? 80) === 443;

session_set_cookie_params([
    'lifetime' => 8 * 3600,          // 8-hour cookie lifetime
    'path' => '/',
    'domain' => '',                // current domain only
    'secure' => $is_https,         // HTTPS-only in production
    'httponly' => true,              // not accessible via JavaScript
    'samesite' => 'Strict',          // block CSRF cross-site delivery
]);

session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['error'] = 'Please fill in all fields.';
    header('Location: login.php');
    exit();
}

// Restrict to @gmail.com addresses
if (!preg_match("/@gmail\.com$/i", $email)) {
    $_SESSION['error'] = 'Invalid email. Only @gmail.com addresses are accepted.';
    header('Location: login.php');
    exit();
}

try {
    $conn = get_db();

    // Fetch user and join with roles to get the role_name
    $stmt = $conn->prepare("
        SELECT u.*, r.role_name
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.email = ?
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = 'Invalid email or password.';
        header('Location: login.php');
        exit();
    }

    // Handle both Hashed and Plaintext (for legacy/migration)
    $stored = $user['password'];
    $is_hashed = strlen($stored) >= 60 && str_starts_with($stored, '$2');
    $password_valid = $is_hashed
        ? password_verify($password, $stored)
        : ($password === $stored);

    if (!$password_valid) {
        $_SESSION['error'] = 'Invalid email or password.';
        header('Location: login.php');
        exit();
    }

    // Email verification check
    if (empty($user['email_verified'])) {
        $_SESSION['error'] = 'Your email address is not verified. <a href="verify.php?email=' . urlencode($email) . '" class="text-orange fw-bold">Enter code</a> or <a href="resend_verification.php?email=' . urlencode($email) . '" class="text-orange fw-bold">resend it</a>.';
        header('Location: login.php');
        exit();
    }

    // Faculty approval check
    if ($user['role_name'] === 'faculty' && empty($user['is_approved'])) {
        $_SESSION['error'] = 'Your account is pending approval by the Dean.';
        header('Location: login.php');
        exit();
    }

    // Auto-migrate plaintext passwords to Hash
    if (!$is_hashed) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $conn->prepare("UPDATE users SET password = ? WHERE id = ?")
            ->execute([$hashed, $user['id']]);
    }

    /**
     * ROLE MAPPING
     * To ensure the "New Dean" has the same features as the "Old Dean",
     * we map the 'dean' role_name to the 'dean' session role.
     */
    $role_map = [
        'faculty' => 'faculty',
        'dean' => 'dean',
        'vpaa' => 'vpaa',
        'department_head' => 'department_head',
    ];

    $session_role = $role_map[$user['role_name']] ?? 'faculty';

    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);

    // Set Session Variables
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['username'] = trim($user['first_name'] . ' ' . $user['last_name']);
    $_SESSION['role'] = $session_role;
    $_SESSION['role_name'] = $user['role_name'];
    $_SESSION['role_id'] = (int) $user['role_id'];
    $_SESSION['college_id'] = $user['college_id'] ? (int) $user['college_id'] : null;
    $_SESSION['last_activity'] = time();  // for backend inactivity guard

    /**
     * REDIRECT LOGIC
     * 'dean' is directed to the admin_dashboard where the Dean's tools are located.
     */
    switch ($session_role) {
        case 'faculty':
            header('Location: faculty/faculty_dashboard.php');
            break;
        case 'dean':
            header('Location: admin/admin_dashboard.php');
            break;
        case 'vpaa':
            header('Location: vpaa/vpaa_dashboard.php');
            break;
        case 'department_head':
            header('Location: dept_head/dept_dashboard.php');
            break;
        default:
            header('Location: faculty/faculty_dashboard.php');
    }
    exit();

} catch (PDOException $e) {
    error_log("Login Error: " . $e->getMessage());
    $_SESSION['error'] = 'A system error occurred. Please try again later.';
    header('Location: login.php');
    exit();
}