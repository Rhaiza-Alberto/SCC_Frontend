<?php
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$email    = trim($_POST['email']    ?? '');
$password = $_POST['password']      ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['error'] = 'Please fill in all fields.';
    header('Location: login.php');
    exit();
}

if (!preg_match("/@gmail\.com$/i", $email)) {
    $_SESSION['error'] = 'Invalid email. Only @gmail.com addresses are accepted.';
    header('Location: login.php');
    exit();
}

try {
    $conn = get_db();

    $stmt = $conn->prepare("
        SELECT u.*, r.role_name
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.email = ? AND u.is_deleted = 0
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = 'Invalid email or password.';
        header('Location: login.php');
        exit();
    }

    $stored         = $user['password'];
    $is_hashed      = strlen($stored) >= 60 && str_starts_with($stored, '$2');
    $password_valid = $is_hashed
        ? password_verify($password, $stored)
        : ($password === $stored);

    if (!$password_valid) {
        $_SESSION['error'] = 'Invalid email or password.';
        header('Location: login.php');
        exit();
    }

    // Faculty must be approved by the Dean
    if ($user['role_name'] === 'faculty' && empty($user['is_approved'])) {
        $_SESSION['error'] = 'Your account is pending approval by the Dean.';
        header('Location: login.php');
        exit();
    }

    if (!$is_hashed) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $conn->prepare("UPDATE users SET password = ? WHERE id = ?")
             ->execute([$hashed, $user['id']]);
    }

    $role_map = [
        'faculty' => 'faculty',
        'dean'    => 'dean',
        'vpaa'    => 'vpaa',
    ];
    $session_role = $role_map[$user['role_name']] ?? 'faculty';

    $_SESSION['logged_in']     = true;
    $_SESSION['user_id']       = (int) $user['id'];
    $_SESSION['email']         = $user['email'];
    $_SESSION['username']      = trim($user['first_name'] . ' ' . $user['last_name']);
    $_SESSION['role']          = $session_role;
    $_SESSION['role_name']     = $user['role_name'];
    $_SESSION['role_id']       = (int) $user['role_id'];
    $_SESSION['department_id'] = $user['department_id'] ? (int) $user['department_id'] : null;

    switch ($session_role) {
        case 'faculty': header('Location: faculty/faculty_dashboard.php'); break;
        case 'dean':    header('Location: admin/admin_dashboard.php');     break;
        case 'vpaa':    header('Location: vpaa/vpaa_dashboard.php');       break;
        default:        header('Location: faculty/faculty_dashboard.php');
    }
    exit();

} catch (PDOException $e) {
    error_log("Login Error: " . $e->getMessage());
    $_SESSION['error'] = 'A system error occurred. Please try again later.';
    header('Location: login.php');
    exit();
}