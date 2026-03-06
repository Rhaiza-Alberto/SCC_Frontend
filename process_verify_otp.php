<?php
/**
 * process_verify_otp.php
 * Validates the OTP entered by the user against the DB token.
 */
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['fp_email'])) {
    header('Location: forgot_password.php');
    exit();
}

$otp   = trim($_POST['otp'] ?? '');
$email = $_SESSION['fp_email'];

if (empty($otp) || !preg_match('/^\d{6}$/', $otp)) {
    $_SESSION['fp_error'] = 'Please enter a valid 6-digit code.';
    header('Location: forgot_password_otp.php');
    exit();
}

try {
    $conn = get_db();

    // Look up user
    $uStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND is_deleted = 0 LIMIT 1");
    $uStmt->execute([$email]);
    $user = $uStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['fp_error'] = 'Session expired. Please start again.';
        header('Location: forgot_password.php');
        exit();
    }

    // Check token — must match and not be expired
    $tStmt = $conn->prepare("
        SELECT id FROM password_resets
        WHERE user_id  = ?
          AND token    = ?
          AND expires_at > NOW()
        LIMIT 1
    ");
    $tStmt->execute([$user['id'], $otp]);
    $token = $tStmt->fetch(PDO::FETCH_ASSOC);

    if (!$token) {
        $_SESSION['fp_error'] = 'Invalid or expired code. Please try again.';
        header('Location: forgot_password_otp.php');
        exit();
    }

    // OTP is valid — mark session as verified so reset_password.php can proceed
    $_SESSION['fp_verified'] = true;
    $_SESSION['fp_user_id']  = $user['id'];
    // Keep fp_email for display on reset page
    unset($_SESSION['fp_otp_demo']);

    header('Location: reset_password.php');
    exit();

} catch (PDOException $e) {
    error_log("OTP Verify Error: " . $e->getMessage());
    $_SESSION['fp_error'] = 'A system error occurred. Please try again.';
    header('Location: forgot_password_otp.php');
    exit();
}