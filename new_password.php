<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - SCC-CCS Syllabus Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap"
        rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        .login-card {
            max-width: 450px;
        }
    </style>
</head>

<body class="bg-white">

    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="login-card p-5">
            <div class="text-center mb-4">
                <h2 class="text-white font-serif mb-2">Reset Password</h2>
                <p class="text-white-50 small">Create a new password for your account</p>
            </div>

            <!-- Simulated Form Action - In a real app this would post to a PHP handler -->
            <form action="login.php" method="POST">
                <div class="mb-3">
                    <label for="newPassword" class="form-label text-white small">New Password</label>
                    <input type="password" class="form-control" id="newPassword" placeholder="Enter new password"
                        required>
                </div>

                <div class="mb-3">
                    <label for="confirmPassword" class="form-label text-white small">Confirm Password</label>
                    <input type="password" class="form-control" id="confirmPassword" placeholder="Confirm new password"
                        required>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-login btn-lg fw-bold"
                        onclick="alert('Password has been reset successfully!')">Reset Password</button>
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