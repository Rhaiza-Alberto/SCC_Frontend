<?php
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if ($_SESSION['role'] == 'faculty') {
        header('Location: faculty/faculty_dashboard.php');
    } elseif ($_SESSION['role'] == 'admin') {
        header('Location: admin/admin_dashboard.php');
    } elseif ($_SESSION['role'] == 'dept_head') {
        header('Location: dept_head/dept_dashboard.php');
    } else if ($_SESSION['role'] == 'vpaa') {
        header('Location: vpaa/vpaa_dashboard.php');
    }
    exit();
}

// Check for error messages from login process
$error_message = '';
if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SCC-CCS Syllabus Portal</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap"
        rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="bg-white">

    <div class="container pt-3">
        <a href="index.php" class="btn btn-outline-dark position-absolute top-0 start-0 m-3">
            &larr; Back
        </a>
    </div>

    <div class="container d-flex justify-content-center align-items-center min-vh-100">

        <div class="login-card p-5 shadow-lg rounded-4 position-relative">

            <!-- Title -->
            <h2 class="text-white font-serif mb-4 text-center">
                SCC- CCS Syllabus Portal
            </h2>

            <!-- Logo -->
            <div class="mb-4 text-center">
                <img src="css/logo.png" alt="Logo" class="rounded-circle" style="width: 100px; height: 100px;">
            </div>

            <!-- Login Form -->
            <form method="POST" action="process_login.php">

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label text-white small">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                </div>

                <div class="mb-3">
                    <label class="form-label text-white small">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter your password"
                        required>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-login btn-lg fw-bold rounded-3">
                        Log In
                    </button>
                </div>

            </form>

            <!-- Links -->
            <div class="mt-4 text-center">
                <p class="text-white small mb-1">
                    Don't have an account?
                    <a href="register.php" class="text-info text-decoration-none">
                        Sign Up Here
                    </a>
                </p>

                <a href="forgot_password.php" class="text-info small text-decoration-none">
                    Forgot Password?
                </a>
            </div>

        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>