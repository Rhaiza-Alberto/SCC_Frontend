<?php
/**
 * faculty/shared_syllabus.php
 * Shared Syllabus is disabled. After VPAA approval, syllabi appear in My Submissions only.
 */
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

ensure_role_in_session();

$user_id      = $_SESSION['user_id'];
$username     = $_SESSION['username'] ?? 'User';
$role_display = 'Faculty Panel';

if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: shared_syllabus.php');
    exit();
}

$unread_count  = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared Syllabus - SCC-CCS Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .text-orange { color: #ff8800 !important; }
        .notif-dot { position:absolute;top:2px;right:2px;width:10px;height:10px;
                     background:#dc3545;border-radius:50%;border:2px solid #fff; }
    </style>
</head>
<body class="bg-light">
<div class="d-flex">

    <!-- Sidebar -->
    <div class="sidebar sidebar-premium text-white p-2 min-vh-100 d-flex flex-column"
         style="width:260px; position:fixed; z-index:1100;">
        <div class="text-center mb-3 mt-2">
            <img src="../css/logo.png" alt="CCS Logo" class="rounded-circle mb-2"
                 style="width:80px;height:80px;border:2px solid rgba(255,136,0,.5);padding:3px;">
            <h5 class="font-serif fw-bold text-orange mb-0"><?= $role_display ?></h5>
            <p class="text-white-50 small fw-bold mb-0" style="font-size:.75rem;">
                <?= htmlspecialchars($username) ?>
            </p>
        </div>
        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
        <a href="faculty_dashboard.php" class="nav-link text-white p-3 rounded hover-effect">Dashboard</a>

        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
        <a href="upload_syllabus.php"  class="nav-link text-white p-3 rounded hover-effect">Upload Syllabus</a>
        <a href="my_submissions.php"   class="nav-link text-white p-3 rounded hover-effect">My Submissions</a>
        <a href="shared_syllabus.php"  class="nav-link text-white active-nav-link p-3 rounded">Shared Syllabus</a>

        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
        <a href="profile.php"   class="nav-link text-white p-3 rounded hover-effect">Profile</a>
        <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 p-5" style="margin-left:260px;">

        <div class="d-flex justify-content-between align-items-center mb-5">
            <h2 class="text-orange font-serif fw-bold">Shared Syllabus Repository</h2>

            <!-- Notification Bell -->
            <div class="dropdown">
                <div class="position-relative" style="cursor:pointer;" data-bs-toggle="dropdown">
                    <i class="bi bi-bell fs-4 text-secondary"></i>
                    <?php if ($unread_count > 0): ?><span class="notif-dot"></span><?php endif; ?>
                </div>
                <ul class="dropdown-menu dropdown-menu-end shadow" style="width:320px;max-height:400px;overflow-y:auto;">
                    <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom">
                        <strong>Notifications</strong>
                        <?php if ($unread_count > 0): ?>
                            <a href="?mark_read=1" class="text-decoration-none small text-orange">Mark all read</a>
                        <?php endif; ?>
                    </li>
                    <?php if (empty($notifications)): ?>
                        <li class="px-3 py-3 text-center text-muted small">No notifications</li>
                    <?php else: foreach ($notifications as $n): ?>
                        <li class="px-3 py-2 border-bottom <?= !$n['is_read'] ? 'bg-light' : '' ?>">
                            <p class="mb-0 small"><?= htmlspecialchars($n['message']) ?></p>
                            <span class="text-muted" style="font-size:.7rem;">
                                <?= date('M d, Y h:i A', strtotime($n['created_at'])) ?>
                            </span>
                        </li>
                    <?php endforeach; endif; ?>
                </ul>
            </div>
        </div>

        <!-- Info notice -->
        <div class="card premium-card p-5 shadow-sm border-0 text-center">
            <i class="bi bi-folder2 text-orange fs-1 mb-3"></i>
            <h5 class="font-serif fw-bold text-orange mb-2">Repository Unavailable</h5>
            <p class="text-muted mb-4">
                Approved syllabi are no longer published to the shared repository.<br>
                You can view all of your submitted syllabi — including VPAA-approved ones — in
                <strong>My Submissions</strong>.
            </p>
            <a href="my_submissions.php" class="btn btn-orange rounded-pill px-5">
                <i class="bi bi-list-check me-2"></i>Go to My Submissions
            </a>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>