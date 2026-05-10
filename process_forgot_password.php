<?php
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php'; // Ensure send_system_email is defined here

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: forgot_password.php');
    exit();
}

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['fp_error'] = 'Invalid email.';
    header('Location: forgot_password.php');
    exit();
}

$conn = get_db();

// 1. Check if user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND is_deleted = 0 LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['fp_error'] = 'Email not found.';
    header('Location: forgot_password.php');
    exit();
}

// 2. Generate a 6-digit OTP
$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

// 3. Clean up old reset attempts and insert new one
$conn->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$user['id']]);

$stmt = $conn->prepare("
    INSERT INTO password_resets (user_id, token, expires_at)
    VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
");
$stmt->execute([$user['id'], $otp]);

// 4. Save email to session for the next step
$_SESSION['fp_email'] = $email;

// 5. Send the Email using PHPMailer
$subject = "Your Password Reset Code";
$body = "
    <div style='font-family: sans-serif; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
        <h2 style='color: #333;'>Account Recovery</h2>
        <p>You requested a password reset for the <strong>SCC Syllabus Portal</strong>.</p>
        <p>Your verification code is:</p>
        <div style='background: #f4f4f4; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 5px; border-radius: 5px;'>
            $otp
        </div>
        <p style='color: #666; font-size: 13px; margin-top: 20px;'>This code will expire in 10 minutes. If you did not request this, please ignore this email.</p>
    </div>
";

if (send_system_email($email, $subject, $body)) {
    $_SESSION['fp_success'] = 'Reset code has been sent to your email.';
} else {
    $_SESSION['fp_error'] = 'Failed to send email. Please try again later.';
    // For development/demo purposes only:
    $_SESSION['fp_otp_demo'] = $otp; 
}

// 6. Redirect to OTP verification page
header('Location: forgot_password_otp.php');
exit();