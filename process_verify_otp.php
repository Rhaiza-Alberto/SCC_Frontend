 <?php
session_start();
require_once __DIR__ . '/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['fp_email'])) {
    header('Location: forgot_password.php');
    exit();
}

$otp = trim($_POST['otp'] ?? '');
$email = $_SESSION['fp_email'];

if (!preg_match('/^\d{6}$/', $otp)) {
    $_SESSION['fp_error'] = 'Enter valid 6-digit code.';
    header('Location: forgot_password_otp.php');
    exit();
}

$conn = get_db();

// Get user
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['fp_error'] = 'Session expired.';
    header('Location: forgot_password.php');
    exit();
}

// Check OTP
$stmt = $conn->prepare("
    SELECT * FROM password_resets
    WHERE user_id = ?
      AND token = ?
      AND expires_at > NOW()
    LIMIT 1
");
$stmt->execute([$user['id'], $otp]);
$token = $stmt->fetch();

if (!$token) {
    $_SESSION['fp_error'] = 'Invalid or expired OTP.';
    header('Location: forgot_password_otp.php');
    exit();
}

// SUCCESS
$_SESSION['fp_verified'] = true;
$_SESSION['fp_user_id'] = $user['id'];

header('Location: reset_password.php');
exit();