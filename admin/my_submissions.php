<?php
session_start();
require_once 'database.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}
$username = $_SESSION['username'] ?? 'Admin User';
$email = $_SESSION['email'] ?? 'admin@gmail.com';
$role_display = "Dean's Panel";

$submissions = $_SESSION['submissions'] ?? [];
$my_submissions = array_filter($submissions, fn($s) => $s['uploader_email'] == $email);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submissions - SCC-CCS Dean's Panel</title>
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
                <a href="my_submissions.php" class="nav-link text-white p-3 rounded active-nav-link">
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
                <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect text-decoration-none">
                    Profile
                </a>
                <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">
                    Logout
                </a>
            </nav>
        </div>
        <div class="main-content flex-grow-1 p-5" style="margin-left: 260px;">
            <h2 class="text-orange font-serif fw-bold mb-4">My Submissions</h2>
            <div class="card premium-card p-4 shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>Course</th>
                                <th>Status</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($my_submissions)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4">No submissions from you yet.</td>
                                </tr>
                            <?php else:
                                $c = 1;
                                foreach ($my_submissions as $s): ?>
                                    <tr>
                                        <td>
                                            <?php echo $c++; ?>
                                        </td>
                                        <td><strong>
                                                <?php echo $s['course_code']; ?>
                                            </strong><br><small>
                                                <?php echo $s['course_title']; ?>
                                            </small></td>
                                        <td><span class="badge bg-warning text-dark rounded-pill">
                                                <?php echo $s['status']; ?>
                                            </span></td>
                                        <td>
                                            <?php echo $s['submitted_on']; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>

</html>