<?php
/**
 * syllabus_review.php
 * Department Head — review syllabi submitted to their department.
 * Approve/Reject triggers the workflow engine.
 */
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

restrict_to_role('department_head');

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$role_display = 'Dept Head Panel';
$college_id = $_SESSION['college_id'] ?? null;

// Handle mark-all-read
if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: syllabus_review.php');
    exit();
}

// ── Handle Approve / Reject POST ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['syllabus_id'])) {
    $syllabus_id = (int) $_POST['syllabus_id'];
    $action = $_POST['action'] === 'approve' ? 'Approved' : 'Rejected';
    $comment = trim($_POST['comment'] ?? '') ?: null;

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

if ($college_id) {
    // Pending: workflow step for dean is still Pending
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
        WHERE r.role_name   = 'dean'
          AND sw.action     = 'Pending'
          AND u.college_id = ?
        ORDER BY s.submitted_at DESC
    ");
    $stmt->execute([$college_id]);
    $pending_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Approved by dean (or fully approved)
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
        WHERE r.role_name   = 'dean'
          AND sw.action     = 'Approved'
          AND u.college_id = ?
        ORDER BY sw.action_at DESC
    ");
    $stmt->execute([$college_id]);
    $approved_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Rejected by dean
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
        WHERE r.role_name   = 'dean'
          AND sw.action     = 'Rejected'
          AND u.college_id = ?
        ORDER BY sw.action_at DESC
    ");
    $stmt->execute([$college_id]);
    $rejected_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$unread_count = count_unread_notifications($user_id);
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
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/common.js"></script>
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
    <?php $active_page = 'review';
    include '_sidebar.php'; ?>

    <main class="scc-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color:var(--text)">Syllabus <span class="text-orange">Review</span></h4>
                <p style="font-size:0.85rem;color:var(--text-secondary);margin:0"><?= get_current_school_year() ?> —
                    Departmental Approval Workflow</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span
                    class="badge <?= $pending_count > 0 ? 'bg-warning-light text-warning border-warning-border' : 'bg-light text-muted border' ?> rounded-pill px-3 py-1"
                    style="font-size:0.75rem">
                    <i class="bi bi-clock-history me-1"></i> <?= $pending_count ?> Awaiting Action
                </span>
            </div>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-4 py-3 mb-4 d-flex align-items-center" role="alert"
                style="background:var(--success-light); color:var(--success)">
                <i class="bi bi-check-circle-fill me-3 fs-5"></i>
                <div><?= htmlspecialchars($success_msg) ?></div>
            </div>
        <?php endif; ?>

        <!-- Content Area -->
        <div class="scc-card animate-in">
            <div class="card-body p-0">
                <ul class="nav nav-tabs scc-tabs px-4 pt-3 border-0" id="reviewTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabPending">
                            Pending <span class="badge bg-warning ms-2"><?= $pending_count ?></span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabApproved">
                            Approved <span class="badge bg-success ms-2"><?= count($approved_rows) ?></span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabDeclined">
                            Rejected <span class="badge bg-danger ms-2"><?= count($rejected_rows) ?></span>
                        </button>
                    </li>
                </ul>

                <div class="tab-content p-4">

                    <!-- ── Pending Tab ── -->
                    <div class="tab-pane fade show active" id="tabPending">
                        <div class="table-responsive">
                            <table class="scc-table">
                                <thead>
                                    <tr>
                                        <th class="ps-4">INSTRUCTOR</th>
                                        <th>COURSE</th>
                                        <th class="d-none d-lg-table-cell">TYPE</th>
                                        <th>STATUS</th>
                                        <th class="text-center">FILE</th>
                                        <th>SUBMITTED</th>
                                        <th class="text-center pe-4">ACTION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($pending_rows)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-5">
                                                <i class="bi bi-check2-circle fs-1 opacity-25 d-block mb-2"></i>
                                                No submissions awaiting approval
                                            </td>
                                        </tr>
                                    <?php else:
                                        foreach ($pending_rows as $i => $sub): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-bold small">
                                                        <?= htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']) ?>
                                                    </div>
                                                    <div class="text-muted small" style="font-size:.7rem;">
                                                        <?= htmlspecialchars($sub['uploader_email']) ?></div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold small"><?= htmlspecialchars($sub['course_code']) ?>
                                                    </div>
                                                    <div class="text-muted text-truncate small"
                                                        style="font-size:.7rem;max-width:140px;">
                                                        <?= htmlspecialchars($sub['course_title']) ?></div>
                                                </td>
                                                <td class="d-none d-lg-table-cell small">
                                                    <?= htmlspecialchars($sub['subject_type'] ?? '—') ?></td>
                                                <td>
                                                    <span
                                                        class="badge bg-warning-light text-warning border-warning-border rounded-pill px-3"
                                                        style="font-size:.75rem;">Pending Review</span>
                                                </td>
                                                <td class="text-center">
                                                    <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($sub['file_path'])) ?>"
                                                        target="_blank" class="text-orange fs-5"><i
                                                            class="bi bi-file-earmark-pdf"></i></a>
                                                </td>
                                                <td class="small"><?= date('M d, Y', strtotime($sub['submitted_at'])) ?></td>
                                                <td class="text-center pe-4">
                                                    <div class="d-flex gap-2 justify-content-center">
                                                        <button type="button"
                                                            onclick="handleReview('approve', <?= $sub['id'] ?>, '<?= htmlspecialchars($sub['course_code']) ?>')"
                                                            class="btn btn-sm btn-success rounded-pill px-3">Approve</button>
                                                        <button type="button"
                                                            onclick="handleReview('reject', <?= $sub['id'] ?>, '<?= htmlspecialchars($sub['course_code']) ?>')"
                                                            class="btn btn-sm btn-danger rounded-pill px-3">Reject</button>
                                                    </div>
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
                            <table class="scc-table">
                                <thead>
                                    <tr>
                                        <th class="ps-4">INSTRUCTOR</th>
                                        <th>COURSE</th>
                                        <th>STATUS</th>
                                        <th class="text-center">FILE</th>
                                        <th class="pe-4">APPROVED ON</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($approved_rows)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-5">
                                                <i class="bi bi-journal-check fs-1 opacity-25 d-block mb-2"></i>
                                                No approved submissions found
                                            </td>
                                        </tr>
                                    <?php else:
                                        foreach ($approved_rows as $sub): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-bold small">
                                                        <?= htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']) ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span
                                                        class="fw-bold small"><?= htmlspecialchars($sub['course_code']) ?></span>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-success-light text-success border-success-border rounded-pill px-3"
                                                        style="font-size:.75rem;">Approved</span>
                                                </td>
                                                <td class="text-center">
                                                    <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($sub['file_path'])) ?>"
                                                        target="_blank" class="text-orange fs-5"><i
                                                            class="bi bi-file-earmark-pdf"></i></a>
                                                </td>
                                                <td class="pe-4 small">
                                                    <?= $sub['reviewed_at'] ? date('M d, Y', strtotime($sub['reviewed_at'])) : '—' ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ── Declined Tab ── -->
                    <div class="tab-pane fade" id="tabDeclined">
                        <div class="table-responsive">
                            <table class="scc-table">
                                <thead>
                                    <tr>
                                        <th class="ps-4">INSTRUCTOR</th>
                                        <th>COURSE</th>
                                        <th>STATUS</th>
                                        <th>REASON</th>
                                        <th class="pe-4">DECLINED ON</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($rejected_rows)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-5">
                                                <i class="bi bi-journal-x fs-1 opacity-25 d-block mb-2"></i>
                                                No declined submissions found
                                            </td>
                                        </tr>
                                    <?php else:
                                        foreach ($rejected_rows as $sub): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-bold small">
                                                        <?= htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']) ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span
                                                        class="fw-bold small"><?= htmlspecialchars($sub['course_code']) ?></span>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-danger-light text-danger border-danger-border rounded-pill px-3"
                                                        style="font-size:.75rem;">Rejected</span>
                                                </td>
                                                <td class="small"><?= htmlspecialchars($sub['comment'] ?? '—') ?></td>
                                                <td class="pe-4 small">
                                                    <?= $sub['reviewed_at'] ? date('M d, Y', strtotime($sub['reviewed_at'])) : '—' ?>
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
        </div>
    </main>

    <!-- Hidden POST form (submitted by SweetAlert confirm) -->
    <form id="reviewForm" method="POST" action="syllabus_review.php">
        <input type="hidden" name="syllabus_id" id="formSyllabusId">
        <input type="hidden" name="action" id="formAction">
        <input type="hidden" name="comment" id="formComment">
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