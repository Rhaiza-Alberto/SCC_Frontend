 <?php
/**
 * forgot_password.php
 * Step 1 — user enters their email to request a password reset.
 */
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

// Already logged in? Redirect away
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
    <title>Account Recovery - SCC-CCS Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>.login-card { max-width: 450px; }</style>
</head>
<body class="bg-white">

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="login-card p-5">

        <div class="text-center mb-4">
            <h2 class="text-white font-serif mb-2">Account Recovery</h2>
            <p class="text-white-50 small">Enter your registered email address</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form action="process_forgot_password.php" method="POST">
            <div class="mb-3">
                <label for="email" class="form-label text-white small">Email</label>
                <input type="email" name="email" class="form-control" id="email"
                       placeholder="yourname@gmail.com" required>
            </div>
            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-login btn-lg fw-bold">Send Reset Code</button>
            </div>
        </form>

        <div class="mt-4 text-center">
            <a href="login.php" class="text-info small text-decoration-none">Return to Login</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>