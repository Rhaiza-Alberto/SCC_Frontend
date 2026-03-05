<?php
session_start();
require_once 'database.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Recovery - SCC-CCS Syllabus Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap"
        rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Specific overrides if needed, but trying to reuse style.css */
        .login-card {
            max-width: 450px;
        }
    </style>
</head>

<body class="bg-white">

    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="login-card p-5">
            <div class="text-center mb-4">
                <h2 class="text-white font-serif mb-2">Account Recovery</h2>
                <p class="text-white-50 small">Enter your email to continue</p>
            </div>

            <!-- Simulated Form Action -->
            <form action="forgot_password_otp.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label text-white small">Email</label>
                    <input type="email" name="email" class="form-control" id="email" placeholder="Input" required>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-login btn-lg fw-bold">Next</button>
                </div>
            </form>

            <div class="mt-4 text-center">
                <a href="login.php" class="text-info small text-decoration-none">Return to Login</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>