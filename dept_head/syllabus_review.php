<?php
/**
 * syllabus_review.php
 * Department Head — review syllabi submitted to their department.
 * Rebuilt for premium aesthetics, layout consistency, and better visual hierarchy.
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

// Handle Approve / Reject POST
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

$pending_rows = [];
$approved_rows = [];
$rejected_rows = [];

if ($college_id) {
    // Pending
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

    // Approved by dean
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
    <title>Syllabus Review &mdash; SCC-CCS Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/common.js"></script>
</head>
<body class="bg-light">
    <?php $active_page = 'review'; include '_sidebar.php'; ?>

    <main class="scc-main">
        <div class="mb-4 position-relative">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-1" style="color:var(--text);letter-spacing:-0.5px;">Syllabus <span class="text-orange">Review</span></h2>
                    <p class="text-secondary mb-0" style="font-size:0.95rem;"><?= get_current_school_year() ?> &mdash; Departmental Approval Workflow</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge <?= $pending_count > 0 ? 'bg-warning-light text-warning border-warning-border' : 'bg-light text-muted border' ?> rounded-pill px-3 py-2" style="font-size:0.75rem">
                        <i class="bi bi-clock-history me-1"></i> <?= $pending_count ?> Awaiting Action
                    </span>
                </div>
            </div>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-4 py-3 mb-4 d-flex align-items-center animate-in" style="background:var(--success-light); color:var(--success)">
                <i class="bi bi-check-circle-fill me-3 fs-5"></i>
                <div><?= htmlspecialchars($success_msg) ?></div>
            </div>
        <?php endif; ?>

        <!-- Tabs + Search Row -->
        <div class="scc-tab-search-wrapper animate-in" style="--animation-order:3">
            <div class="scc-tabs-container" id="reviewTabs" role="tablist">
                <button class="scc-tab-item tab-orange active" data-bs-toggle="tab" data-bs-target="#tabPending" type="button">
                    <span class="tab-indicator"></span> Pending <span class="tab-count"><?= $pending_count ?></span>
                </button>
                <button class="scc-tab-item tab-green" data-bs-toggle="tab" data-bs-target="#tabApproved" type="button">
                    <span class="tab-indicator"></span> Approved <span class="tab-count"><?= count($approved_rows) ?></span>
                </button>
                <button class="scc-tab-item tab-red" data-bs-toggle="tab" data-bs-target="#tabDeclined" type="button">
                    <span class="tab-indicator"></span> Rejected <span class="tab-count"><?= count($rejected_rows) ?></span>
                </button>
            </div>
            <div class="position-relative search-container" style="width:100%;max-width:300px;">
                <i class="bi bi-search position-absolute" style="left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
                <input type="text" id="reviewSearch" class="form-control ps-5" placeholder="Filter submissions..." style="border-radius:var(--radius-md);background:var(--bg-card);border:1px solid var(--border);height:45px;">
            </div>
        </div>

        <div class="tab-content pt-2">
            <!-- Pending Tab -->
            <div class="tab-pane fade show active" id="tabPending">
                <?php if (empty($pending_rows)): ?>
                    <div class="scc-card p-5 text-center animate-in">
                        <div class="mb-3"><i class="bi bi-check2-circle text-muted" style="font-size:3rem;opacity:0.3"></i></div>
                        <h5 class="fw-bold" style="color:var(--text)">All Caught Up!</h5>
                        <p class="text-secondary mb-0">No syllabus submissions are currently awaiting your review.</p>
                    </div>
                <?php else: ?>
                    <div class="scc-card scc-premium-shadow border-0 animate-in">
                        <div class="table-responsive">
                            <table class="scc-table mb-0">
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
                                    <?php foreach ($pending_rows as $sub): ?>
                                        <tr class="review-item" data-search="<?= strtolower(htmlspecialchars($sub['first_name'].' '.$sub['last_name'].' '.$sub['course_code'].' '.$sub['course_title'])) ?>">
                                            <td class="ps-4">
                                                <div class="fw-bold small"><?= htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']) ?></div>
                                                <div class="text-muted small" style="font-size:.7rem;"><?= htmlspecialchars($sub['uploader_email']) ?></div>
                                            </td>
                                            <td>
                                                <div class="fw-bold small"><?= htmlspecialchars($sub['course_code']) ?></div>
                                                <div class="text-muted text-truncate small" style="font-size:.7rem;max-width:140px;"><?= htmlspecialchars($sub['course_title']) ?></div>
                                            </td>
                                            <td class="d-none d-lg-table-cell small"><?= htmlspecialchars($sub['subject_type'] ?? '&mdash;') ?></td>
                                            <td><span class="badge bg-warning-light text-warning border-warning-border rounded-pill px-3" style="font-size:.75rem;">Pending Review</span></td>
                                            <td class="text-center">
                                                <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($sub['file_path'])) ?>" target="_blank" class="text-orange fs-5"><i class="bi bi-file-earmark-pdf"></i></a>
                                            </td>
                                            <td class="small"><?= date('M d, Y', strtotime($sub['submitted_at'])) ?></td>
                                            <td class="text-center pe-4">
                                                <div class="d-flex gap-2 justify-content-center">
                                                    <button type="button" onclick="handleReview('approve', <?= $sub['id'] ?>, '<?= htmlspecialchars($sub['course_code']) ?>')" class="btn btn-sm btn-success rounded-pill px-3">Approve</button>
                                                    <button type="button" onclick="handleReview('reject', <?= $sub['id'] ?>, '<?= htmlspecialchars($sub['course_code']) ?>')" class="btn btn-sm btn-danger rounded-pill px-3">Reject</button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Approved Tab -->
            <div class="tab-pane fade" id="tabApproved">
                <?php if (empty($approved_rows)): ?>
                    <div class="scc-card p-5 text-center animate-in">
                        <div class="mb-3"><i class="bi bi-journal-check text-muted" style="font-size:3rem;opacity:0.3"></i></div>
                        <h5 class="fw-bold" style="color:var(--text)">No Approved Submissions</h5>
                        <p class="text-secondary mb-0">Approved syllabi will appear here once reviewed.</p>
                    </div>
                <?php else: ?>
                    <div class="scc-card scc-premium-shadow border-0 animate-in">
                        <div class="table-responsive">
                            <table class="scc-table mb-0">
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
                                    <?php foreach ($approved_rows as $sub): ?>
                                        <tr class="review-item" data-search="<?= strtolower(htmlspecialchars($sub['first_name'].' '.$sub['last_name'].' '.$sub['course_code'].' '.$sub['course_title'])) ?>">
                                            <td class="ps-4"><div class="fw-bold small"><?= htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']) ?></div></td>
                                            <td><span class="fw-bold small"><?= htmlspecialchars($sub['course_code']) ?></span></td>
                                            <td><span class="badge bg-success-light text-success border-success-border rounded-pill px-3" style="font-size:.75rem;">Approved</span></td>
                                            <td class="text-center">
                                                <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($sub['file_path'])) ?>" target="_blank" class="text-orange fs-5"><i class="bi bi-file-earmark-pdf"></i></a>
                                            </td>
                                            <td class="pe-4 small"><?= $sub['reviewed_at'] ? date('M d, Y', strtotime($sub['reviewed_at'])) : '&mdash;' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Rejected Tab -->
            <div class="tab-pane fade" id="tabDeclined">
                <?php if (empty($rejected_rows)): ?>
                    <div class="scc-card p-5 text-center animate-in">
                        <div class="mb-3"><i class="bi bi-journal-x text-muted" style="font-size:3rem;opacity:0.3"></i></div>
                        <h5 class="fw-bold" style="color:var(--text)">No Rejected Submissions</h5>
                        <p class="text-secondary mb-0">Rejected syllabi will appear here for tracking purposes.</p>
                    </div>
                <?php else: ?>
                    <div class="scc-card scc-premium-shadow border-0 animate-in">
                        <div class="table-responsive">
                            <table class="scc-table mb-0">
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
                                    <?php foreach ($rejected_rows as $sub): ?>
                                        <tr class="review-item" data-search="<?= strtolower(htmlspecialchars($sub['first_name'].' '.$sub['last_name'].' '.$sub['course_code'].' '.$sub['course_title'])) ?>">
                                            <td class="ps-4"><div class="fw-bold small"><?= htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']) ?></div></td>
                                            <td><span class="fw-bold small"><?= htmlspecialchars($sub['course_code']) ?></span></td>
                                            <td><span class="badge bg-danger-light text-danger border-danger-border rounded-pill px-3" style="font-size:.75rem;">Rejected</span></td>
                                            <td class="small"><?= htmlspecialchars($sub['comment'] ?? '&mdash;') ?></td>
                                            <td class="pe-4 small"><?= $sub['reviewed_at'] ? date('M d, Y', strtotime($sub['reviewed_at'])) : '&mdash;' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <form id="reviewForm" method="POST" action="syllabus_review.php">
        <input type="hidden" name="syllabus_id" id="formSyllabusId">
        <input type="hidden" name="action" id="formAction">
        <input type="hidden" name="comment" id="formComment">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('reviewSearch').addEventListener('keyup', function() {
            let q = this.value.toLowerCase();
            document.querySelectorAll('.review-item').forEach(item => {
                let text = item.getAttribute('data-search');
                item.style.display = text.includes(q) ? '' : 'none';
            });
        });

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
