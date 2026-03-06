 <?php
/**
 * login.php
 */
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

// Redirect if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    switch ($_SESSION['role'] ?? '') {
        case 'faculty':   header('Location: faculty/faculty_dashboard.php'); break;
        case 'dept_head': header('Location: dept_head/dept_dashboard.php');  break;
        case 'dean':      header('Location: admin/admin_dashboard.php');       break;
        case 'vpaa':      header('Location: vpaa/vpaa_dashboard.php');       break;
        default:          header('Location: faculty/faculty_dashboard.php');
    }
    exit();
}

$error_message   = $_SESSION['error']   ?? '';
$success_message = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SCC-CCS Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-white">

<div class="container pt-3">
    <a href="index.php" class="btn btn-outline-dark position-absolute top-0 start-0 m-3">&larr; Back</a>
</div>

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="login-card p-5 shadow-lg rounded-4 position-relative">

        <h2 class="text-white font-serif mb-4 text-center">SCC-CCS Syllabus Portal</h2>

        <div class="mb-4 text-center">
            <img src="css/logo.png" alt="Logo" class="rounded-circle" style="width:100px;height:100px;">
        </div>

        <form method="POST" action="process_login.php">

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($success_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label text-white small">Email</label>
                <input type="email" name="email" class="form-control"
                       placeholder="Enter your email" required>
            </div>

            <div class="mb-3">
                <label class="form-label text-white small">Password</label>
                <input type="password" name="password" class="form-control"
                       placeholder="Enter your password" required>
            </div>

            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-login btn-lg fw-bold rounded-3">Log In</button>
            </div>

        </form>

        <div class="mt-4 text-center">
            <p class="text-white small mb-1">
                Don't have an account?
                <a href="register.php" class="text-info text-decoration-none">Sign Up Here</a>
            </p>
            <a href="forgot_password.php" class="text-info small text-decoration-none">Forgot Password?</a>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>