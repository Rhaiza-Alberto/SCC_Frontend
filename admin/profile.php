<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}
$username = $_SESSION['username'] ?? 'Admin User';
$email = $_SESSION['email'] ?? 'admin@gmail.com';
$role_display = "Dean's Panel";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - SCC-CCS Dean's Panel</title>
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
                <a href="add_user.php" class="nav-link text-white p-3 rounded hover-effect">
                    Add User
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
                <a href="profile.php" class="nav-link text-white p-3 rounded active-nav-link text-decoration-none">
                    Profile
                </a>
                <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">
                    Logout
                </a>
            </nav>
        </div>
        <div class="main-content flex-grow-1 p-5" style="margin-left: 260px;">
            <h2 class="text-orange font-serif fw-bold mb-4">Admin Profile</h2>
            <div class="card premium-card p-4 shadow-sm border-0 bg-white border-start border-orange border-4"
                style="max-width: 600px;">
                <div class="row g-4">
                    <div class="col-md-4 text-center">
                        <div class="bg-light rounded-circle p-4 mb-3 d-inline-block border">
                            <i class="bi bi-person fs-1 text-orange"></i>
                        </div>
                        <button class="btn btn-sm btn-outline-orange w-100 rounded-pill">Edit Photo</button>
                    </div>
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold mb-0">FULL NAME</label>
                            <p class="fw-bold fs-5 mb-0">
                                <?php echo htmlspecialchars($username); ?>
                            </p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold mb-0">EMAIL ADDRESS</label>
                            <p class="text-dark mb-0">
                                <?php echo htmlspecialchars($email); ?>
                            </p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold mb-0">ROLE</label>
                            <p class="badge bg-orange bg-opacity-10 text-orange rounded-pill mb-0">Administrator / Dean
                            </p>
                        </div>
                        <button class="btn btn-orange rounded-pill px-4 mt-3">Edit Profile Information</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>