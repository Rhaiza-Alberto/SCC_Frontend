<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Initialize session-based "users" if empty
if (!isset($_SESSION['users'])) {
    $_SESSION['users'] = [
        ['id' => 1, 'username' => 'Achy', 'email' => 'faculty@gmail.com', 'role' => 'faculty', 'dept' => 'CS'],
        ['id' => 2, 'username' => 'Dr. Jane Smith', 'email' => 'dept@gmail.com', 'role' => 'dept_head', 'dept' => 'CS'],
        ['id' => 3, 'username' => 'VPAA', 'email' => 'vpaa@gmail.com', 'role' => 'vpaa', 'dept' => 'Institutional'],
        ['id' => 4, 'username' => 'Admin User', 'email' => 'admin@gmail.com', 'role' => 'admin', 'dept' => 'CCS'],
    ];
}

$username = $_SESSION['username'] ?? 'Dean / Admin';
$role_display = "Dean's Panel";

// Handle Form Submission
$success = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_user = [
        'id' => count($_SESSION['users']) + 1,
        'username' => $_POST['fullname'],
        'email' => $_POST['email'],
        'role' => $_POST['role'],
        'dept' => $_POST['dept']
    ];
    $_SESSION['users'][] = $new_user;
    $success = "User created successfully!";
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User - SCC-CCS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .text-orange {
            color: #ff8800 !important;
        }

        .btn-orange {
            background-color: #ff8800 !important;
            color: white !important;
            border: none;
        }

        .btn-orange:hover {
            background-color: #e67a00 !important;
            color: white !important;
        }
    </style>
</head>

<body class="bg-light">
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar sidebar-premium text-white p-2 min-vh-100 d-flex flex-column"
            style="width: 260px; position: fixed; z-index: 1100;">
            <div class="text-center mb-3 mt-2">
                <img src="../css/logo.png" alt="CCS Logo" class="rounded-circle mb-2"
                    style="width: 80px; height: 80px; border: 2px solid rgba(255, 136, 0, 0.5); padding: 3px;">
                <h5 class="font-serif fw-bold text-orange mb-0"><?php echo $role_display; ?></h5>
                <p class="text-white-50 small fw-bold mb-0" style="font-size: 0.75rem;">
                    <?php echo htmlspecialchars($username); ?>
                </p>
            </div>
            <nav class="nav flex-column gap-2 mb-auto">
                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
                <a href="admin_dashboard.php" class="nav-link text-white p-3 rounded hover-effect">
                    Dashboard
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
                <a href="upload_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">
                    Upload Syllabus
                </a>
                <a href="my_submissions.php" class="nav-link text-white p-3 rounded hover-effect">
                    My Submissions
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">USER MANAGEMENT</div>
                <a href="manage_user.php" class="nav-link text-white p-3 rounded hover-effect">
                    Manage User
                </a>
                <a href="add_user.php" class="nav-link text-white p-3 rounded active-nav-link">
                    Add User
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
                <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect text-decoration-none">
                    Profile
                </a>
                <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">
                    Logout
                </a>
            </nav>
        </div>

        <div class="main-content flex-grow-1 p-5 justify-content-center align-items-center" style="margin-left: 260px;">
            <div class="mb-4">
                <h2 class="text-orange font-serif fw-bold">Add New User</h2>
                <p class="text-muted small">Enter details to register a new system member.</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?>
                    <a href="manage_user.php" class="ms-3 fw-bold text-success text-decoration-none">View User List</a>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-center">
                <div class="card premium-card p-4 shadow-sm border-0 bg-white" style="max-width: 700px; width: 100%;">
                    <form method="POST">
                        <div class="row g-4">
                            <div class="col-md-6 text-center border-end">
                                <div class="bg-light rounded-circle p-5 mb-3 d-inline-block border">
                                    <i class="bi bi-person-plus text-orange fs-1"></i>
                                </div>
                                <p class="text-muted small">Standard Registration Flow</p>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted">FULL NAME</label>
                                    <input type="text" class="form-control" name="fullname" placeholder="E.g. John Doe"
                                        required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted">GMAIL ADDRESS</label>
                                    <input type="email" class="form-control" name="email" placeholder="user@gmail.com"
                                        required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted">ASSIGNED ROLE</label>
                                    <select class="form-select" name="role" required>
                                        <option value="faculty">Instructor</option>
                                        <option value="dept_head">Department Head</option>
                                        <option value="vpaa">VPAA Hub</option>
                                        <option value="admin">Admin / Dean</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted">DEPARTMENT</label>
                                    <select class="form-select" name="dept" required>
                                        <option value="CS">Computer Science</option>
                                        <option value="IT">Information Technology</option>
                                        <option value="IS">Information Systems</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="text-center mt-4 pt-3 border-top">
                            <button type="reset" class="btn btn-light rounded-pill px-4 me-2">Clear</button>
                            <button type="submit" class="btn btn-orange rounded-pill px-5">Add User Profile</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>