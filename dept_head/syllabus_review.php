 <?php
/**
 * syllabus_review.php
 * Department Head — review syllabi submitted to their department.
 * Approve/Reject triggers the workflow engine.
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
$dept_id      = $_SESSION['department_id'] ?? null;

// Handle mark-all-read
if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: syllabus_review.php');
    exit();
}

// ── Handle Approve / Reject POST ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['syllabus_id'])) {
    $syllabus_id = (int) $_POST['syllabus_id'];
    $action      = $_POST['action'] === 'approve' ? 'Approved' : 'Rejected';
    $comment     = trim($_POST['comment'] ?? '') ?: null;

    process_syllabus_action($syllabus_id, $action, $comment);

    $_SESSION['review_success'] = $action === 'Approved'
        ? 'Syllabus approved and forwarded to the Dean.'
        : 'Syllabus rejected. The faculty member has been notified.';

    header('Location: syllabus_review.php');
    exit();
}

$success_msg = $_SESSION['review_success'] ?? '';
unset($_SESSION['review_success']);

$conn = get_db();

// ── Fetch syllabi pending dept_head approval for this department ──────────────
// We look for syllabus_workflow rows where role = department_head, action = Pending
// and the syllabus uploader belongs to this dept_head's department
$pending_rows = [];
$approved_rows = [];
$rejected_rows = [];

if ($dept_id) {
    // Pending: workflow step for dept_head is still Pending
    $stmt = $conn->prepare("
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
        WHERE r.role_name   = 'department_head'
          AND sw.action     = 'Pending'
          AND u.department_id = ?
        ORDER BY s.submitted_at DESC
    ");
    $stmt->execute([$dept_id]);
    $pending_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Approved by dept_head (or fully approved)
    $stmt = $conn->prepare("
        SELECT s.*,
               COALESCE(NULLIF(s.course_code,''),  c.course_code)  AS course_code,
               COALESCE(NULLIF(s.course_title,''), c.course_title) AS course_title,
               u.first_name, u.last_name,
               sw.action_at AS reviewed_at, sw.comment
        FROM syllabus_workflow sw
        JOIN syllabus s ON sw.syllabus_id = s.id
        JOIN users u    ON s.uploaded_by  = u.id
        LEFT JOIN courses c ON s.course_id = c.id
        JOIN roles r    ON sw.role_id     = r.id
        WHERE r.role_name   = 'department_head'
          AND sw.action     = 'Approved'
          AND u.department_id = ?
        ORDER BY sw.action_at DESC
    ");
    $stmt->execute([$dept_id]);
    $approved_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Rejected by dept_head
    $stmt = $conn->prepare("
        SELECT s.*,
               COALESCE(NULLIF(s.course_code,''),  c.course_code)  AS course_code,
               COALESCE(NULLIF(s.course_title,''), c.course_title) AS course_title,
               u.first_name, u.last_name,
               sw.action_at AS reviewed_at, sw.comment
        FROM syllabus_workflow sw
        JOIN syllabus s ON sw.syllabus_id = s.id
        JOIN users u    ON s.uploaded_by  = u.id
        LEFT JOIN courses c ON s.course_id = c.id
        JOIN roles r    ON sw.role_id     = r.id
        WHERE r.role_name   = 'department_head'
          AND sw.action     = 'Rejected'
          AND u.department_id = ?
        ORDER BY sw.action_at DESC
    ");
    $stmt->execute([$dept_id]);
    $rejected_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$unread_count  = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);
$pending_count = count($pending_rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Syllabus Review - SCC-CCS Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .text-orange { color: #ff8800 !important; }
        .btn-orange  { background-color: #ff8800 !important; color: #fff !important; border: none; }
        .btn-orange:hover { background-color: #e67a00 !important; }
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
        <a href="syllabus_review.php"       class="nav-link text-white active-nav-link p-3 rounded">Syllabus Review</a>
        <a href="upload_syllabus.php"       class="nav-link text-white p-3 rounded hover-effect">Upload Syllabus</a>
        <a href="my_submissions.php"        class="nav-link text-white p-3 rounded hover-effect">My Submissions</a>
        <a href="shared_syllabus.php"       class="nav-link text-white p-3 rounded hover-effect">Shared Syllabus</a>
        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">ACCOUNT MANAGEMENT</div>
        <a href="registration_requests.php" class="nav-link text-white p-3 rounded hover-effect">Registration Requests</a>
        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
        <a href="profile.php"               class="nav-link text-white p-3 rounded hover-effect">Profile</a>
        <a href="../logout.php"             class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 p-5" style="margin-left:260px;">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-orange font-serif fw-bold">Syllabus Review</h2>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-<?= $pending_count > 0 ? 'warning text-dark' : 'secondary opacity-50' ?> rounded-pill px-3 py-1 shadow-sm">
                    <i class="bi bi-exclamation-circle me-1"></i><?= $pending_count ?> Pending
                </span>
                <!-- Notification Bell -->
                <div class="dropdown">
                        <div class="position-relative" style="cursor:pointer;" data-bs-toggle="dropdown">
                        <i class="bi bi-bell fs-4 text-dark"></i>
                        <?php if ($unread_count > 0): ?>
                        <span class="notif-dot"></span>
                        <?php endif; ?>
                        </div>

                        <ul class="dropdown-menu dropdown-menu-end shadow"
                        style="width:320px;max-height:400px;overflow-y:auto;">

                        <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom">
                        <strong>Notifications</strong>
                        <?php if ($unread_count > 0): ?>
                        <a href="?mark_read=1" class="text-decoration-none small text-orange">
                            Mark all read
                        </a>
                        <?php endif; ?>
                        </li>

        <?php if (empty($notifications)): ?>
            <li class="px-3 py-3 text-center text-muted small">
                No notifications yet
            </li>
        <?php else: ?>

            <?php foreach ($notifications as $n):
                $color = get_notification_color($n['message']); ?>
                
                <li class="px-3 py-2 border-bottom <?= !$n['is_read'] ? 'bg-light' : '' ?>">
                    <p class="mb-0 small">
                        <span class="<?= $color['text'] ?> fw-bold me-1">
                            <?= $color['icon'] ?>
                        </span>
                        <span class="<?= $color['text'] ?>">
                            <?= htmlspecialchars($n['message']) ?>
                        </span>
                    </p>

                    <span class="text-muted" style="font-size:.7rem;">
                        <?= date('M d, Y h:i A', strtotime($n['created_at'])) ?>
                    </span>
                </li>

            <?php endforeach; ?>

        <?php endif; ?>
    </ul>
</div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Status alert -->
        <?php if ($pending_count === 0): ?>
        <div class="alert border-0 shadow-sm mb-4 d-flex align-items-center p-3 rounded-3"
             style="background-color:rgba(220,53,69,.08);">
            <div class="bg-danger text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center"
                 style="width:45px;height:45px;">
                <i class="bi bi-megaphone-fill fs-5"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-1 text-muted opacity-75">All Caught Up</h6>
                <p class="mb-0 text-muted small">No faculty syllabus submissions awaiting review.</p>
            </div>
        </div>
        <?php else: ?>
        <div class="alert border-0 shadow-sm mb-4 d-flex align-items-center p-3 rounded-3"
             style="background-color:rgba(255,193,7,.1);">
            <div class="bg-warning text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center"
                 style="width:45px;height:45px;">
                <i class="bi bi-megaphone-fill fs-5"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-1 text-muted opacity-75">Action Required</h6>
                <p class="mb-0 text-muted small"><?= $pending_count ?> syllabus submission(s) awaiting your review.</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tabbed Submissions -->
        <div class="card premium-card p-4 mb-5 shadow-sm">
            <ul class="nav nav-tabs nav-fill mb-4 border-bottom" id="syllabusTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active font-serif fw-bold text-orange"
                            data-bs-toggle="tab" data-bs-target="#tabPending" type="button">
                        Pending Approval
                        <?php if ($pending_count > 0): ?>
                            <span class="badge bg-warning text-dark ms-1"><?= $pending_count ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link font-serif fw-bold text-orange"
                            data-bs-toggle="tab" data-bs-target="#tabApproved" type="button">
                        Approved
                        <?php if (count($approved_rows) > 0): ?>
                            <span class="badge bg-success ms-1"><?= count($approved_rows) ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link font-serif fw-bold text-orange"
                            data-bs-toggle="tab" data-bs-target="#tabDeclined" type="button">
                        Declined
                        <?php if (count($rejected_rows) > 0): ?>
                            <span class="badge bg-danger ms-1"><?= count($rejected_rows) ?></span>
                        <?php endif; ?>
                    </button>
                </li>
            </ul>

            <div class="tab-content">

                <!-- ── Pending Tab ── -->
                <div class="tab-pane fade show active" id="tabPending">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-premium">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-secondary small">#</th>
                                    <th class="text-secondary small">INSTRUCTOR</th>
                                    <th class="text-secondary small">COURSE</th>
                                    <th class="text-secondary small d-none d-lg-table-cell">TYPE</th>
                                    <th class="text-secondary small">STATUS</th>
                                    <th class="text-secondary small text-center">FILE</th>
                                    <th class="text-secondary small">SUBMITTED</th>
                                    <th class="text-secondary small text-center">ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pending_rows)): ?>
                                    <tr><td colspan="8" class="text-center text-muted py-4">No submissions awaiting approval</td></tr>
                                <?php else: foreach ($pending_rows as $i => $sub): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold small"><?= htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']) ?></span>
                                                <span class="text-muted" style="font-size:.7rem;"><?= htmlspecialchars($sub['uploader_email']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold small"><?= htmlspecialchars($sub['course_code']) ?></span>
                                                <span class="text-muted text-truncate" style="font-size:.7rem;max-width:140px;"><?= htmlspecialchars($sub['course_title']) ?></span>
                                            </div>
                                        </td>
                                        <td class="d-none d-lg-table-cell small"><?= htmlspecialchars($sub['subject_type'] ?? '—') ?></td>
                                        <td>
                                            <span class="badge bg-warning text-dark bg-opacity-25 border border-warning rounded-pill px-3" style="font-size:.75rem;">Pending</span>
                                        </td>
                                        <td class="text-center">
                                            <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($sub['file_path'])) ?>"
                                               target="_blank" rel="noopener" class="btn btn-sm btn-link text-orange p-0">
                                                <i class="bi bi-file-earmark-pdf fs-5"></i>
                                            </a>
                                        </td>
                                        <td class="small"><?= date('M d, Y', strtotime($sub['submitted_at'])) ?></td>
                                        <td class="text-center">
                                            <button type="button"
                                                    onclick="handleReview('approve', <?= $sub['id'] ?>, '<?= htmlspecialchars($sub['course_code']) ?>')"
                                                    class="btn btn-sm btn-success rounded-pill me-1 px-3">
                                                <i class="bi bi-check me-1"></i>Approve
                                            </button>
                                            <button type="button"
                                                    onclick="handleReview('reject', <?= $sub['id'] ?>, '<?= htmlspecialchars($sub['course_code']) ?>')"
                                                    class="btn btn-sm btn-danger rounded-pill px-3">
                                                <i class="bi bi-x me-1"></i>Reject
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ── Approved Tab ── -->
                <div class="tab-pane fade" id="tabApproved">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-premium">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-secondary small">#</th>
                                    <th class="text-secondary small">INSTRUCTOR</th>
                                    <th class="text-secondary small">COURSE</th>
                                    <th class="text-secondary small">STATUS</th>
                                    <th class="text-secondary small text-center">FILE</th>
                                    <th class="text-secondary small">APPROVED ON</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($approved_rows)): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">No approved submissions</td></tr>
                                <?php else: foreach ($approved_rows as $i => $sub): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td class="small fw-bold"><?= htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']) ?></td>
                                        <td>
                                            <span class="fw-bold small"><?= htmlspecialchars($sub['course_code']) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success bg-opacity-25 text-success border border-success rounded-pill px-3" style="font-size:.75rem;">Approved</span>
                                        </td>
                                        <td class="text-center">
                                            <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($sub['file_path'])) ?>"
                                               target="_blank" rel="noopener" class="btn btn-sm btn-link text-orange p-0">
                                                <i class="bi bi-file-earmark-pdf fs-5"></i>
                                            </a>
                                        </td>
                                        <td class="small"><?= $sub['reviewed_at'] ? date('M d, Y', strtotime($sub['reviewed_at'])) : '—' ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ── Declined Tab ── -->
                <div class="tab-pane fade" id="tabDeclined">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-premium">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-secondary small">#</th>
                                    <th class="text-secondary small">INSTRUCTOR</th>
                                    <th class="text-secondary small">COURSE</th>
                                    <th class="text-secondary small">STATUS</th>
                                    <th class="text-secondary small">REASON</th>
                                    <th class="text-secondary small">DECLINED ON</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rejected_rows)): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">No declined submissions</td></tr>
                                <?php else: foreach ($rejected_rows as $i => $sub): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td class="small fw-bold"><?= htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']) ?></td>
                                        <td>
                                            <span class="fw-bold small"><?= htmlspecialchars($sub['course_code']) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger bg-opacity-25 text-danger border border-danger rounded-pill px-3" style="font-size:.75rem;">Rejected</span>
                                        </td>
                                        <td class="small"><?= htmlspecialchars($sub['comment'] ?? '—') ?></td>
                                        <td class="small"><?= $sub['reviewed_at'] ? date('M d, Y', strtotime($sub['reviewed_at'])) : '—' ?></td>
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

<!-- Hidden POST form (submitted by SweetAlert confirm) -->
<form id="reviewForm" method="POST" action="syllabus_review.php">
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
            html: `Approve <strong>${courseCode}</strong> and forward to the Dean?`,
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