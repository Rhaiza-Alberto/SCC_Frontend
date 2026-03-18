<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$user_id      = $_SESSION['user_id'];
$username     = $_SESSION['username'] ?? 'Admin User';
$role_display = "Dean's Panel";   // ← was missing semicolon

$unread_count  = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Syllabus - SCC-CCS Dean's Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .text-orange { color: #ff8800 !important; }
        .btn-orange { background-color: #ff8800 !important; color: white !important; border: none; }
        .btn-orange:hover { background-color: #e67a00 !important; color: white !important; }
        .notif-dot { position:absolute;top:2px;right:2px;width:10px;height:10px; background:#dc3545;border-radius:50%;border:2px solid #fff; }
    </style>
</head>
<body class="bg-light">
<div class="d-flex">
    <div class="sidebar sidebar-premium text-white p-2 min-vh-100 d-flex flex-column" style="width:260px; position:fixed; z-index:1100;">
        <div class="text-center mb-3 mt-2">
            <img src="../css/logo.png" alt="CCS Logo" class="rounded-circle mb-2" style="width:80px;height:80px;border:2px solid rgba(255,136,0,.5);padding:3px;">
            <h5 class="font-serif fw-bold text-orange mb-0"><?= $role_display ?></h5>
            <p class="text-white-50 small fw-bold mb-0" style="font-size:.75rem;"><?= htmlspecialchars($username) ?></p>
        </div>
        <nav class="nav flex-column gap-2 mb-auto">
            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
            <a href="admin_dashboard.php" class="nav-link text-white p-3 rounded hover-effect">Dashboard</a>

            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
            <a href="syllabus_review.php" class="nav-link text-white p-3 rounded hover-effect">Syllabus Review</a>
            <a href="upload_syllabus.php" class="nav-link text-white active-nav-link p-3 rounded">Upload Syllabus</a>
            <a href="my_submissions.php" class="nav-link text-white p-3 rounded hover-effect">My Submissions</a>
            <a href="shared_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">Shared Syllabus</a>

            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">USER MANAGEMENT</div>
            <a href="registration_requests.php" class="nav-link text-white p-3 rounded hover-effect">Registration Requests</a>
            <a href="manage_user.php" class="nav-link text-white p-3 rounded hover-effect">Manage Users</a>
            <a href="add_user.php" class="nav-link text-white p-3 rounded hover-effect">Add User</a>

            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
            <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect">Profile</a>
            <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
        </nav>
    </div>

    <div class="main-content flex-grow-1 p-5" style="margin-left:260px;">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <h3 class="text-orange font-serif fw-bold mb-0">Upload Syllabus</h3>
            <div class="dropdown">
                <div class="position-relative" style="cursor:pointer;" data-bs-toggle="dropdown">
                    <i class="bi bi-bell fs-4 text-secondary"></i>
                    <?php if ($unread_count > 0): ?><span class="notif-dot"></span><?php endif; ?>
                </div>
                <ul class="dropdown-menu dropdown-menu-end shadow" style="width:320px;">
                    <li class="px-3 py-2 border-bottom"><strong>Notifications</strong></li>
                    <?php if (empty($notifications)): ?>
                        <li class="px-3 py-3 text-center text-muted small">No notifications yet</li>
                    <?php else: foreach ($notifications as $n): ?>
                        <li class="px-3 py-2 border-bottom <?= !$n['is_read'] ? 'bg-light' : '' ?>">
                            <p class="mb-0 small"><?= htmlspecialchars($n['message']) ?></p>
                        </li>
                    <?php endforeach; endif; ?>
                </ul>
            </div>
        </div>

        <div class="card premium-card shadow-sm p-5 bg-white mx-auto" style="max-width:800px;">
            <form action="../faculty/process_upload.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label fw-bold small">Course Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="course_code" placeholder="E.G., CS101" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Course Title / Subject Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="course_title" placeholder="E.G., Computer Programming 1" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Course / Department</label>
                    <input type="text" class="form-control" name="course" placeholder="E.G., Computer Science">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Subject Type <span class="text-danger">*</span></label>
                    <select class="form-select" name="subject_type" required>
                        <option selected disabled>-- Select Subject Type --</option>
                        <option value="Institutional Subject">Institutional Subject</option>
                        <option value="General Education (GE)">General Education (GE)</option>
                        <option value="Core Subject">Core Subject</option>
                        <option value="Professional Subjects">Professional Subjects</option>
                        <option value="Mandatory / Elect Subject">Mandatory / Elect Subject</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Subject Semester <span class="text-danger">*</span></label>
                    <select class="form-select" name="subject_semester" required>
                        <option selected disabled>-- Select Semester --</option>
                        <option value="1st Semester">1st Semester</option>
                        <option value="2nd Semester">2nd Semester</option>
                        <option value="Summer">Summer</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold small">Upload File (PDF Only) <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" name="pdf_file" accept=".pdf" required>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-orange btn-lg fw-bold rounded-pill">Upload Syllabus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>