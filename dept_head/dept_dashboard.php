<?php
/**
 * dept_dashboard.php
 * Department Head dashboard — all data from database.
 */
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

ensure_role_in_session();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$role_display = 'Dept Head Panel';
$dept_id = $_SESSION['department_id'] ?? null;

// Handle mark-all-read
if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: dept_dashboard.php');
    exit();
}

$conn = get_db();

// ── Stats: submissions for this dept head's department (workflow step pending for dept_head role) ──
// Count syllabi where the dept_head workflow step is Pending (awaiting this dept head's review)
$pending_review_count = 0;
if ($dept_id) {
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT sw.syllabus_id)
        FROM syllabus_workflow sw
        JOIN syllabus s ON sw.syllabus_id = s.id
        JOIN users u ON s.uploaded_by = u.id
        JOIN roles r ON sw.role_id = r.id
        WHERE r.role_name = 'department_head'
          AND sw.action = 'Pending'
          AND u.department_id = ?
    ");
    $stmt->execute([$dept_id]);
    $pending_review_count = (int) $stmt->fetchColumn();
}

// My own submissions as dept_head (they can also upload syllabi)
$my_submissions = get_faculty_submissions($user_id);
$my_total = count($my_submissions);
$my_approved = count(array_filter($my_submissions, fn($s) => $s['status'] === 'Approved'));
$my_pending = count(array_filter($my_submissions, fn($s) => $s['status'] === 'Pending'));
$my_rejected = count(array_filter($my_submissions, fn($s) => $s['status'] === 'Rejected'));

// Pending registration requests for this department
$reg_count = 0;
if ($dept_id) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE r.role_name = 'faculty'
          AND u.is_approved = 0
          AND u.is_deleted  = 0
          AND u.department_id = ?
    ");
    $stmt->execute([$dept_id]);
    $reg_count = (int) $stmt->fetchColumn();
}

// Approved syllabi count in department repository
$approved_repo_count = 0;
if ($dept_id) {
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT s.id)
        FROM syllabus s
        JOIN users u ON s.uploaded_by = u.id
        WHERE s.status = 'Approved'
          AND u.department_id = ?
    ");
    $stmt->execute([$dept_id]);
    $approved_repo_count = (int) $stmt->fetchColumn();
}

// Active instructors in department
$active_instructors = 0;
if ($dept_id) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE r.role_name = 'faculty'
          AND u.is_approved = 1
          AND u.is_deleted  = 0
          AND u.department_id = ?
    ");
    $stmt->execute([$dept_id]);
    $active_instructors = (int) $stmt->fetchColumn();
}

