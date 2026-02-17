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

    <div class="container d-flex justify-content-center align-items-center min-vh-100">

        <div class="login-card text-center p-5">
            <a href="login.php" class="btn btn-outline-light text-left mb-3">
                ← Back
            </a>

            <h2 class="text-white font-serif mb-4">SCC- CCS Syllabus Portal</h2>

            <div class="mb-4">
                <img src="css/logo.png" alt="Logo" class="rounded-circle" style="width: 100px; height: 100px;">
            </div>

            <form>
                <div class="mb-3 text-start">
                    <label for="email" class="form-label text-white small">Email</label>
                    <input type="email" class="form-control" id="email" placeholder="Input">
                </div>
                <div class="mb-3 text-start">
                    <label for="password" class="form-label text-white small">Password</label>
                    <input type="password" class="form-control" id="password" placeholder="Input">
                </div>
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-login btn-lg fw-bold">Log in</button>
                </div>
            </form>

            <div class="mt-4">
                <p class="text-white small mb-1">Don't have an account? <a href="register.php"
                        class="text-info text-decoration-none">Sign Up Here</a></p>
                <a href="forgot_password.php" class="text-info small text-decoration-none">Forgot Password?</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>