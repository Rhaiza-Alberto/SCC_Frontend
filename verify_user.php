<?php
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

$email = $_REQUEST['email'] ?? '';
$error = '';
$success = false;
$message = '';

// Handle OTP Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    $otp = trim($_POST['otp']);
    $user_email = trim($_POST['email'] ?? '');

    if (strlen($otp) !== 6) {
        $error = "Please enter the 6-digit code sent to your email.";
    } elseif (empty($user_email)) {
        $error = "Email address is missing. Please enter your email.";
    } else {
        try {
            $conn = get_db();
            // Check OTP
            $stmt = $conn->prepare("SELECT id, first_name FROM users WHERE email = ? AND verification_token = ? AND is_deleted = 0");
            $stmt->execute([$user_email, $otp]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Mark as verified
                $update = $conn->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?");
                $update->execute([$user['id']]);
                
                $success = true;
                $message = "Verification Successful! Your account is now active and verified.";
                
                // Notify Dean if it's a new faculty
                $dean = get_dean();
                if ($dean) {
                    notify_user($dean['id'], "User " . htmlspecialchars($user['first_name']) . " has verified their email.", null);
                }
            } else {
                $error = "The 6-digit code you entered is incorrect. Please check your email.";
            }
        } catch (PDOException $e) {
            $error = "System error. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account - SCC-CCS Syllabus Portal</title>
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
                
                <a href="login.php" class="d-inline-flex align-items-center text-decoration-none mb-4" style="color:var(--text-secondary);font-size:0.85rem">
                    <i class="bi bi-arrow-left me-2"></i> Back to Login
                </a>

                <?php if ($success): ?>
                    <div class="mb-3">
                        <div style="width:56px;height:56px;border-radius:var(--radius-md);background:var(--success-light);display:flex;align-items:center;justify-content:center">
                            <i class="bi bi-check-circle" style="font-size:1.5rem;color:var(--success)"></i>
                        </div>
                    </div>

                    <h1 style="font-size:1.5rem">Verified!</h1>
                    <p class="subtitle mb-4"><?= htmlspecialchars($message) ?></p>

                    <a href="login.php" class="btn btn-primary-scc w-100 py-3 fw-bold mb-4 shadow-sm text-center text-decoration-none" style="font-size:1rem; border-radius: 12px; display: block;">
                        Login to Portal
                    </a>
                <?php else: ?>
                    <div class="mb-3">
                        <div style="width:56px;height:56px;border-radius:var(--radius-md);background:rgba(255,136,0,0.1);display:flex;align-items:center;justify-content:center">
                            <i class="bi bi-shield-check" style="font-size:1.5rem;color:var(--primary)"></i>
                        </div>
                    </div>

                    <h1 style="font-size:1.5rem">Verify Account</h1>
                    <p class="subtitle">Enter the 6-digit code sent to <strong class="text-orange"><?= htmlspecialchars($email) ?></strong></p>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" style="font-size:0.85rem">
                            <i class="bi bi-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-uppercase opacity-75" style="letter-spacing:1px; color:var(--text)">Verification Code</label>
                            <input type="text" name="otp" class="auth-input text-center fw-bold otp-field" 
                                   style="font-size: 2.2rem; letter-spacing: 0.8rem; height: auto; padding: 0.8rem; border-radius: 12px; font-family: 'Courier New', Courier, monospace;"
                                   placeholder="000000" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" required autofocus autocomplete="one-time-code">
                        </div>

                        <button type="submit" class="btn btn-primary-scc w-100 py-3 fw-bold mb-4 shadow-sm" style="font-size:1rem; border-radius: 12px;">
                            Verify & Activate <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                    </form>

                    <div class="text-center pt-2">
                        <p class="small text-muted mb-0">Didn't receive a code?</p>
                        <a href="resend_verification.php?email=<?= urlencode($email) ?>" class="text-decoration-none small fw-bold" style="color:var(--primary)">
                            Resend Code
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="auth-illustration-side" style="background: linear-gradient(135deg, #1a1a1a 0%, #333 100%);">
            <div class="text-center text-white px-5" style="position:relative;z-index:2;max-width:400px">
                <div class="mb-4 d-inline-block p-4 rounded-circle" style="background: rgba(255,136,0,0.1); border: 1px solid rgba(255,136,0,0.2);">
                    <i class="bi bi-shield-lock-fill" style="font-size:3.5rem; color:var(--primary)"></i>
                </div>
                <h3 class="fw-bold" style="font-family:var(--font-serif)">Secure Activation</h3>
                <p style="color:rgba(255,255,255,0.6); font-size:0.95rem; line-height:1.6">We have dispatched a security validation pin to your email address. This step verifies ownership to safely activate your academic portal permissions.</p>
            </div>
            <div style="position:absolute; bottom:-50px; left:-50px; width:200px; height:200px; border-radius:50%; background:radial-gradient(circle, rgba(255,136,0,0.1) 0%, transparent 70%);"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Restrict user input strictly to integers
        const otpInput = document.querySelector('.otp-field');
        if (otpInput) {
            otpInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }
    </script>
    <script src="js/theme.js"></script>
</body>
</html>