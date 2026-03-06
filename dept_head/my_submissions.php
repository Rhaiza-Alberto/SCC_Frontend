 <?php
/**
 * dept_head/my_submissions.php
 * Shows all syllabus submissions made by the dept head themselves.
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
$role_display = 'Dept Head Panel';

if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: my_submissions.php');
    exit();
}

$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

$all_submissions = get_faculty_submissions($user_id);
$pending         = array_values(array_filter($all_submissions, fn($s) => $s['status'] === 'Pending'));
$approved        = array_values(array_filter($all_submissions, fn($s) => $s['status'] === 'Approved'));
$rejected        = array_values(array_filter($all_submissions, fn($s) => $s['status'] === 'Rejected'));

$unread_count  = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submissions - SCC-CCS Syllabus Portal</title>
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
            <p class="text-white-50 small fw-bold mb-0" style="font-size:.75rem;"><?= htmlspecialchars($username) ?></p>
        </div>
        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
        <a href="dept_dashboard.php"        class="nav-link text-white p-3 rounded hover-effect">Dashboard</a>
        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
        <a href="syllabus_review.php"       class="nav-link text-white p-3 rounded hover-effect">Syllabus Review</a>
        <a href="upload_syllabus.php"       class="nav-link text-white p-3 rounded hover-effect">Upload Syllabus</a>
        <a href="my_submissions.php"        class="nav-link text-white active-nav-link p-3 rounded">My Submissions</a>
        <a href="shared_syllabus.php"       class="nav-link text-white p-3 rounded hover-effect">Shared Syllabus</a>
        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">ACCOUNT MANAGEMENT</div>
        <a href="registration_requests.php" class="nav-link text-white p-3 rounded hover-effect">Registration Requests</a>
        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
        <a href="profile.php"               class="nav-link text-white p-3 rounded hover-effect">Profile</a>
        <a href="../logout.php"             class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 p-5" style="margin-left:260px;">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <h3 class="text-orange font-serif fw-bold mb-0">My Syllabus Submissions</h3>
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
                            <span class="text-muted" style="font-size:.7rem;"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></span>
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

        <div class="card premium-card p-4 mb-5 shadow-sm">
            <ul class="nav nav-tabs nav-fill mb-4 border-bottom" id="submissionTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active font-serif fw-bold text-orange"
                            data-bs-toggle="tab" data-bs-target="#tabPending" type="button">
                        Pending
                        <?php if (count($pending) > 0): ?>
                            <span class="badge bg-warning text-dark ms-1"><?= count($pending) ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link font-serif fw-bold text-orange"
                            data-bs-toggle="tab" data-bs-target="#tabApproved" type="button">
                        Approved
                        <?php if (count($approved) > 0): ?>
                            <span class="badge bg-success ms-1"><?= count($approved) ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link font-serif fw-bold text-orange"
                            data-bs-toggle="tab" data-bs-target="#tabDeclined" type="button">
                        Declined
                        <?php if (count($rejected) > 0): ?>
                            <span class="badge bg-danger ms-1"><?= count($rejected) ?></span>
                        <?php endif; ?>
                    </button>
                </li>
            </ul>
            <div class="tab-content">

                <!-- Pending -->
                <div class="tab-pane fade show active" id="tabPending">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-premium">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-secondary small">#</th>
                                    <th class="text-secondary small">COURSE</th>
                                    <th class="text-secondary small d-none d-xl-table-cell">YEAR</th>
                                    <th class="text-secondary small">STATUS</th>
                                    <th class="text-secondary small text-center">FILE</th>
                                    <th class="text-secondary small">SUBMITTED</th>
                                    <th class="text-secondary small text-center">ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pending)): ?>
                                    <tr><td colspan="7" class="text-center text-muted py-4">No pending submissions.</td></tr>
                                <?php else: foreach ($pending as $i => $sub): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td>
                                            <span class="fw-bold small"><?= htmlspecialchars($sub['course_code']) ?></span><br>
                                            <span class="text-muted text-truncate d-block" style="font-size:.7rem;max-width:150px;"><?= htmlspecialchars($sub['course_title']) ?></span>
                                        </td>
                                        <td class="d-none d-xl-table-cell small"><?= htmlspecialchars($sub['school_year'] ?? '—') ?></td>
                                        <td><span class="badge bg-warning text-dark bg-opacity-25 border border-warning rounded-pill px-3" style="font-size:.75rem;">Pending</span></td>
                                        <td class="text-center">
                                            <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($sub['file_path'])) ?>"
                                               target="_blank" class="btn btn-sm btn-link text-orange p-0">
                                                <i class="bi bi-file-earmark-pdf fs-5"></i>
                                            </a>
                                        </td>
                                        <td class="small"><?= date('M d, Y', strtotime($sub['submitted_at'])) ?></td>
                                        <td class="text-center">
                                            <a href="edit_syllabus.php?id=<?= $sub['id'] ?>"
                                               class="btn btn-sm btn-outline-warning rounded-pill px-3">Edit</a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Approved -->
                <div class="tab-pane fade" id="tabApproved">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-premium">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-secondary small">#</th>
                                    <th class="text-secondary small">COURSE</th>
                                    <th class="text-secondary small">STATUS</th>
                                    <th class="text-secondary small">LAST REVIEWER</th>
                                    <th class="text-secondary small text-center">FILE</th>
                                    <th class="text-secondary small">SUBMITTED</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($approved)): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">No approved submissions yet.</td></tr>
                                <?php else: foreach ($approved as $i => $sub): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td>
                                            <span class="fw-bold small"><?= htmlspecialchars($sub['course_code']) ?></span><br>
                                            <span class="text-muted" style="font-size:.7rem;"><?= htmlspecialchars($sub['course_title']) ?></span>
                                        </td>
                                        <td><span class="badge bg-success bg-opacity-25 text-success border border-success rounded-pill px-3" style="font-size:.75rem;">Approved</span></td>
                                        <td class="small"><?= htmlspecialchars($sub['last_reviewer'] ?? '—') ?></td>
                                        <td class="text-center">
                                            <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($sub['file_path'])) ?>"
                                               target="_blank" class="btn btn-sm btn-link text-orange p-0">
                                                <i class="bi bi-file-earmark-pdf fs-5"></i>
                                            </a>
                                        </td>
                                        <td class="small"><?= date('M d, Y', strtotime($sub['submitted_at'])) ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Declined -->
                <div class="tab-pane fade" id="tabDeclined">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-premium">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-secondary small">#</th>
                                    <th class="text-secondary small">COURSE</th>
                                    <th class="text-secondary small">STATUS</th>
                                    <th class="text-secondary small">REASON</th>
                                    <th class="text-secondary small text-center">FILE</th>
                                    <th class="text-secondary small">SUBMITTED</th>
                                    <th class="text-secondary small text-center">ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rejected)): ?>
                                    <tr><td colspan="7" class="text-center text-muted py-4">No declined submissions.</td></tr>
                                <?php else: foreach ($rejected as $i => $sub): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td>
                                            <span class="fw-bold small"><?= htmlspecialchars($sub['course_code']) ?></span><br>
                                            <span class="text-muted" style="font-size:.7rem;"><?= htmlspecialchars($sub['course_title']) ?></span>
                                        </td>
                                        <td><span class="badge bg-danger bg-opacity-25 text-danger border border-danger rounded-pill px-3" style="font-size:.75rem;">Rejected</span></td>
                                        <td class="small"><?= htmlspecialchars($sub['reject_comment'] ?? '—') ?></td>
                                        <td class="text-center">
                                            <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($sub['file_path'])) ?>"
                                               target="_blank" class="btn btn-sm btn-link text-orange p-0">
                                                <i class="bi bi-file-earmark-pdf fs-5"></i>
                                            </a>
                                        </td>
                                        <td class="small"><?= date('M d, Y', strtotime($sub['submitted_at'])) ?></td>
                                        <td class="text-center">
                                            <a href="upload_syllabus.php?resubmit=<?= $sub['id'] ?>"
                                               class="btn btn-sm btn-outline-danger rounded-pill px-3">Resubmit</a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>