// Notifications
$unread_count = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Head Dashboard - SCC-CCS Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .text-orange {
            color: #ff8800 !important;
        }

        .btn-orange {
            background-color: #ff8800 !important;
            color: #fff !important;
            border: none;
        }

        .btn-orange:hover {
            background-color: #e67a00 !important;
        }

        .stat-card {
            transition: transform .3s ease, box-shadow .3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, .1) !important;
        }

        .notif-dot {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 10px;
            height: 10px;
            background: #dc3545;
            border-radius: 50%;
            border: 2px solid #fff;
        }
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
                <p class="text-white-50 small fw-bold mb-0" style="font-size:.75rem;"><?= htmlspecialchars($username) ?>
                </p>
            </div>
            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
            <a href="dept_dashboard.php" class="nav-link text-white active-nav-link p-3 rounded">Dashboard</a>
            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
            <a href="syllabus_review.php" class="nav-link text-white p-3 rounded hover-effect">Syllabus Review
                <?php if ($pending_review_count > 0): ?>
                    <span class="badge bg-danger ms-1"><?= $pending_review_count ?></span>
                <?php endif; ?>
            </a>
            <a href="upload_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">Upload Syllabus</a>
            <a href="my_submissions.php" class="nav-link text-white p-3 rounded hover-effect">My Submissions</a>
            <a href="shared_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">Shared Syllabus</a>
            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">ACCOUNT MANAGEMENT</div>
            <a href="registration_requests.php" class="nav-link text-white p-3 rounded hover-effect">Registration
                Requests
                <?php if ($reg_count > 0): ?>
                    <span class="badge bg-danger ms-1"><?= $reg_count ?></span>
                <?php endif; ?>
            </a>
            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
            <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect">Profile</a>
            <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-grow-1 p-5" style="margin-left:260px;">

            <div class="d-flex justify-content-between align-items-center mb-5">
                <h3 class="text-orange font-serif fw-bold mb-0">Welcome, <?= htmlspecialchars($username) ?>!</h3>

                <!-- Notification Bell -->
                <div class="dropdown">
                    <div class="position-relative" style="cursor:pointer;" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <i class="bi bi-bell fs-4 text-secondary"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="notif-dot"></span>
                        <?php endif; ?>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow"
                        style="width:320px;max-height:400px;overflow-y:auto;">
                        <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom">
                            <strong>Notifications</strong>
                            <?php if ($unread_count > 0): ?>
                                <a href="?mark_read=1" class="text-decoration-none small text-orange">Mark all read</a>
                            <?php endif; ?>
                        </li>
                        <?php if (empty($notifications)): ?>
                            <li class="px-3 py-3 text-center text-muted small">No notifications yet</li>
                        <?php else:
                            foreach ($notifications as $n): ?>
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

            <!-- Stat Cards (my own submissions) -->
            <div class="row g-4 mb-5">
                <?php
                $stats = [
                    ['label' => 'My Submissions', 'value' => $my_total, 'color' => '#ff8800', 'icon' => 'bi-files'],
                    ['label' => 'Approved', 'value' => $my_approved, 'color' => '#28a745', 'icon' => 'bi-check-circle'],
                    ['label' => 'Pending', 'value' => $my_pending, 'color' => '#ffc107', 'icon' => 'bi-clock-history'],
                    ['label' => 'Rejected', 'value' => $my_rejected, 'color' => '#dc3545', 'icon' => 'bi-x-circle'],
                ];
                foreach ($stats as $s): ?>
                    <div class="col-md-3 col-sm-6">
                        <div class="card stat-card shadow-sm border-0 bg-white"
                            style="border-left:5px solid <?= $s['color'] ?> !important;">
                            <div class="p-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="text-uppercase fw-bold text-muted small mb-0"><?= $s['label'] ?></h6>
                                    <i class="bi <?= $s['icon'] ?> opacity-50 fs-4" style="color:<?= $s['color'] ?>"></i>
                                </div>
                                <h1 class="display-5 fw-bold text-dark mb-0"><?= $s['value'] ?></h1>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pipeline + Activity -->
            <div class="row g-4 mb-5">
                <div class="col-lg-8">
                    <div class="card premium-card p-4 shadow-sm h-100 border-0 rounded-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title font-serif fw-bold mb-0 text-orange">Syllabus Review Pipeline</h5>
                            <span
                                class="badge bg-warning bg-opacity-10 text-warning border border-warning rounded-pill px-3 py-1 small">
                                <?= get_current_school_year() ?>
                            </span>
                        </div>
                        <div class="row g-4 mb-4">
                            <div class="col-4">
                                <div class="p-3 rounded-3 bg-light text-center border-bottom border-warning border-4">
                                    <h3 class="mb-1 fw-bold text-dark"><?= $my_total ?></h3>
                                    <span class="text-muted small fw-bold text-uppercase">Submitted</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-3 rounded-3 bg-light text-center border-bottom border-primary border-4">
                                    <h3 class="mb-1 fw-bold text-dark"><?= $pending_review_count ?></h3>
                                    <span class="text-muted small fw-bold text-uppercase">Awaiting Review</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-3 rounded-3 bg-light text-center border-bottom border-success border-4">
                                    <h3 class="mb-1 fw-bold text-dark"><?= $my_approved ?></h3>
                                    <span class="text-muted small fw-bold text-uppercase">Approved</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-auto text-center py-2">
                            <a href="syllabus_review.php" class="btn btn-orange rounded-pill px-5 shadow-sm">Go to
                                Review Queue</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card premium-card p-4 shadow-sm h-100 border-0 rounded-4">
                        <h5 class="card-title font-serif fw-bold mb-4 text-orange">Notifications</h5>
                        <?php if (empty($notifications)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-bell-slash text-muted fs-2 opacity-25"></i>
                                <p class="text-muted small mt-2">No new notifications</p>
                            </div>
                        <?php else:
                            foreach ($notifications as $n): ?>
                                <div
                                    class="d-flex align-items-start mb-3 pb-2 border-bottom <?= !$n['is_read'] ? 'fw-semibold' : '' ?>">
                                    <i class="bi bi-bell-fill text-orange me-2 mt-1" style="font-size:.85rem;"></i>
                                    <div>
                                        <p class="mb-0 small"><?= htmlspecialchars($n['message']) ?></p>
                                        <span class="text-muted" style="font-size:.7rem;">
                                            <?= date('M d, Y', strtotime($n['created_at'])) ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        <?php if (!empty($notifications)): ?>
                            <a href="?mark_read=1" class="btn btn-sm btn-outline-secondary w-100 mt-2">Mark all read</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Access Cards -->
            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <div
                        class="card premium-card p-4 h-100 shadow-sm border-0 border-top border-warning border-4 rounded-4 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <i class="bi bi-file-earmark-check text-orange fs-2"></i>
                            <a href="syllabus_review.php" class="btn btn-sm btn-outline-warning rounded-pill px-3">View
                                All</a>
                        </div>
                        <h5 class="card-title font-serif fw-bold mb-2">Syllabus Review</h5>
                        <p class="text-muted small mb-4">Manage faculty syllabus submissions awaiting your approval.</p>
                        <div class="d-flex align-items-center mt-auto">
                            <span
                                class="badge rounded-pill me-2 <?= $pending_review_count > 0 ? 'bg-warning text-dark' : 'bg-secondary opacity-75' ?> px-2 py-1"
                                style="font-size:.75rem;">
                                <?= $pending_review_count ?> Pending
                            </span>
                            <span
                                class="text-muted small"><?= $pending_review_count > 0 ? 'awaiting your review' : 'no pending review' ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div
                        class="card premium-card p-4 h-100 shadow-sm border-0 border-top border-orange border-4 rounded-4 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <i class="bi bi-person-check text-orange fs-2"></i>
                            <a href="registration_requests.php"
                                class="btn btn-sm btn-outline-warning rounded-pill px-3">Manage</a>
                        </div>
                        <h5 class="card-title font-serif fw-bold mb-2">Registration Requests</h5>
                        <p class="text-muted small mb-4">Review and approve new faculty registration requests.</p>
                        <div class="d-flex align-items-center mt-auto">
                            <span
                                class="badge rounded-pill me-2 <?= $reg_count > 0 ? 'bg-warning text-dark' : 'bg-secondary opacity-75' ?> px-2 py-1"
                                style="font-size:.75rem;">
                                <?= $reg_count ?> New
                            </span>
                            <span
                                class="text-muted small"><?= $reg_count > 0 ? 'pending approval' : 'no pending approval' ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Department Repository Overview -->
            <div class="row g-4 mb-5">
                <div class="col-12">
                    <div class="card premium-card p-4 shadow-sm bg-light border-0 rounded-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title font-serif fw-bold mb-0 text-orange">Department Repository Overview
                            </h5>
                            <a href="shared_syllabus.php"
                                class="btn btn-sm btn-orange rounded-pill px-4 shadow-sm">Explore Repository</a>
                        </div>
                        <div class="row g-4 align-items-center">
                            <div class="col-md-3 text-center">
                                <div class="display-6 fw-bold text-orange mb-0"><?= $approved_repo_count ?></div>
                                <span class="text-muted small">Approved Files</span>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="display-6 fw-bold text-orange mb-0"><?= $active_instructors ?></div>
                                <span class="text-muted small">Active Instructors</span>
                            </div>
                            <div class="col-md-6 ps-md-4">
                                <p class="text-muted small mb-0">Your department repository is the single source of
                                    truth for all validated academic content. It ensures consistency across all subject
                                    offerings and facilitates peer review and quality management.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>