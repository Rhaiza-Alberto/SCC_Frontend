<?php
/**
 * my_submissions.php
 * Shows all faculty syllabus submissions from the database.
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
$role_display = 'Faculty Panel';

// Handle mark-all-read
if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: my_submissions.php');
    exit();
}

$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

// Fetch from DB
$all = get_faculty_submissions($user_id);

$pending = array_values(array_filter($all, fn($s) => $s['status'] === 'Pending'));
$approved = array_values(array_filter($all, fn($s) => $s['status'] === 'Approved'));
$rejected = array_values(array_filter($all, fn($s) => $s['status'] === 'Rejected'));

$unread_count = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submissions - SCC-CCS Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
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
                <p class="text-white-50 small fw-bold mb-0" style="font-size:.75rem;">
                    <?= htmlspecialchars($username) ?>
                </p>
            </div>
            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
            <a href="faculty_dashboard.php" class="nav-link text-white p-3 rounded hover-effect">Dashboard</a>

            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
            <a href="upload_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">Upload Syllabus</a>
            <a href="my_submissions.php" class="nav-link text-white active-nav-link p-3 rounded">My Submissions</a>
            <a href="shared_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">Shared Syllabus</a>

            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
            <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect">Profile</a>
            <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-grow-1 p-5" style="margin-left:260px;">

            <div class="d-flex justify-content-between align-items-center mb-5">
                <h3 class="text-orange font-serif fw-bold mb-0">My Syllabus Submissions</h3>

                <!-- Notification Bell -->
                <div class="position-relative" style="cursor:pointer;" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell fs-4 text-secondary"></i>
                    <?php if ($unread_count > 0): ?>
                        <span class="notif-dot"></span>
                    <?php endif; ?>
                    <ul class="dropdown-menu dropdown-menu-end shadow"
                        style="width:320px;max-height:400px;overflow-y:auto;">
                        <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom">
                            <strong>Notifications</strong>
                            <?php if ($unread_count > 0): ?>
                                <a href="?mark_read=1" class="text-decoration-none small text-orange">Mark all read</a>
                            <?php endif; ?>
                        </li>
                        <?php if (empty($notifications)): ?>
                            <li class="px-3 py-3 text-center text-muted small">No notifications</li>
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

            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4">
                    <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Tabbed Submissions -->
            <div class="card premium-card p-4 mb-5 shadow-sm">
                <ul class="nav nav-tabs nav-fill mb-4 border-bottom" id="submissionTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active font-serif fw-bold text-orange" data-bs-toggle="tab"
                            data-bs-target="#tabPending" type="button">
                            Pending Approval
                            <?php if (count($pending) > 0): ?>
                                <span class="badge bg-warning text-dark ms-1"><?= count($pending) ?></span>
                            <?php endif; ?>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link font-serif fw-bold text-orange" data-bs-toggle="tab"
                            data-bs-target="#tabApproved" type="button">
                            Approved
                            <?php if (count($approved) > 0): ?>
                                <span class="badge bg-success ms-1"><?= count($approved) ?></span>
                            <?php endif; ?>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link font-serif fw-bold text-orange" data-bs-toggle="tab"
                            data-bs-target="#tabDeclined" type="button">
                            Declined
                            <?php if (count($rejected) > 0): ?>
                                <span class="badge bg-danger ms-1"><?= count($rejected) ?></span>
                            <?php endif; ?>
                        </button>
                    </li>
                </ul>

                <div class="tab-content">

                    <!-- ── Pending ── -->
                    <div class="tab-pane fade show active" id="tabPending">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-premium">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-secondary small">#</th>
                                        <th class="text-secondary small">COURSE</th>
                                        <th class="text-secondary small d-none d-xl-table-cell">LEVEL</th>
                                        <th class="text-secondary small d-none d-xl-table-cell">YEAR</th>
                                        <th class="text-secondary small">STATUS</th>
                                        <th class="text-secondary small text-center">FILE</th>
                                        <th class="text-secondary small">SUBMITTED</th>
                                        <th class="text-secondary small text-center">ACTION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($pending)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">No pending submissions.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($pending as $i => $sub): ?>
                                            <tr>
                                                <td><?= $i + 1 ?></td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span
                                                            class="fw-bold small"><?= htmlspecialchars($sub['course_code']) ?></span>
                                                        <span class="text-muted text-truncate"
                                                            style="font-size:.7rem;max-width:150px;">
                                                            <?= htmlspecialchars($sub['course_title']) ?>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="d-none d-xl-table-cell small">
                                                    <?= htmlspecialchars($sub['year_level'] ?? '—') ?>
                                                </td>
                                                <td class="d-none d-xl-table-cell small">
                                                    <?= htmlspecialchars($sub['school_year'] ?? '—') ?>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-warning text-dark bg-opacity-25 border border-warning rounded-pill px-3"
                                                        style="font-size:.75rem;">
                                                        <?= format_syllabus_status($sub['status'], $sub['current_stage_role'] ?? null, $sub['rejecting_role'] ?? null) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <a href="view_syllabus.php?file=<?= urlencode(basename($sub['file_path'])) ?>"
                                                        target="_blank" rel="noopener"
                                                        class="btn btn-sm btn-link text-orange p-0">
                                                        <i class="bi bi-file-earmark-pdf fs-5"></i>
                                                    </a>
                                                </td>
                                                <td class="small"><?= date('M d, Y', strtotime($sub['submitted_at'])) ?></td>
                                                <td class="text-center">
                                                    <a href="edit_syllabus.php?id=<?= $sub['id'] ?>"
                                                        class="btn btn-sm btn-outline-warning rounded-pill px-3">Edit</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ── Approved ── -->
                    <div class="tab-pane fade" id="tabApproved">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-premium">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-secondary small">#</th>
                                        <th class="text-secondary small">COURSE</th>
                                        <th class="text-secondary small d-none d-xl-table-cell">LEVEL</th>
                                        <th class="text-secondary small d-none d-xl-table-cell">YEAR</th>
                                        <th class="text-secondary small">STATUS</th>
                                        <th class="text-secondary small">LAST REVIEWER</th>
                                        <th class="text-secondary small text-center">FILE</th>
                                        <th class="text-secondary small">SUBMITTED</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($approved)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">No approved submissions yet.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($approved as $i => $sub): ?>
                                            <tr>
                                                <td><?= $i + 1 ?></td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span
                                                            class="fw-bold small"><?= htmlspecialchars($sub['course_code']) ?></span>
                                                        <span class="text-muted text-truncate"
                                                            style="font-size:.7rem;max-width:150px;">
                                                            <?= htmlspecialchars($sub['course_title']) ?>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="d-none d-xl-table-cell small">
                                                    <?= htmlspecialchars($sub['year_level'] ?? '—') ?>
                                                </td>
                                                <td class="d-none d-xl-table-cell small">
                                                    <?= htmlspecialchars($sub['school_year'] ?? '—') ?>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-success text-success bg-opacity-25 border border-success rounded-pill px-3"
                                                        style="font-size:.75rem;">
                                                        <?= format_syllabus_status($sub['status'], $sub['current_stage_role'] ?? null, $sub['rejecting_role'] ?? null) ?>
                                                    </span>
                                                </td>
                                                <td class="small"><?= htmlspecialchars($sub['last_reviewer'] ?? '—') ?></td>
                                                <td class="text-center">
                                                    <a href="view_syllabus.php?file=<?= urlencode(basename($sub['file_path'])) ?>"
                                                        target="_blank" rel="noopener"
                                                        class="btn btn-sm btn-link text-orange p-0">
                                                        <i class="bi bi-file-earmark-pdf fs-5"></i>
                                                    </a>
                                                </td>
                                                <td class="small"><?= date('M d, Y', strtotime($sub['submitted_at'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ── Declined ── -->
                    <div class="tab-pane fade" id="tabDeclined">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-premium">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-secondary small">#</th>
                                        <th class="text-secondary small">COURSE</th>
                                        <th class="text-secondary small d-none d-xl-table-cell">LEVEL</th>
                                        <th class="text-secondary small d-none d-xl-table-cell">YEAR</th>
                                        <th class="text-secondary small">STATUS</th>
                                        <th class="text-secondary small">REASON</th>
                                        <th class="text-secondary small text-center">FILE</th>
                                        <th class="text-secondary small">SUBMITTED</th>
                                        <th class="text-secondary small text-center">ACTION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($rejected)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">No declined submissions.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($rejected as $i => $sub): ?>
                                            <tr>
                                                <td><?= $i + 1 ?></td>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span
                                                            class="fw-bold small"><?= htmlspecialchars($sub['course_code']) ?></span>
                                                        <span class="text-muted text-truncate"
                                                            style="font-size:.7rem;max-width:150px;">
                                                            <?= htmlspecialchars($sub['course_title']) ?>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="d-none d-xl-table-cell small">
                                                    <?= htmlspecialchars($sub['year_level'] ?? '—') ?>
                                                </td>
                                                <td class="d-none d-xl-table-cell small">
                                                    <?= htmlspecialchars($sub['school_year'] ?? '—') ?>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-danger text-danger bg-opacity-25 border border-danger rounded-pill px-3"
                                                        style="font-size:.75rem;">
                                                        <?= format_syllabus_status($sub['status'], $sub['current_stage_role'] ?? null, $sub['rejecting_role'] ?? null) ?>
                                                    </span>
                                                </td>
                                                <td class="small"><?= htmlspecialchars($sub['reject_comment'] ?? '—') ?></td>
                                                <td class="text-center">
                                                    <a href="view_syllabus.php?file=<?= urlencode(basename($sub['file_path'])) ?>"
                                                        target="_blank" rel="noopener"
                                                        class="btn btn-sm btn-link text-orange p-0">
                                                        <i class="bi bi-file-earmark-pdf fs-5"></i>
                                                    </a>
                                                </td>
                                                <td class="small"><?= date('M d, Y', strtotime($sub['submitted_at'])) ?></td>
                                                <td class="text-center">
                                                    <a href="upload_syllabus.php?resubmit=<?= $sub['id'] ?>"
                                                        class="btn btn-sm btn-outline-danger rounded-pill px-3">Resubmit</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div><!-- /tab-content -->
            </div>
        </div><!-- /main-content -->
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle tab switching via URL hash
        document.addEventListener('DOMContentLoaded', function () {
            const hash = window.location.hash;
            if (hash) {
                const tabBtn = document.querySelector(`button[data-bs-target="${hash}"]`);
                if (tabBtn) {
                    new bootstrap.Tab(tabBtn).show();
                }
            }
        });

        // Update hash on tab click
        document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(btn => {
            btn.addEventListener('shown.bs.tab', function (e) {
                const target = e.target.getAttribute('data-bs-target');
                if (target) history.replaceState(null, null, target);
            });
        });
    </script>
</body>

</html>