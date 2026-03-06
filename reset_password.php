<?php
/**
 * reset_password.php
 * Step 3 — user sets a new password after OTP is verified.
 */
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

// Guard — must have verified OTP first
if (empty($_SESSION['fp_verified']) || empty($_SESSION['fp_user_id'])) {
    header('Location: forgot_password.php');
    exit();
}

$error   = $_SESSION['fp_error']   ?? '';
$success = $_SESSION['fp_success'] ?? '';
unset($_SESSION['fp_error'], $_SESSION['fp_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - SCC-CCS Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>.login-card { max-width: 450px; }</style>
</head>
<body class="bg-white">

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="login-card p-5">

        <div class="text-center mb-4">
            <h2 class="text-white font-serif mb-2">Reset Password</h2>
            <p class="text-white-50 small">Create a new password for your account</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form action="process_reset_password.php" method="POST" id="resetForm">
            <div class="mb-3">
                <label for="newPassword" class="form-label text-white small">New Password</label>
                <input type="password" name="newPassword" class="form-control"
                       id="newPassword" placeholder="Min. 6 characters" required minlength="6">
            </div>
            <div class="mb-3">
                <label for="confirmPassword" class="form-label text-white small">Confirm Password</label>
                <input type="password" name="confirmPassword" class="form-control"
                       id="confirmPassword" placeholder="Repeat new password" required minlength="6">
                <div id="matchMsg" class="form-text small mt-1"></div>
            </div>
            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-login btn-lg fw-bold" id="submitBtn">
                    Reset Password
                </button>
            </div>
        </form>

        <div class="mt-4 text-center">
            <a href="login.php" class="text-info small text-decoration-none">Return to Login</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Live password match feedback
const np  = document.getElementById('newPassword');
const cp  = document.getElementById('confirmPassword');
const msg = document.getElementById('matchMsg');
const btn = document.getElementById('submitBtn');

function checkMatch() {
    if (cp.value === '') {
        msg.textContent = '';
        btn.disabled = false;
        return;
    }
    if (np.value === cp.value) {
        msg.textContent = '✓ Passwords match';
        msg.className = 'form-text small mt-1 text-success';
        btn.disabled = false;
    } else {
        msg.textContent = '✗ Passwords do not match';
        msg.className = 'form-text small mt-1 text-danger';
        btn.disabled = true;
    }
}
np.addEventListener('input', checkMatch);
cp.addEventListener('input', checkMatch);
</script>
</body>
</html>