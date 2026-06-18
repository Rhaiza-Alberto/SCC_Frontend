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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/design-system.css">
    <link rel="stylesheet" href="css/style.css">
    <style>.login-card { max-width: 450px; }</style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-form-side">
            <div class="auth-form-container animate-in" style="max-width:420px">
                <a href="forgot_password.php" class="d-inline-flex align-items-center text-decoration-none mb-4" style="color:var(--text-secondary);font-size:0.85rem">
                    <i class="bi bi-arrow-left me-2"></i> Back to Recovery
                </a>

                <div class="mb-3">
                    <div style="width:56px;height:56px;border-radius:var(--radius-md);background:var(--success-light);display:flex;align-items:center;justify-content:center">
                        <i class="bi bi-shield-check" style="font-size:1.5rem;color:var(--success)"></i>
                    </div>
                </div>

                <h1 style="font-size:1.5rem">Verify It's You</h1>
                <p class="subtitle">Enter the 6-digit code sent to <strong class="text-orange"><?= htmlspecialchars($_SESSION['fp_email']) ?></strong></p>

                <?php if ($demo_otp): ?>
                    <div class="alert alert-warning border-0 shadow-sm rounded-4 py-2 mb-4 text-center" style="font-size:0.82rem; background: #fff8f0; border: 1px dashed #ff8800 !important;">
                        <span class="text-muted me-2">Demo Code:</span> <span class="fw-bold fs-6 text-orange" style="letter-spacing: 2px;"><?= htmlspecialchars($demo_otp) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" style="font-size:0.85rem">
                        <i class="bi bi-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="process_verify_otp.php" method="POST">
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase opacity-75" style="letter-spacing:1px; color:var(--text)">Verification Code</label>
                        <input type="text" name="otp" class="auth-input text-center fw-bold" 
                               style="font-size: 2.2rem; letter-spacing: 0.8rem; height: auto; padding: 0.8rem; border-radius: 12px; font-family: 'Courier New', Courier, monospace;"
                               placeholder="000000" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary-scc w-100 py-3 fw-bold mb-4 shadow-sm" style="font-size:1rem; border-radius: 12px;">
                        Verify & Continue <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </form>

                <div class="text-center pt-2">
                    <p class="small text-muted mb-0">Didn't receive a code?</p>
                    <form action="process_forgot_password.php" method="POST" class="d-inline">
                        <input type="hidden" name="email" value="<?= htmlspecialchars($_SESSION['fp_email']) ?>">
                        <button type="submit" class="btn btn-link text-decoration-none small p-0 fw-bold" style="color:var(--primary)">
                            Resend Code
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="auth-illustration-side" style="background: linear-gradient(135deg, #1a1a1a 0%, #333 100%);">
            <div class="text-center text-white px-5" style="position:relative;z-index:2;max-width:400px">
                <div class="mb-4 d-inline-block p-4 rounded-circle" style="background: rgba(255,136,0,0.1); border: 1px solid rgba(255,136,0,0.2);">
                    <i class="bi bi-shield-lock-fill" style="font-size:3.5rem; color:var(--primary)"></i>
                </div>
                <h3 class="fw-bold" style="font-family:var(--font-serif)">Secure Recovery</h3>
                <p style="color:rgba(255,255,255,0.6); font-size:0.95rem; line-height:1.6">We've sent a one-time verification code to your email. This ensures that only you can access and reset your account password.</p>
            </div>
            <!-- Decorative circle -->
            <div style="position:absolute; bottom:-50px; left:-50px; width:200px; height:200px; border-radius:50%; background:radial-gradient(circle, rgba(255,136,0,0.1) 0%, transparent 70%);"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/theme.js"></script>
</body>
</html>
