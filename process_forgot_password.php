<?php
/**
 * process_forgot_password.php
 * Generates a 6-digit OTP, stores it in the DB (password_resets table),
 * and shows it on-screen (no email server required for local/demo use).
 */
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: forgot_password.php');
    exit();
}

$email = trim($_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['fp_error'] = 'Please enter a valid email address.';
    header('Location: forgot_password.php');
    exit();
}

try {
    $conn = get_db();

    // Check the user exists and is not deleted
    $stmt = $conn->prepare("SELECT id, first_name FROM users WHERE email = ? AND is_deleted = 0 LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Don't reveal whether the email exists — generic message
        $_SESSION['fp_error'] = 'If that email is registered you will see a code below.';
        header('Location: forgot_password.php');
        exit();
    }

    // Generate a 6-digit OTP
    $otp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // Remove any existing reset tokens for this user
    $conn->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$user['id']]);

    // Insert new token
    $conn->prepare("
        INSERT INTO password_resets (user_id, token, expires_at, created_at)
        VALUES (?, ?, ?, NOW())
    ")->execute([$user['id'], $otp, $expires]);

    // Mark reset_requested on the user row
    $conn->prepare("UPDATE users SET reset_requested = 1 WHERE id = ?")
         ->execute([$user['id']]);

    // Store email in session so the OTP page knows which user to verify
    $_SESSION['fp_email'] = $email;

    // For demo/local use — display the OTP directly (replace with mail() for production)
    $_SESSION['fp_otp_demo'] = $otp;

    header('Location: forgot_password_otp.php');
    exit();

} catch (PDOException $e) {
    error_log("Forgot Password Error: " . $e->getMessage());
    $_SESSION['fp_error'] = 'A system error occurred. Please try again.';
    header('Location: forgot_password.php');
    exit();
}