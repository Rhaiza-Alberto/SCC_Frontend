 <?php
/**
 * forgot_password_otp.php
 * Step 2 — user enters the 6-digit OTP sent to their email.
 */
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

if (empty($_SESSION['fp_email'])) {
    header('Location: forgot_password.php');
    exit();
}

$error   = $_SESSION['fp_error']   ?? '';
$success = $_SESSION['fp_success'] ?? '';
unset($_SESSION['fp_error'], $_SESSION['fp_success']);

// Demo: show the generated OTP on-screen (remove this in production)
$demo_otp = $_SESSION['fp_otp_demo'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code - SCC-CCS Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>.login-card { max-width: 450px; }</style>
</head>
<body class="bg-white">

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="login-card p-5">

        <div class="text-center mb-4">
            <h2 class="text-white font-serif mb-2">Verify It's You</h2>
            <p class="text-white-50 small">
                Enter the 6-digit code sent to
                <strong class="text-orange"><?= htmlspecialchars($_SESSION['fp_email']) ?></strong>
            </p>
        </div>

        <?php if ($demo_otp): ?>
            <!-- DEMO ONLY: remove this block in production when real email is configured -->
            <div class="alert alert-warning text-center small mb-3">
                <strong>Demo mode:</strong> Your reset code is
                <span class="fw-bold fs-5 text-dark"><?= htmlspecialchars($demo_otp) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form action="process_verify_otp.php" method="POST">
            <div class="mb-3">
                <label for="otp" class="form-label text-white small">Enter Code</label>
                <input type="text" name="otp" class="form-control text-center fw-bold fs-4 letter-spacing-3"
                       id="otp" placeholder="000000" maxlength="6"
                       pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code" required>
            </div>
            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-login btn-lg fw-bold">Verify Code</button>
            </div>
        </form>

        <div class="mt-3 text-center">
            <form action="process_forgot_password.php" method="POST" class="d-inline">
                <input type="hidden" name="email" value="<?= htmlspecialchars($_SESSION['fp_email']) ?>">
                <button type="submit" class="btn btn-link text-info small text-decoration-none p-0">
                    Resend Code
                </button>
            </form>
        </div>
        <div class="mt-2 text-center">
            <a href="forgot_password.php" class="text-info small text-decoration-none">Back</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>