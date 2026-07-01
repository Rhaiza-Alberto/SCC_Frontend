 <?php
/**
 * forgot_password.php 
 */
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: login.php');
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
    <title>Account Recovery — SCC Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/design-system.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-form-side">
            <div class="auth-form-container animate-in" style="max-width:420px">
                <a href="login.php" class="d-inline-flex align-items-center text-decoration-none mb-4" style="color:var(--text-secondary);font-size:0.85rem">
                    <i class="bi bi-arrow-left me-2"></i> Back to Login
                </a>

                <div class="mb-3">
                    <div style="width:56px;height:56px;border-radius:var(--radius-md);background:var(--primary-light);display:flex;align-items:center;justify-content:center">
                        <i class="bi bi-shield-lock" style="font-size:1.5rem;color:var(--primary)"></i>
                    </div>
                </div>

                <h1 style="font-size:1.5rem">Account Recovery</h1>
                <p class="subtitle">Enter your registered email to receive a reset code</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show rounded-3 border-0 shadow-sm" style="font-size:0.85rem">
                        <i class="bi bi-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show rounded-3 border-0 shadow-sm" style="font-size:0.85rem">
                        <i class="bi bi-check-circle me-1"></i> <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="process_forgot_password.php" method="POST">
                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text)">Email Address</label>
                        <div class="position-relative">
                            <i class="bi bi-envelope position-absolute" style="left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
                            <input type="email" name="email" class="auth-input" style="padding-left:2.5rem" placeholder="name@gmail.com" required id="email">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary-scc w-100 py-3 fw-bold" style="font-size:0.95rem;border-radius:var(--radius-sm)">Send Reset Code</button>
                </form>

                <div class="text-center mt-4">
                    <a href="login.php" class="text-decoration-none small" style="color:var(--primary)">Return to Login</a>
                </div>
            </div>
        </div>

        <div class="auth-illustration-side">
            <div class="text-center text-white px-5" style="position:relative;z-index:2;max-width:400px">
                <i class="bi bi-key-fill" style="font-size:4rem;color:var(--primary);opacity:0.6"></i>
                <h3 class="fw-bold mt-3" style="font-family:var(--font-serif)">Secure Recovery</h3>
                <p style="color:rgba(255,255,255,0.5);font-size:0.9rem">We'll send a verification code to your email to reset your password securely.</p>
            </div>
            <div style="position:absolute;top:-100px;right:-100px;width:300px;height:300px;border-radius:50%;background:rgba(255,136,0,0.05)"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/theme.js"></script>
</body>
</html>