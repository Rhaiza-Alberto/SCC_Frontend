<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration - SCC-CCS Syllabus Portal</title>
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

    <div class="container d-flex justify-content-center align-items-center min-vh-100 py-5">

        <div class="register-card p-5">
            <a href="login.php" class="btn btn-outline-light mb-3">
                ← Back
            </a>

            <h2 class="text-center text-orange font-serif mb-4">REGISTRATION FORM</h2>

            <form>
                <!-- Name Fields -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="firstName" class="form-label text-white small">First Name *</label>
                        <input type="text" class="form-control form-control-dark" id="firstName" placeholder="Name">
                    </div>
                    <div class="col-md-4">
                        <label for="middleName" class="form-label text-white small">Middle Name</label>
                        <input type="text" class="form-control form-control-dark" id="middleName"
                            placeholder="Middle Name">
                    </div>
                    <div class="col-md-4">
                        <label for="lastName" class="form-label text-white small">Last Name *</label>
                        <input type="text" class="form-control form-control-dark" id="lastName" placeholder="Last Name">
                    </div>
                </div>

                <!-- Birthdate and Sex -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="birthdate" class="form-label text-white small">Birthdate *</label>
                        <input type="date" class="form-control form-control-dark" id="birthdate" placeholder="dd/mm/yy">
                    </div>
                    <div class="col-md-6">
                        <label for="sex" class="form-label text-white small">Sex *</label>
                        <select class="form-select form-select-dark" id="sex">
                            <option selected disabled>--Select Sex--</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label text-white small">Email *</label>
                    <input type="email" class="form-control form-control-dark" id="email"
                        placeholder="Johndoe@gmail.com">
                </div>

                <!-- Password and Confirm Password -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="password" class="form-label text-white small">Password *</label>
                        <input type="password" class="form-control form-control-dark" id="password">
                    </div>
                    <div class="col-md-6">
                        <label for="confirmPassword" class="form-label text-white small">Confirm Password *</label>
                        <input type="password" class="form-control form-control-dark" id="confirmPassword">
                    </div>
                </div>

                <!-- College and Department -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="college" class="form-label text-white small">College</label>
                        <input type="text" class="form-control form-control-dark" id="college"
                            value="College of Computing Studies" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="department" class="form-label text-white small">Department</label>
                        <select class="form-select form-select-dark" id="department">
                            <option selected disabled>--Select Department--</option>
                            <option value="cs">Department of Computer Science</option>
                            <option value="it">Department of Information Technology</option>
                            <option value="is">Department of Information System</option>
                        </select>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-login btn-lg fw-bold">Register</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>