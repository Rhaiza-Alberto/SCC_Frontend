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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/design-system.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .login-card { max-width: 450px; }
        .custom-label {
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--text);
            opacity: 0.85;
            margin-bottom: 0.35rem;
            display: block;
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-form-side">
            <div class="auth-form-container animate-in" style="max-width:420px">
                
                <a href="forgot_password_otp.php" class="d-inline-flex align-items-center text-decoration-none mb-4" style="color:var(--text-secondary);font-size:0.85rem">
                    <i class="bi bi-arrow-left me-2"></i> Back to Verification
                </a>

                <div class="mb-3">
                    <div style="width:56px;height:56px;border-radius:var(--radius-md);background:rgba(255,136,0,0.1);display:flex;align-items:center;justify-content:center">
                        <i class="bi bi-key-fill" style="font-size:1.5rem;color:var(--primary)"></i>
                    </div>
                </div>

                <h1 style="font-size:1.5rem">Reset Password</h1>
                <p class="subtitle">Create a strong, new password for your institutional account</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" style="font-size:0.85rem">
                        <i class="bi bi-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="process_reset_password.php" method="POST" id="resetForm">
                    <div class="mb-3">
                        <label for="newPassword" class="custom-label">New Password</label>
                        <input type="password" name="newPassword" class="auth-input"
                               id="newPassword" placeholder="Min. 6 characters" required minlength="6">
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirmPassword" class="custom-label">Confirm Password</label>
                        <input type="password" name="confirmPassword" class="auth-input"
                               id="confirmPassword" placeholder="Repeat new password" required minlength="6">
                        <div id="matchMsg" class="form-text small mt-1"></div>
                    </div>

                    <button type="submit" class="btn btn-primary-scc w-100 py-3 fw-bold mb-4 shadow-sm" id="submitBtn" style="font-size:1rem; border-radius: 12px;">
                        Reset Password
                    </button>
                </form>

                <div class="text-center pt-2">
                    <a href="login.php" class="text-decoration-none small fw-bold" style="color:var(--primary)">
                        Return to Login
                    </a>
                </div>
            </div>
        </div>

        <div class="auth-illustration-side" style="background: linear-gradient(135deg, #1a1a1a 0%, #333 100%);">
            <div class="text-center text-white px-5" style="position:relative;z-index:2;max-width:400px">
                <div class="mb-4 d-inline-block p-4 rounded-circle" style="background: rgba(255,136,0,0.1); border: 1px solid rgba(255,136,0,0.2);">
                    <i class="bi bi-shield-lock-fill" style="font-size:3.5rem; color:var(--primary)"></i>
                </div>
                <h3 class="fw-bold" style="font-family:var(--font-serif)">Secure Recovery</h3>
                <p style="color:rgba(255,255,255,0.6); font-size:0.95rem; line-height:1.6">Make sure your new password uses a secure combination of letters, numbers, and elements that remain highly confidential.</p>
            </div>
            <div style="position:absolute; bottom:-50px; left:-50px; width:200px; height:200px; border-radius:50%; background:radial-gradient(circle, rgba(255,136,0,0.1) 0%, transparent 70%);"></div>
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
            msg.className = 'form-text small mt-1 text-success fw-bold';
            btn.disabled = false;
        } else {
            msg.textContent = '✗ Passwords do not match';
            msg.className = 'form-text small mt-1 text-danger fw-bold';
            btn.disabled = true;
        }
    }
    np.addEventListener('input', checkMatch);
    cp.addEventListener('input', checkMatch);
    </script>
    <script src="js/theme.js"></script>
</body>
</html>