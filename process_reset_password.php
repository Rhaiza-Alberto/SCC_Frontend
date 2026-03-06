<?php
/**
 * process_reset_password.php
 * Updates the user's password after a verified OTP reset flow.
 */
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || empty($_SESSION['fp_verified'])
    || empty($_SESSION['fp_user_id'])) {
    header('Location: forgot_password.php');
    exit();
}

$user_id         = (int) $_SESSION['fp_user_id'];
$new_password    = $_POST['newPassword']     ?? '';
$confirm_password = $_POST['confirmPassword'] ?? '';

if (empty($new_password) || strlen($new_password) < 6) {
    $_SESSION['fp_error'] = 'Password must be at least 6 characters.';
    header('Location: reset_password.php');
    exit();
}

if ($new_password !== $confirm_password) {
    $_SESSION['fp_error'] = 'Passwords do not match.';
    header('Location: reset_password.php');
    exit();
}

try {
    $conn   = get_db();
    $hashed = password_hash($new_password, PASSWORD_BCRYPT);

    // Update password and clear reset flag
    $conn->prepare("
        UPDATE users
        SET password = ?, reset_requested = 0
        WHERE id = ? AND is_deleted = 0
    ")->execute([$hashed, $user_id]);

    // Delete used OTP token
    $conn->prepare("DELETE FROM password_resets WHERE user_id = ?")
         ->execute([$user_id]);

    // Clear all forgot-password session keys
    unset(
        $_SESSION['fp_email'],
        $_SESSION['fp_verified'],
        $_SESSION['fp_user_id'],
        $_SESSION['fp_otp_demo'],
        $_SESSION['fp_error'],
        $_SESSION['fp_success']
    );

    $_SESSION['success'] = 'Password reset successfully. You may now log in.';
    header('Location: login.php');
    exit();

} catch (PDOException $e) {
    error_log("Reset Password Error: " . $e->getMessage());
    $_SESSION['fp_error'] = 'A system error occurred. Please try again.';
    header('Location: reset_password.php');
    exit();
}