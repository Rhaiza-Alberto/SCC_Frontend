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
    }
    exit();
}

// Load departments from DB (College of Computing Studies only)
$departments = [];
try {
    $conn = get_db();
    $stmt = $conn->prepare("
        SELECT d.id, d.department_name 
        FROM departments d
        JOIN colleges c ON d.college_id = c.id
        WHERE c.college_name = 'College of Computing Studies'
        ORDER BY d.department_name ASC
    ");
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Register page DB error: " . $e->getMessage());
}
?>
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

    <div class="container pt-3">
        <a href="login.php" class="btn btn-outline-dark position-absolute top-0 start-0 m-3">
            &larr; Back
        </a>
    </div>

    <div class="container d-flex justify-content-center align-items-center min-vh-100 py-5">

        <?php if (isset($_GET['success']) && $_GET['success'] === 'true'): ?>
            <!-- Success Card -->
            <div class="card p-5 shadow-lg rounded-4 text-center text-white"
                style="background-color: #000; max-width: 500px;">
                <div class="mb-4">
                    <div class="display-1 text-success mb-3">✅</div>
                    <h2 class="font-serif fw-bold text-orange">Registration Submitted!</h2>
                </div>

                <p class="lead mb-4">Your account has been successfully created and is now pending approval.</p>

                <div class="bg-dark bg-opacity-50 p-4 rounded-3 text-start mb-4 border border-secondary">
                    <h6 class="text-warning fw-bold mb-2">⏳ Important:</h6>
                    <p class="small mb-2">Your registration will be reviewed by the Department Head.</p>
                    <p class="small mb-0">You will receive an email once your account is approved.</p>
                </div>

                <p class="text-muted small mb-4">Please do not attempt to register again.</p>

                <div class="d-grid">
                    <a href="login.php" class="btn btn-login btn-lg fw-bold">Back to Login</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Registration Form -->
            <div class="register-card p-5">

                <h2 class="text-center text-orange font-serif mb-4">REGISTRATION FORM</h2>

                <?php
                if (isset($_SESSION['register_error'])) {
                    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' .
                        htmlspecialchars($_SESSION['register_error']) .
                        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                    unset($_SESSION['register_error']);
                }
                ?>

                <form method="POST" action="process_register.php">
                    <!-- Name Fields -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="firstName" class="form-label text-white small">First Name *</label>
                            <input type="text" name="firstName" class="form-control form-control-dark" id="firstName"
                                placeholder="Name" required>
                        </div>
                        <div class="col-md-4">
                            <label for="middleName" class="form-label text-white small">Middle Name</label>
                            <input type="text" name="middleName" class="form-control form-control-dark" id="middleName"
                                placeholder="Middle Name">
                        </div>
                        <div class="col-md-4">
                            <label for="lastName" class="form-label text-white small">Last Name *</label>
                            <input type="text" name="lastName" class="form-control form-control-dark" id="lastName"
                                placeholder="Last Name" required>
                        </div>
                    </div>

                    <!-- Birthdate and Sex -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="birthdate" class="form-label text-white small">Birthdate *</label>
                            <input type="date" name="birthdate" class="form-control form-control-dark" id="birthdate"
                                placeholder="dd/mm/yy" required>
                        </div>
                        <div class="col-md-6">
                            <label for="sex" class="form-label text-white small">Sex *</label>
                            <select name="sex" class="form-select form-select-dark" id="sex" required>
                                <option selected disabled value="">--Select Sex--</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label text-white small">Email *</label>
                        <input type="email" name="email" class="form-control form-control-dark" id="email"
                            placeholder="Johndoe@gmail.com" required pattern=".*@gmail\.com"
                            title="Please use a @gmail.com address">
                        <div class="form-text text-white-50" style="font-size: 0.75rem;">Only @gmail.com addresses are
                            accepted.</div>
                    </div>

                    <!-- Password and Confirm Password -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label text-white small">Password *</label>
                            <input type="password" name="password" class="form-control form-control-dark" id="password"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label for="confirmPassword" class="form-label text-white small">Confirm Password *</label>
                            <input type="password" name="confirmPassword" class="form-control form-control-dark"
                                id="confirmPassword" required>
                        </div>
                    </div>

                    <!-- College and Department -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="college" class="form-label text-white small">College</label>
                            <input type="text" name="college" class="form-control form-control-dark" id="college"
                                value="College of Computing Studies" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="department" class="form-label text-white small">Department</label>
                            <select name="department" class="form-select form-select-dark" id="department" required>
                                <option selected disabled value="">--Select Department--</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>">
                                        <?php echo htmlspecialchars($dept['department_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-login btn-lg fw-bold">Register</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>