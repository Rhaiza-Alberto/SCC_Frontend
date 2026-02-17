<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code - SCC-CCS Syllabus Portal</title>
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

<body style="background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%); min-height: 100vh;">

    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="login-card p-5">
            <div class="text-center mb-4">
                <h2 class="text-white font-serif mb-2">Verify it's you</h2>
                <p class="text-white-50 small">Enter the code we sent to your email</p>
            </div>

            <form action="new_password.php" method="POST">
                <div class="mb-3">
                    <label for="otp" class="form-label text-white small">Enter Code</label>
                    <input type="text" name="otp" class="form-control" id="otp" placeholder="Enter 6-digit code"
                        required>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-login btn-lg fw-bold">Next</button>
                </div>
            </form>

            <div class="mt-4 text-center">
                <a href="forgot_password.php" class="text-info small text-decoration-none">Back</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>