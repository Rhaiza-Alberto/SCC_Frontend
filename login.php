<?php
/**
 * login.php — Modern split-screen Dribbble-style login
 */
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    switch ($_SESSION['role'] ?? '') {
        case 'faculty':
            header('Location: faculty/faculty_dashboard.php');
            break;
        case 'dept_head':
            header('Location: dept_head/dept_dashboard.php');
            break;
        case 'dean':
            header('Location: admin/admin_dashboard.php');
            break;
        case 'vpaa':
            header('Location: vpaa/vpaa_dashboard.php');
            break;
        default:
            header('Location: faculty/faculty_dashboard.php');
    }
    exit();
}

$error_message   = $_SESSION['error']   ?? '';
$success_message = $_SESSION['success'] ?? '';
$timeout_msg     = isset($_GET['timeout']) ? 'Your session expired due to inactivity. Please log in again.' : '';
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SCC Syllabus Portal</title>
    <meta name="description" content="Sign in to the SCC-CCS Syllabus Management Portal">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/design-system.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <div class="auth-wrapper">
        <!-- Left: Login Form -->
        <div class="auth-form-side">
            <div class="auth-form-container animate-in">
                <!-- Back Link -->
                <a href="index.php" class="d-inline-flex align-items-center text-decoration-none mb-4"
                    style="color:var(--text-secondary);font-size:0.85rem">
                    <i class="bi bi-arrow-left me-2"></i> Back to Home
                </a>

                <!-- Logo -->
                <div class="mb-4">
                    <img src="css/logo.png" alt="SCC Logo"
                        style="width:64px;height:64px;border-radius:50%;border:2px solid var(--primary-border);padding:2px">
                </div>

                <h1>Welcome Back</h1>
                <p class="subtitle">Sign in to your SCC-CCS Syllabus Portal account</p>

                <?php if (!empty($timeout_msg)): ?>
                    <div class="alert alert-warning alert-dismissible fade show rounded-3 border-0 shadow-sm d-flex align-items-center gap-2" role="alert"
                        style="font-size:0.85rem">
                        <i class="bi bi-clock-history flex-shrink-0"></i>
                        <span><?= htmlspecialchars($timeout_msg) ?></span>
                        <button type="button" class="btn-close btn-close-sm ms-auto" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show rounded-3 border-0 shadow-sm" role="alert"
                        style="font-size:0.85rem">
                        <i class="bi bi-check-circle me-1"></i> <?= htmlspecialchars($success_message) ?>
                        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show rounded-3 border-0 shadow-sm" role="alert"
                        style="font-size:0.85rem">
                        <i class="bi bi-exclamation-circle me-1"></i> <?= $error_message ?>
                        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="process_login.php">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text)">Email
                            Address</label>
                        <div class="position-relative">
                            <i class="bi bi-envelope position-absolute"
                                style="left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
                            <input type="email" name="email" class="auth-input" style="padding-left:2.5rem"
                                placeholder="name@example.com" required id="loginEmail">
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label fw-semibold"
                                style="font-size:0.85rem;color:var(--text)">Password</label>
                            <a href="forgot_password.php"
                                style="font-size:0.8rem;text-decoration:none;color:var(--primary)">Forgot password?</a>
                        </div>
                        <div class="position-relative">
                            <i class="bi bi-lock position-absolute"
                                style="left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
                            <input type="password" name="password" class="auth-input" style="padding-left:2.5rem"
                                placeholder="Enter your password" required id="loginPassword">
                            <button type="button" class="btn btn-link position-absolute"
                                style="right:4px;top:50%;transform:translateY(-50%);color:var(--text-muted);text-decoration:none"
                                onclick="togglePassword(this,'loginPassword')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="rememberMe"
                                style="border-color:var(--border)">
                            <label class="form-check-label" for="rememberMe"
                                style="font-size:0.85rem;color:var(--text-secondary)">Remember me</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-scc w-100 py-3 fw-bold"
                        style="font-size:0.95rem;border-radius:var(--radius-sm)" id="loginSubmitBtn">
                        Sign In
                    </button>
                </form>

                <div class="text-center mt-4">
                    <p style="font-size:0.85rem;color:var(--text-secondary)">
                        Don't have an account?
                        <a href="register.php" class="fw-semibold text-decoration-none"
                            style="color:var(--primary)">Create Account</a>
                    </p>
                </div>
            </div>
        </div>

        <!-- Right: Illustration Panel -->
        <div class="auth-illustration-side">
            <div class="text-center text-white px-5" style="position:relative;z-index:2;max-width:500px">
                <!-- Decorative SVG -->
                <svg width="280" height="220" viewBox="0 0 280 220" fill="none" xmlns="http://www.w3.org/2000/svg"
                    class="mb-4 animate-in">
                    <rect x="40" y="30" width="200" height="160" rx="12" fill="rgba(255,255,255,0.05)"
                        stroke="rgba(255,136,0,0.3)" stroke-width="1.5" />
                    <rect x="60" y="55" width="160" height="8" rx="4" fill="rgba(255,136,0,0.4)" />
                    <rect x="60" y="75" width="120" height="6" rx="3" fill="rgba(255,255,255,0.15)" />
                    <rect x="60" y="90" width="140" height="6" rx="3" fill="rgba(255,255,255,0.1)" />
                    <rect x="60" y="105" width="100" height="6" rx="3" fill="rgba(255,255,255,0.08)" />
                    <rect x="60" y="130" width="80" height="30" rx="6" fill="rgba(255,136,0,0.6)" />
                    <circle cx="220" cy="145" r="20" fill="rgba(34,197,94,0.3)" stroke="rgba(34,197,94,0.5)"
                        stroke-width="1.5" />
                    <path d="M212 145L218 151L228 139" stroke="rgba(34,197,94,0.8)" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <circle cx="60" cy="20" r="8" fill="rgba(255,136,0,0.2)" />
                    <circle cx="240" cy="40" r="12" fill="rgba(59,130,246,0.15)" />
                    <circle cx="30" cy="120" r="6" fill="rgba(34,197,94,0.2)" />
                </svg>

                <h2 class="fw-bold mb-3" style="font-family:var(--font-serif);font-size:1.6rem">
                    Streamline Your<br>
                    <span style="color:var(--primary)">Syllabus Workflow</span>
                </h2>
                <p style="color:rgba(255,255,255,0.6);font-size:0.9rem;line-height:1.7">
                    Submit, track, and manage course syllabi with a modern approval workflow designed for academic
                    excellence.
                </p>

                <!-- Trust indicators -->
                <div class="d-flex justify-content-center gap-4 mt-4"
                    style="color:rgba(255,255,255,0.4);font-size:0.75rem">
                    <div><i class="bi bi-shield-check me-1" style="color:var(--primary)"></i> Secure</div>
                    <div><i class="bi bi-lightning me-1" style="color:var(--primary)"></i> Fast</div>
                    <div><i class="bi bi-people me-1" style="color:var(--primary)"></i> Collaborative</div>
                </div>
            </div>

            <!-- Background decorative circles -->
            <div
                style="position:absolute;top:-100px;right:-100px;width:300px;height:300px;border-radius:50%;background:rgba(255,136,0,0.05)">
            </div>
            <div
                style="position:absolute;bottom:-80px;left:-80px;width:250px;height:250px;border-radius:50%;background:rgba(59,130,246,0.04)">
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/theme.js"></script>
    <script>
        function togglePassword(btn, inputId) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }
    </script>
</body>

</html>