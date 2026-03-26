 <?php
session_start();
require_once __DIR__ . '/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || empty($_SESSION['fp_verified'])
    || empty($_SESSION['fp_user_id'])) {
    header('Location: forgot_password.php');
    exit();
}

$user_id = $_SESSION['fp_user_id'];
$new = $_POST['newPassword'] ?? '';
$confirm = $_POST['confirmPassword'] ?? '';

if (strlen($new) < 6) {
    $_SESSION['fp_error'] = 'Password too short.';
    header('Location: reset_password.php');
    exit();
}

if ($new !== $confirm) {
    $_SESSION['fp_error'] = 'Passwords do not match.';
    header('Location: reset_password.php');
    exit();
}

$conn = get_db();

$hashed = password_hash($new, PASSWORD_BCRYPT);

// Update password
$conn->prepare("
    UPDATE users 
    SET password = ?, reset_requested = 0
    WHERE id = ?
")->execute([$hashed, $user_id]);

// Delete OTP
$conn->prepare("DELETE FROM password_resets WHERE user_id = ?")
     ->execute([$user_id]);

// Clear session
session_destroy();

session_start();
$_SESSION['success'] = "Password reset successful!";

header('Location: login.php');
exit();