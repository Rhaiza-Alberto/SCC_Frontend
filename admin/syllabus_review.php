<?php
/**
 * admin/syllabus_review.php
 * Dean reviews faculty syllabus submissions (step 1 of workflow).
 * Replaces the former dept_head/syllabus_review.php
 */
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

restrict_to_role('dean');

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Dean / Admin';
$role_display = "Dean's Panel";

if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: syllabus_review.php');
    exit();
}

// ── Handle Approve / Reject ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['syllabus_id'])) {
    $syllabus_id = (int) $_POST['syllabus_id'];
    $action = $_POST['action'] === 'approve' ? 'Approved' : 'Rejected';
    $comment = trim($_POST['comment'] ?? '') ?: null;
    process_syllabus_action($syllabus_id, $action, $comment);
    $_SESSION['review_success'] = $action === 'Approved'
        ? 'Syllabus approved and forwarded to VPAA.'
        : 'Syllabus rejected. The faculty member has been notified.';
    header('Location: syllabus_review.php');
    exit();
}

$success_msg = $_SESSION['review_success'] ?? '';
unset($_SESSION['review_success']);

$conn = get_db();

// Pending for dean
$stmt = $conn->prepare("
    SELECT s.*,
           COALESCE(NULLIF(s.course_code,''),  c.course_code)  AS course_code,
           COALESCE(NULLIF(s.course_title,''), c.course_title) AS course_title,
           u.first_name, u.last_name, u.email AS uploader_email,
           r2.role_name AS uploader_role,
           sw.id AS workflow_id
    FROM syllabus_workflow sw
    JOIN syllabus s ON sw.syllabus_id = s.id
    JOIN users u    ON s.uploaded_by  = u.id
    JOIN roles r2   ON u.role_id      = r2.id
    LEFT JOIN courses c ON s.course_id = c.id
    JOIN roles r    ON sw.role_id     = r.id
    WHERE r.role_name = 'dean' AND sw.action = 'Pending'
    ORDER BY s.submitted_at DESC
");
$stmt->execute();
$pending_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Approved by dean
$stmt = $conn->prepare("
    SELECT s.*,
           COALESCE(NULLIF(s.course_code,''),  c.course_code)  AS course_code,
           COALESCE(NULLIF(s.course_title,''), c.course_title) AS course_title,
           u.first_name, u.last_name, u.email AS uploader_email,
           r2.role_name AS uploader_role,
           sw.action_at AS reviewed_at, sw.comment
    FROM syllabus_workflow sw
    JOIN syllabus s ON sw.syllabus_id = s.id
    JOIN users u    ON s.uploaded_by  = u.id
    JOIN roles r2   ON u.role_id      = r2.id
    LEFT JOIN courses c ON s.course_id = c.id
    JOIN roles r    ON sw.role_id     = r.id
    WHERE r.role_name = 'dean' AND sw.action = 'Approved'
    ORDER BY sw.action_at DESC
");
$stmt->execute();
$approved_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Rejected by dean
$stmt = $conn->prepare("
    SELECT s.*,
           COALESCE(NULLIF(s.course_code,''),  c.course_code)  AS course_code,
           COALESCE(NULLIF(s.course_title,''), c.course_title) AS course_title,
           u.first_name, u.last_name, u.email AS uploader_email,
           r2.role_name AS uploader_role,
           sw.action_at AS reviewed_at, sw.comment
    FROM syllabus_workflow sw
    JOIN syllabus s ON sw.syllabus_id = s.id
    JOIN users u    ON s.uploaded_by  = u.id
    JOIN roles r2   ON u.role_id      = r2.id
    LEFT JOIN courses c ON s.course_id = c.id
    JOIN roles r    ON sw.role_id     = r.id
    WHERE r.role_name = 'dean' AND sw.action = 'Rejected'
    ORDER BY sw.action_at DESC
");
$stmt->execute();
$rejected_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pending_count = count($pending_rows);
$unread_count = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Syllabus Review — SCC Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php $active_page = 'review';
    include '_sidebar.php'; ?>

    <main class="scc-main">
        <div class="scc-page-header position-relative">
            <nav aria-label="breadcrumb" class="animate-in" style="--animation-order: 1">
                <ol class="breadcrumb mb-2" style="font-size: 0.75rem; letter-spacing: 0.5px; text-transform: uppercase;">
                    <li class="breadcrumb-item"><a href="admin_dashboard.php" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item active text-primary fw-bold" aria-current="page">Syllabus Review</li>
                </ol>
            </nav>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 animate-in" style="--animation-order: 2">
                <div>
                    <h2 class="fw-800 mb-1" style="color:var(--text); letter-spacing: -0.5px;">Syllabus <span class="text-orange">Command Center</span></h2>
                    <p class="text-secondary mb-0" style="font-size: 0.95rem;">Streamline institutional excellence through rigorous syllabus evaluation.</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="dropdown">
                        <div class="position-relative" style="cursor:pointer" data-bs-toggle="dropdown">
                            <i class="bi bi-bell fs-5" style="color:var(--text)"></i>
                            <?php if ($unread_count > 0): ?><span class="notif-badge"><?= $unread_count > 9 ? '9+' : $unread_count ?></span><?php endif; ?>
                        </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0"
                        style="width:340px;max-height:420px;overflow-y:auto;border-radius:var(--radius-md);background:var(--bg-card)">
                        <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom sticky-top"
                            style="background:var(--bg-card)">
                            <strong style="font-size:0.9rem;color:var(--text)">Notifications</strong>
                            <?php if ($unread_count > 0): ?><a href="?mark_read=1" class="text-decoration-none small"
                                    style="color:var(--primary)">Mark all read</a><?php endif; ?>
                        </li>
                        <?php if (empty($notifications)): ?>
                            <li class="px-3 py-4 text-center" style="color:var(--text-muted)"><i
                                    class="bi bi-bell-slash fs-4 d-block mb-2 opacity-50"></i><span class="small">No
                                    notifications</span></li>
                        <?php else:
                            foreach ($notifications as $n):
                                $color = get_notification_color($n['message']); ?>
                                <li class="border-bottom"
                                    style="<?= !$n['is_read'] ? 'background:var(--primary-light)' : '' ?>">
                                    <a href="notifications.php?notif_id=<?= $n['id'] ?>"
                                        class="text-decoration-none d-block px-3 py-2">
                                        <p class="mb-0 small" style="color:var(--text)"><span
                                                class="<?= $color['text'] ?> fw-bold me-1"><?= $color['icon'] ?></span><?= htmlspecialchars($n['message']) ?>
                                        </p>
                                        <span
                                            style="font-size:.7rem;color:var(--text-muted)"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></span>
                                    </a>
                                </li>
                            <?php endforeach; endif; ?>
                        <li style="background:var(--bg-card);border-top:1px solid var(--border)"><a
                                href="notifications.php"
                                class="d-block text-center text-decoration-none small fw-bold py-2"
                                style="color:var(--primary)">View all notifications</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4 rounded-3 border-0 shadow-sm">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($pending_count === 0): ?>
            <div class="alert border-0 shadow-sm mb-4 d-flex align-items-center p-3 rounded-3"
                style="background:rgba(220,53,69,.08);">
                <div class="bg-danger text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center"
                    style="width:45px;height:45px;">
                    <i class="bi bi-megaphone-fill fs-5"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1 text-muted opacity-75">All Caught Up</h6>
                    <p class="mb-0 text-muted small">No faculty syllabus submissions awaiting your review.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="alert border-0 shadow-sm mb-4 d-flex align-items-center p-3 rounded-3"
                style="background:rgba(255,193,7,.1);">
                <div class="bg-warning text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center"
                    style="width:45px;height:45px;">
                    <i class="bi bi-megaphone-fill fs-5"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1 text-muted opacity-75">Action Required</h6>
                    <p class="mb-0 text-muted small"><?= $pending_count ?> syllabus submission(s) awaiting your review.
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Search and Bookmark Tabs -->
        <!-- Search and Bookmark Tabs -->
        <div class="scc-tab-search-wrapper animate-in" style="--animation-order:3">
            <div class="scc-tabs-container" id="reviewTabs" role="tablist">
                <button class="scc-tab-item tab-orange <?= (!isset($_GET['status']) || $_GET['status'] === 'Pending') ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tabPending" type="button">
                    <span class="tab-indicator"></span> Pending <span class="tab-count"><?= count($pending_rows) ?></span>
                </button>
                <button class="scc-tab-item tab-green <?= (isset($_GET['status']) && $_GET['status'] === 'Approved') ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tabApproved" type="button">
                    <span class="tab-indicator"></span> Approved / Forwarded <span class="tab-count"><?= count($approved_rows) ?></span>
                </button>
                <button class="scc-tab-item tab-red <?= (isset($_GET['status']) && $_GET['status'] === 'Declined') ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tabDeclined" type="button">
                    <span class="tab-indicator"></span> Declined <span class="tab-count"><?= count($rejected_rows) ?></span>
                </button>
            </div>
            <div class="position-relative search-container" style="width:100%;max-width:300px;">
                <i class="bi bi-search position-absolute" style="left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
                <input type="text" id="reviewSearch" class="form-control ps-5" placeholder="Search in this tab..." style="border-radius:var(--radius-md); background:var(--bg-card); border:1px solid var(--border); height: 45px;">
            </div>
        </div>

        <div class="tab-content pt-4">
                            <!-- PENDING TAB -->
                            <div class="tab-pane fade show active" id="tabPending">
                                <?php if (empty($pending_rows)): ?>
                                    <div class="scc-card p-5 text-center">
                                        <div class="mb-3"><i class="bi bi-check-all text-success"
                                                style="font-size:3rem; opacity:0.3"></i></div>
                                        <h5 class="fw-bold" style="color:var(--text)">All Caught Up!</h5>
                                        <p class="text-secondary mb-0">No syllabus submissions are currently awaiting your
                                            review.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="scc-card scc-premium-shadow border-0 animate-in" style="--animation-order:4">
                                        <div class="table-responsive">
                                            <table class="scc-table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th class="ps-4">Course</th>
                                                        <th>Instructor</th>
                                                        <th>Category</th>
                                                        <th>Submitted</th>
                                                        <th class="text-center">Syllabus</th>
                                                        <th class="text-center pe-4">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($pending_rows as $sub): ?>
                                                        <tr class="review-item" data-search="<?= strtolower(htmlspecialchars($sub['course_code'] . ' ' . $sub['course_title'] . ' ' . $sub['first_name'] . ' ' . $sub['last_name'])) ?>">
                                                            <td class="ps-4">
                                                                <div class="fw-bold small" style="color:var(--text)"><?= htmlspecialchars($sub['course_code']) ?></div>
                                                                <div class="text-secondary small text-truncate" style="max-width:200px;"><?= htmlspecialchars($sub['course_title']) ?></div>
                                                            </td>
                                                            <td>
                                                                <div class="small fw-bold" style="color:var(--text)"><?= htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']) ?></div>
                                                                <div class="text-muted small" style="font-size:0.7rem"><?= ucfirst(htmlspecialchars($sub['uploader_role'])) ?></div>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <div class="rounded-circle" style="width: 6px; height: 6px; background: var(--primary);"></div>
                                                                    <div class="small" style="color:var(--text)"><?= htmlspecialchars($sub['subject_type'] ?? 'General') ?></div>
                                                                </div>
                                                            </td>
                                                            <td class="small text-muted"><?= date('M d, Y', strtotime($sub['submitted_at'])) ?></td>
                                                            <td class="text-center">
                                                                <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($sub['file_path'])) ?>" target="_blank" class="text-primary hover-lift d-inline-block">
                                                                    <i class="bi bi-file-earmark-pdf fs-5"></i>
                                                                </a>
                                                            </td>
                                                            <td class="text-center pe-4">
                                                                <div class="d-flex justify-content-center gap-2">
                                                                    <button type="button" onclick="showTrackerModal(<?= (int)$sub['id'] ?>, '<?= htmlspecialchars($sub['course_code'], ENT_QUOTES) ?>', '<?= htmlspecialchars($sub['course_title'], ENT_QUOTES) ?>')" class="btn btn-sm btn-light border px-2 py-1 text-primary shadow-sm hover-lift" title="Track Progress">
                                                                        <i class="bi bi-geo-alt"></i>
                                                                    </button>
                                                                    <button type="button" onclick="handleReview('approve', <?= (int)$sub['id'] ?>, '<?= htmlspecialchars($sub['course_code'], ENT_QUOTES) ?>')" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm hover-lift">
                                                                        <i class="bi bi-check2-circle me-1"></i> Approve
                                                                    </button>
                                                                    <button type="button" onclick="handleReview('reject', <?= (int)$sub['id'] ?>, '<?= htmlspecialchars($sub['course_code'], ENT_QUOTES) ?>')" class="btn btn-sm btn-outline-danger rounded-pill px-3 shadow-sm hover-lift">
                                                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Return
                                                                    </button>
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

                            <!-- APPROVED TAB -->
                            <div class="tab-pane fade" id="tabApproved">
                                <?php if (empty($approved_rows)): ?>
                                    <div class="scc-card p-5 text-center animate-in">
                                        <div class="mb-3"><i class="bi bi-check-circle text-success" style="font-size:3rem; opacity:0.3"></i></div>
                                        <h5 class="fw-bold" style="color:var(--text)">No Approved Syllabi</h5>
                                        <p class="text-secondary mb-0">Submissions you've approved will appear here.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="scc-card scc-premium-shadow border-0 animate-in">
                                        <div class="table-responsive">
                                            <table class="scc-table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th class="ps-4">Course</th>
                                                        <th>Instructor</th>
                                                        <th>Status</th>
                                                        <th>Reviewed</th>
                                                        <th class="text-center">Syllabus</th>
                                                        <th class="text-center pe-4">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($approved_rows as $sub): ?>
                                                        <tr class="review-item" data-search="<?= strtolower(htmlspecialchars($sub['course_code'] . ' ' . $sub['course_title'] . ' ' . $sub['first_name'] . ' ' . $sub['last_name'])) ?>">
                                                            <td class="ps-4">
                                                                <div class="fw-bold small" style="color:var(--text)"><?= htmlspecialchars($sub['course_code']) ?></div>
                                                                <div class="text-secondary small"><?= htmlspecialchars($sub['course_title']) ?></div>
                                                            </td>
                                                            <td>
                                                                <div class="small fw-bold" style="color:var(--text)"><?= htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']) ?></div>
                                                                <div class="text-muted small" style="font-size:0.7rem"><?= ucfirst(htmlspecialchars($sub['uploader_role'] ?? 'faculty')) ?></div>
                                                            </td>
                                                            <td><span class="badge-status bg-success-subtle text-success">Forwarded</span></td>
                                                            <td class="small text-muted"><?= date('M d, Y', strtotime($sub['reviewed_at'])) ?></td>
                                                            <td class="text-center">
                                                                <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($sub['file_path'])) ?>" target="_blank" class="text-primary hover-lift d-inline-block">
                                                                    <i class="bi bi-file-earmark-pdf fs-5"></i>
                                                                </a>
                                                            </td>
                                                            <td class="text-center pe-4">
                                                                <button type="button" onclick="showTrackerModal(<?= (int)$sub['id'] ?>, '<?= htmlspecialchars($sub['course_code'], ENT_QUOTES) ?>', '<?= htmlspecialchars($sub['course_title'], ENT_QUOTES) ?>')" class="btn btn-sm btn-light border px-2 py-1 text-primary shadow-sm hover-lift" title="Track Progress"><i class="bi bi-geo-alt"></i></button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- DECLINED TAB -->
                            <div class="tab-pane fade" id="tabDeclined">
                                <?php if (empty($rejected_rows)): ?>
                                    <div class="scc-card p-5 text-center animate-in">
                                        <div class="mb-3"><i class="bi bi-x-circle text-muted" style="font-size:3rem; opacity:0.3"></i></div>
                                        <h5 class="fw-bold" style="color:var(--text)">No Declined Syllabi</h5>
                                        <p class="text-secondary mb-0">Returned submissions will appear here.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="scc-card scc-premium-shadow border-0 animate-in">
                                        <div class="table-responsive">
                                            <table class="scc-table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th class="ps-4">Course</th>
                                                        <th>Instructor</th>
                                                        <th>Reason</th>
                                                        <th>Declined</th>
                                                        <th class="text-center">Syllabus</th>
                                                        <th class="text-center pe-4">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($rejected_rows as $sub): ?>
                                                        <tr class="review-item" data-search="<?= strtolower(htmlspecialchars($sub['course_code'] . ' ' . $sub['course_title'] . ' ' . $sub['first_name'] . ' ' . $sub['last_name'])) ?>">
                                                            <td class="ps-4">
                                                                <div class="fw-bold small" style="color:var(--text)"><?= htmlspecialchars($sub['course_code']) ?></div>
                                                                <div class="text-secondary small"><?= htmlspecialchars($sub['course_title']) ?></div>
                                                            </td>
                                                            <td>
                                                                <div class="small fw-bold" style="color:var(--text)"><?= htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']) ?></div>
                                                                <div class="text-muted small" style="font-size:0.7rem"><?= htmlspecialchars($sub['uploader_email']) ?></div>
                                                            </td>
                                                            <td class="small"><?= htmlspecialchars($sub['comment'] ?? 'No reason provided') ?></td>
                                                            <td class="small text-muted"><?= date('M d, Y', strtotime($sub['reviewed_at'])) ?></td>
                                                            <td class="text-center">
                                                                <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($sub['file_path'])) ?>" target="_blank" class="text-danger hover-lift d-inline-block">
                                                                    <i class="bi bi-file-earmark-pdf fs-5"></i>
                                                                </a>
                                                            </td>
                                                            <td class="text-center pe-4">
                                                                <button type="button" onclick="showTrackerModal(<?= (int)$sub['id'] ?>, '<?= htmlspecialchars($sub['course_code'], ENT_QUOTES) ?>', '<?= htmlspecialchars($sub['course_title'], ENT_QUOTES) ?>')" class="btn btn-sm btn-light border px-2 py-1 text-primary shadow-sm hover-lift" title="Track Progress"><i class="bi bi-geo-alt"></i></button>
                                                            </td>
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

    <!-- Hidden POST form for SweetAlert -->
    <form id="reviewForm" method="POST" action="syllabus_review.php">
        <input type="hidden" name="syllabus_id" id="formSyllabusId">
        <input type="hidden" name="action" id="formAction">
        <input type="hidden" name="comment" id="formComment">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/common.js"></script>
    <script>
        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('open'); document.getElementById('sidebarOverlay').classList.toggle('active'); }

        // Real-time search/filter
        document.getElementById('reviewSearch').addEventListener('input', function (e) {
            const q = e.target.value.toLowerCase().trim();
            const activePane = document.querySelector('.tab-pane.active');
            const items = activePane.querySelectorAll('.review-item');

            items.forEach(item => {
                const text = item.getAttribute('data-search') || '';
                item.style.display = text.includes(q) ? '' : 'none';
            });
        });

        function handleReview(action, syllabusId, courseCode) {
            if (!syllabusId) return;
            
            if (action === 'approve') {
                Swal.fire({
                    title: 'Approve Syllabus?',
                    html: `Approve <strong>${courseCode}</strong> and forward to VPAA?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--success)',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Approve'
                }).then(r => {
                    if (r.isConfirmed) {
                        document.getElementById('formSyllabusId').value = syllabusId;
                        document.getElementById('formAction').value = 'approve';
                        document.getElementById('formComment').value = '';
                        document.getElementById('reviewForm').submit();
                    }
                });
            } else {
                Swal.fire({
                    title: 'Reject Syllabus?',
                    html: `Reason for rejecting <strong>${courseCode}</strong>:`,
                    input: 'textarea',
                    inputPlaceholder: 'Enter rejection reason (optional)...',
                    inputAttributes: { rows: 3 },
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--danger)',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Reject'
                }).then(r => {
                    if (r.isConfirmed) {
                        document.getElementById('formSyllabusId').value = syllabusId;
                        document.getElementById('formAction').value = 'reject';
                        document.getElementById('formComment').value = r.value || '';
                        document.getElementById('reviewForm').submit();
                    }
                });
            }
        }
    </script>
    <?php include __DIR__ . '/../_tracker_modal.php'; ?>
</body>

</html>