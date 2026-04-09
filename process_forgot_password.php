 <?php
session_start();
require_once __DIR__ . '/database.php';

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

// Check user
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND is_deleted = 0 LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['fp_error'] = 'Email not found.';
    header('Location: forgot_password.php');
    exit();
}

$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

// Delete old OTP
$conn->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$user['id']]);

// ✅ NEW INSERT (PUT THIS HERE)
$stmt = $conn->prepare("
    INSERT INTO password_resets (user_id, token, expires_at)
    VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
");
$stmt->execute([$user['id'], $otp]);

// Save session
$_SESSION['fp_email'] = $email;
$_SESSION['fp_otp_demo'] = $otp; // DEMO only

header('Location: forgot_password_otp.php');
exit();