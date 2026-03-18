<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

ensure_role_in_session();

$user_id      = $_SESSION['user_id'];
$username     = $_SESSION['username'] ?? 'Dean / Admin';
$email        = $_SESSION['email']    ?? '';
$role_display = "Dean's Panel";
$dept_id      = $_SESSION['department_id'] ?? null;

// Handle mark-all-read
if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: admin_dashboard.php');
    exit();
}

$conn = get_db();

// ── Handle Syllabus Approve/Reject POST ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['syllabus_id'])) {
    $syllabus_id = (int) $_POST['syllabus_id'];
    $action      = $_POST['action'] === 'approve' ? 'Approved' : 'Rejected';
    $comment     = trim($_POST['comment'] ?? '') ?: null;
    process_syllabus_action($syllabus_id, $action, $comment);
    header('Location: admin_dashboard.php');
    exit();
}

// ── Syllabus Stats ───────────────────────────────────────────────────────────
// Pending syllabi waiting for dean review
$pending_review_count = (int) $conn->query("
    SELECT COUNT(DISTINCT sw.syllabus_id)
    FROM syllabus_workflow sw
    JOIN roles r ON sw.role_id = r.id
    WHERE r.role_name = 'dean' AND sw.action = 'Pending'
")->fetchColumn();

$total_count    = (int) $conn->query("SELECT COUNT(*) FROM syllabus")->fetchColumn();
$approved_count = (int) $conn->query("SELECT COUNT(*) FROM syllabus WHERE status = 'Approved'")->fetchColumn();
$pending_count  = (int) $conn->query("SELECT COUNT(*) FROM syllabus WHERE status = 'Pending'")->fetchColumn();
$rejected_count = (int) $conn->query("SELECT COUNT(*) FROM syllabus WHERE status = 'Rejected'")->fetchColumn();

// ── Registration Requests ────────────────────────────────────────────────────
$reg_stmt = $conn->prepare("
    SELECT COUNT(*) FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE r.role_name = 'faculty' AND u.is_approved = 0 AND u.is_deleted = 0
");
$reg_stmt->execute();
$reg_count = (int) $reg_stmt->fetchColumn();

// ── User Stats ───────────────────────────────────────────────────────────────
$total_users     = (int) $conn->query("SELECT COUNT(*) FROM users WHERE is_deleted = 0")->fetchColumn();
$instructor_count = (int) $conn->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id=r.id WHERE r.role_name='faculty' AND u.is_deleted=0")->fetchColumn();
$dean_count       = (int) $conn->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id=r.id WHERE r.role_name='dean' AND u.is_deleted=0")->fetchColumn();
$vpaa_count       = (int) $conn->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id=r.id WHERE r.role_name='vpaa' AND u.is_deleted=0")->fetchColumn();

// ── Pending Syllabi for Dean Review ─────────────────────────────────────────
$pending_syllabi_stmt = $conn->prepare("
    SELECT s.*,
           COALESCE(NULLIF(s.course_code,''),  c.course_code)  AS course_code,
           COALESCE(NULLIF(s.course_title,''), c.course_title) AS course_title,
           u.first_name, u.last_name, u.email AS uploader_email,
           sw.id AS workflow_id
    FROM syllabus_workflow sw
    JOIN syllabus s ON sw.syllabus_id = s.id
    JOIN users u    ON s.uploaded_by  = u.id
    LEFT JOIN courses c ON s.course_id = c.id
    JOIN roles r    ON sw.role_id     = r.id
    WHERE r.role_name = 'dean' AND sw.action = 'Pending'
    ORDER BY s.submitted_at DESC
");
$pending_syllabi_stmt->execute();
$pending_syllabi = $pending_syllabi_stmt->fetchAll(PDO::FETCH_ASSOC);

// ── My Submissions (dean can also upload) ────────────────────────────────────
$my_submissions = get_faculty_submissions($user_id);

// ── All Submissions ──────────────────────────────────────────────────────────
$all_stmt = $conn->prepare("
    SELECT s.*,
           COALESCE(NULLIF(s.course_code,''),  c.course_code)  AS course_code,
           COALESCE(NULLIF(s.course_title,''), c.course_title) AS course_title,
           u.first_name, u.last_name, u.email AS uploader_email,
           d.department_name
    FROM syllabus s
    LEFT JOIN courses c     ON s.course_id      = c.id
    LEFT JOIN users u       ON s.uploaded_by    = u.id
    LEFT JOIN departments d ON COALESCE(c.department_id, u.department_id) = d.id
    ORDER BY s.submitted_at DESC
");
$all_stmt->execute();
$all_submissions = $all_stmt->fetchAll(PDO::FETCH_ASSOC);

$unread_count  = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dean's Panel - SCC-CCS Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .text-orange  { color: #ff8800 !important; }
        .border-orange { border-color: #ff8800 !important; }
        .btn-orange   { background-color: #ff8800 !important; color: white !important; border: none; }
        .btn-orange:hover { background-color: #e67a00 !important; }
        .stat-card { transition: transform .3s ease, box-shadow .3s ease; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,.1) !important; }
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
        <nav class="nav flex-column gap-2 mb-auto">
            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
            <a href="admin_dashboard.php" class="nav-link text-white active-nav-link p-3 rounded">Dashboard</a>

            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
            <a href="syllabus_review.php" class="nav-link text-white p-3 rounded hover-effect">
                Syllabus Review
                <?php if ($pending_review_count > 0): ?>
                    <span class="badge bg-danger ms-1"><?= $pending_review_count ?></span>
                <?php endif; ?>
            </a>
            <a href="upload_syllabus.php"  class="nav-link text-white p-3 rounded hover-effect">Upload Syllabus</a>
            <a href="my_submissions.php"   class="nav-link text-white p-3 rounded hover-effect">My Submissions</a>
            <a href="shared_syllabus.php"  class="nav-link text-white p-3 rounded hover-effect">Shared Syllabus</a>

            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">USER MANAGEMENT</div>
            <a href="registration_requests.php" class="nav-link text-white p-3 rounded hover-effect">
                Registration Requests
                <?php if ($reg_count > 0): ?>
                    <span class="badge bg-danger ms-1"><?= $reg_count ?></span>
                <?php endif; ?>
            </a>
            <a href="manage_user.php"  class="nav-link text-white p-3 rounded hover-effect">Manage Users</a>
            <a href="add_user.php"     class="nav-link text-white p-3 rounded hover-effect">Add User</a>

            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
            <a href="profile.php"      class="nav-link text-white p-3 rounded hover-effect">Profile</a>
            <a href="../logout.php"    class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 p-5" style="margin-left:260px;">

        <div class="d-flex justify-content-between align-items-center mb-5">
            <h1 class="text-orange font-serif fw-bold mb-0 h2">Welcome, <?= htmlspecialchars($username) ?>!</h1>
            <div class="d-flex align-items-center gap-3">
                <!-- Notification Bell -->
                <div class="dropdown">
                    <div class="position-relative" style="cursor:pointer;" data-bs-toggle="dropdown">
                        <i class="bi bi-bell fs-4 text-dark"></i>
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
                            <li class="px-3 py-3 text-center text-muted small">No notifications yet</li>
                        <?php else: foreach ($notifications as $n): ?>
                            <li class="px-3 py-2 border-bottom <?= !$n['is_read'] ? 'bg-light' : '' ?>">
                                <p class="mb-0 small"><?= htmlspecialchars($n['message']) ?></p>
                                <span class="text-muted" style="font-size:.7rem;"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></span>
                            </li>
                        <?php endforeach; endif; ?>
                    </ul>
                </div>
                <button class="btn btn-sm btn-white border shadow-sm rounded-1 px-3 py-1 fw-bold text-dark d-flex align-items-center" onclick="window.print()">
                    <i class="bi bi-printer me-2"></i> PRINT
                </button>
            </div>
        </div>

        <!-- Syllabus Status Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card shadow-sm border-0 bg-white p-4 text-center border-start border-orange border-4">
                    <h6 class="text-uppercase fw-bold text-muted small mb-3">Total Submissions</h6>
                    <h1 class="display-4 fw-bold text-dark mb-0"><?= $total_count ?></h1>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm border-0 bg-white p-4 text-center border-start border-success border-4">
                    <h6 class="text-uppercase fw-bold text-success small mb-3">Approved</h6>
                    <h1 class="display-4 fw-bold text-success mb-0"><?= $approved_count ?></h1>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm border-0 bg-white p-4 text-center border-start border-warning border-4">
                    <h6 class="text-uppercase fw-bold text-warning small mb-3">Pending</h6>
                    <h1 class="display-4 fw-bold text-warning mb-0"><?= $pending_count ?></h1>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm border-0 bg-white p-4 text-center border-start border-danger border-4">
                    <h6 class="text-uppercase fw-bold text-danger small mb-3">Rejected</h6>
                    <h1 class="display-4 fw-bold text-danger mb-0"><?= $rejected_count ?></h1>
                </div>
            </div>
        </div>

        <!-- User Stats Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card stat-card shadow-sm border-0 bg-white p-4 text-center">
                    <h6 class="text-uppercase fw-bold text-muted small mb-2">Total Users</h6>
                    <h1 class="display-6 fw-bold text-dark mb-0"><?= $total_users ?></h1>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm border-0 bg-white p-4 text-center">
                    <h6 class="text-uppercase fw-bold text-muted small mb-2">Instructors</h6>
                    <h1 class="display-6 fw-bold text-dark mb-0"><?= $instructor_count ?></h1>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm border-0 bg-white p-4 text-center">
                    <h6 class="text-uppercase fw-bold text-muted small mb-2">Deans</h6>
                    <h1 class="display-6 fw-bold text-dark mb-0"><?= $dean_count ?></h1>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm border-0 bg-white p-4 text-center">
                    <h6 class="text-uppercase fw-bold text-muted small mb-2">VPAA</h6>
                    <h1 class="display-6 fw-bold text-dark mb-0"><?= $vpaa_count ?></h1>
                </div>
            </div>
        </div>

        <!-- Quick Access Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="card stat-card p-4 h-100 shadow-sm border-0 border-top border-warning border-4 rounded-4 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <i class="bi bi-file-earmark-check text-orange fs-2"></i>
                        <a href="syllabus_review.php" class="btn btn-sm btn-outline-warning rounded-pill px-3">View All</a>
                    </div>
                    <h5 class="card-title font-serif fw-bold mb-2">Syllabus Review</h5>
                    <p class="text-muted small mb-3">Review and approve faculty syllabus submissions.</p>
                    <span class="badge rounded-pill <?= $pending_review_count > 0 ? 'bg-warning text-dark' : 'bg-secondary opacity-75' ?> px-3 py-1">
                        <?= $pending_review_count ?> Pending
                    </span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card stat-card p-4 h-100 shadow-sm border-0 border-top border-orange border-4 rounded-4 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <i class="bi bi-person-check text-orange fs-2"></i>
                        <a href="registration_requests.php" class="btn btn-sm btn-outline-warning rounded-pill px-3">Manage</a>
                    </div>
                    <h5 class="card-title font-serif fw-bold mb-2">Registration Requests</h5>
                    <p class="text-muted small mb-3">Approve or reject new faculty registration requests.</p>
                    <span class="badge rounded-pill <?= $reg_count > 0 ? 'bg-warning text-dark' : 'bg-secondary opacity-75' ?> px-3 py-1">
                        <?= $reg_count ?> New
                    </span>
                </div>
            </div>
        </div>

        <!-- Pending Syllabi for Dean Review -->
        <div class="card premium-card p-4 mb-5 shadow-sm border-0">
            <h5 class="card-title font-serif fw-bold mb-4">Pending Syllabi for Dean Review</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr class="small text-muted text-uppercase border-bottom">
                            <th class="py-3">#</th>
                            <th class="py-3">Uploader</th>
                            <th class="py-3">Course</th>
                            <th class="py-3">Type</th>
                            <th class="py-3">Semester</th>
                            <th class="py-3">Status</th>
                            <th class="py-3 text-center">File</th>
                            <th class="py-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending_syllabi)): ?>
                            <tr><td colspan="8" class="text-center py-5 text-muted">No syllabi pending your review</td></tr>
                        <?php else: $c = 1; foreach ($pending_syllabi as $s): ?>
                            <tr class="border-bottom">
                                <td class="small"><?= $c++ ?></td>
                                <td>
                                    <div class="fw-bold small"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></div>
                                    <div class="text-muted" style="font-size:.7rem;"><?= htmlspecialchars($s['uploader_email']) ?></div>
                                </td>
                                <td>
                                    <div class="small fw-bold"><?= htmlspecialchars($s['course_code']) ?></div>
                                    <div class="text-muted" style="font-size:.7rem;"><?= htmlspecialchars($s['course_title']) ?></div>
                                </td>
                                <td class="small"><?= htmlspecialchars($s['subject_type'] ?? '—') ?></td>
                                <td class="small"><?= htmlspecialchars($s['semester'] ?? '—') ?></td>
                                <td><span class="text-warning fw-bold small">Pending</span></td>
                                <td class="text-center">
                                    <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($s['file_path'])) ?>"
                                       target="_blank" class="btn btn-sm btn-link text-orange p-0">
                                        <i class="bi bi-file-earmark-pdf fs-5"></i>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <button onclick="handleReview('approve', <?= $s['id'] ?>, '<?= htmlspecialchars($s['course_code']) ?>')"
                                                class="btn btn-sm btn-outline-success px-2 py-0 small rounded-1">Approve</button>
                                        <button onclick="handleReview('reject', <?= $s['id'] ?>, '<?= htmlspecialchars($s['course_code']) ?>')"
                                                class="btn btn-sm btn-outline-danger px-2 py-0 small rounded-1">Reject</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- My Submissions -->
        <div class="card premium-card p-4 mb-5 shadow-sm border-0">
            <h5 class="card-title font-serif fw-bold mb-4">My Submissions</h5>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr class="small text-muted text-uppercase border-bottom">
                            <th class="py-3">#</th>
                            <th class="py-3">Course Code</th>
                            <th class="py-3">Title</th>
                            <th class="py-3">Semester</th>
                            <th class="py-3">Status</th>
                            <th class="py-3">File</th>
                            <th class="py-3">Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($my_submissions)): ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted">No submissions found</td></tr>
                        <?php else: $c = 1; foreach ($my_submissions as $s): ?>
                            <tr class="border-bottom">
                                <td class="small"><?= $c++ ?></td>
                                <td class="fw-bold small"><?= htmlspecialchars($s['course_code']) ?></td>
                                <td class="small"><?= htmlspecialchars($s['course_title']) ?></td>
                                <td class="small"><?= htmlspecialchars($s['semester'] ?? '—') ?></td>
                                <td>
                                    <?php
                                    $badge = match($s['status']) {
                                        'Approved' => 'bg-success',
                                        'Rejected' => 'bg-danger',
                                        default    => 'bg-warning text-dark',
                                    };
                                    ?>
                                    <span class="badge <?= $badge ?> rounded-pill px-3"><?= $s['status'] ?></span>
                                </td>
                                <td><a href="#" class="text-orange text-decoration-none small fw-bold">Preview</a></td>
                                <td class="small text-muted"><?= date('M d, Y', strtotime($s['submitted_at'])) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- All Submissions -->
        <div class="card premium-card p-4 shadow-sm border-0">
            <h5 class="card-title font-serif fw-bold mb-4">All Submissions</h5>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr class="small text-muted text-uppercase border-bottom">
                            <th class="py-3">#</th>
                            <th class="py-3">Uploader</th>
                            <th class="py-3">Department</th>
                            <th class="py-3">Course</th>
                            <th class="py-3">Semester</th>
                            <th class="py-3">Status</th>
                            <th class="py-3">File</th>
                            <th class="py-3">Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_submissions)): ?>
                            <tr><td colspan="8" class="text-center py-5 text-muted">No submissions available</td></tr>
                        <?php else: $c = 1; foreach ($all_submissions as $s): ?>
                            <tr class="border-bottom bg-light bg-opacity-50">
                                <td class="small"><?= $c++ ?></td>
                                <td>
                                    <div class="fw-bold small"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></div>
                                    <div class="text-muted" style="font-size:.7rem;"><?= htmlspecialchars($s['uploader_email']) ?></div>
                                </td>
                                <td class="small"><?= htmlspecialchars($s['department_name'] ?? '—') ?></td>
                                <td>
                                    <div class="fw-bold small"><?= htmlspecialchars($s['course_code']) ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($s['course_title']) ?></div>
                                </td>
                                <td class="small"><?= htmlspecialchars($s['semester'] ?? '—') ?></td>
                                <td>
                                    <?php
                                    $badge = match($s['status']) {
                                        'Approved' => 'text-success',
                                        'Rejected' => 'text-danger',
                                        default    => 'text-warning',
                                    };
                                    ?>
                                    <span class="fw-bold small <?= $badge ?>"><?= $s['status'] ?></span>
                                </td>
                                <td><a href="#" class="text-orange text-decoration-none small fw-bold">Preview</a></td>
                                <td class="small text-muted"><?= date('M d, Y', strtotime($s['submitted_at'])) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Hidden POST form for SweetAlert -->
<form id="reviewForm" method="POST" action="admin_dashboard.php">
    <input type="hidden" name="syllabus_id" id="formSyllabusId">
    <input type="hidden" name="action"      id="formAction">
    <input type="hidden" name="comment"     id="formComment">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function handleReview(action, syllabusId, courseCode) {
    if (action === 'approve') {
        Swal.fire({
            title: 'Approve Syllabus?',
            html: `Approve <strong>${courseCode}</strong> and forward to VPAA?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Approve'
        }).then(result => {
            if (result.isConfirmed) {
                document.getElementById('formSyllabusId').value = syllabusId;
                document.getElementById('formAction').value = 'approve';
                document.getElementById('formComment').value = '';
                document.getElementById('reviewForm').submit();
            }
        });
    } else {
        Swal.fire({
            title: 'Reject Syllabus?',
            html: `Provide a reason for rejecting <strong>${courseCode}</strong>:`,
            input: 'textarea',
            inputPlaceholder: 'Enter rejection reason (optional)...',
            inputAttributes: { rows: 3 },
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Reject'
        }).then(result => {
            if (result.isConfirmed) {
                document.getElementById('formSyllabusId').value = syllabusId;
                document.getElementById('formAction').value = 'reject';
                document.getElementById('formComment').value = result.value || '';
                document.getElementById('reviewForm').submit();
            }
        });
    }
}
</script>
</body>
</html>