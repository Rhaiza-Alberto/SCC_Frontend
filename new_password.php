<?php
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

$error = $_SESSION['np_error'] ?? '';
unset($_SESSION['np_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — SCC Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/design-system.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <button class="theme-toggle" id="themeToggleBtn" title="Toggle dark mode"><i class="bi bi-moon-fill"></i></button>

    <div class="auth-wrapper">
        <div class="auth-form-side">
            <div class="auth-form-container animate-in" style="max-width:420px">
                <a href="login.php" class="d-inline-flex align-items-center text-decoration-none mb-4" style="color:var(--text-secondary);font-size:0.85rem">
                    <i class="bi bi-arrow-left me-2"></i> Back to Login
                </a>

                <div class="mb-3">
                    <div style="width:56px;height:56px;border-radius:var(--radius-md);background:rgba(34,197,94,0.1);display:flex;align-items:center;justify-content:center">
                        <i class="bi bi-key" style="font-size:1.5rem;color:var(--success)"></i>
                    </div>
                </div>

                <h1 style="font-size:1.5rem">Reset Password</h1>
                <p class="subtitle">Create a new secure password for your account</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show rounded-3 border-0 shadow-sm" style="font-size:0.85rem">
                        <i class="bi bi-exclamation-circle me-1"></i> <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="process_new_password.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text)">New Password</label>
                        <div class="position-relative">
                            <i class="bi bi-lock position-absolute" style="left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
                            <input type="password" name="newPassword" class="auth-input" style="padding-left:2.5rem" placeholder="Enter new password" required id="newPassword">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text)">Confirm Password</label>
                        <div class="position-relative">
                            <i class="bi bi-lock-fill position-absolute" style="left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
                            <input type="password" name="confirmPassword" class="auth-input" style="padding-left:2.5rem" placeholder="Confirm new password" required id="confirmPassword">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary-scc w-100 py-3 fw-bold" style="font-size:0.95rem;border-radius:var(--radius-sm)">Reset Password</button>
                </form>

                <div class="text-center mt-4">
                    <a href="login.php" class="text-decoration-none small" style="color:var(--primary)">Return to Login</a>
                </div>
            </div>
        </div>

        <div class="auth-illustration-side">
            <div class="text-center text-white px-5" style="position:relative;z-index:2;max-width:400px">
                <i class="bi bi-shield-lock-fill" style="font-size:4rem;color:var(--primary);opacity:0.6"></i>
                <h3 class="fw-bold mt-3" style="font-family:var(--font-serif)">Almost There</h3>
                <p style="color:rgba(255,255,255,0.5);font-size:0.9rem">Set a strong password to protect your account and get back to managing your syllabi.</p>
            </div>
            <div style="position:absolute;top:-100px;right:-100px;width:300px;height:300px;border-radius:50%;background:rgba(255,136,0,0.05)"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/theme.js"></script>
</body>
</html>