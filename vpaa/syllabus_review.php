<?php
/**
 * syllabus_review.php
 * VPAA Review Queue — Final approval step.
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
$username = $_SESSION['username'] ?? 'VPAA';
$role_display = 'VPAA Institutional Hub';

$conn = get_db();

// Handle Actions (Approve/Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action      = ucfirst($_POST['action']) . 'ed'; // 'approve' -> 'Approved', 'reject' -> 'Rejected'
    $syllabus_id = (int) $_POST['syllabus_id'];
    $comment     = $_POST['comment'] ?? '';

    if (process_syllabus_action($syllabus_id, $action, $comment)) {
        $_SESSION['success_message'] = "Syllabus " . ($action === 'Approved' ? 'Approved' : 'Rejected') . " successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to process review action.";
    }
    header('Location: syllabus_review.php');
    exit();
}

// Fetch Pending Reviews for VPAA
$stmt_pending = $conn->prepare("
    SELECT s.*, 
           COALESCE(NULLIF(s.course_code,''),  c.course_code)  AS course_code,
           COALESCE(NULLIF(s.course_title,''), c.course_title) AS course_title,
           u.first_name, u.last_name, u.email AS uploader_email,
           d.department_name,
           r.role_name as review_role
    FROM syllabus s
    JOIN syllabus_workflow sw ON s.id = sw.syllabus_id
    JOIN roles r ON sw.role_id = r.id
    LEFT JOIN courses c ON s.course_id = c.id
    LEFT JOIN users u ON s.uploaded_by = u.id
    LEFT JOIN departments d ON COALESCE(c.department_id, u.department_id) = d.id
    WHERE r.role_name = 'vpaa' 
      AND sw.action = 'Pending'
      AND s.status = 'Pending'
    ORDER BY s.submitted_at ASC
");
$stmt_pending->execute();
$pending_rows = $stmt_pending->fetchAll(PDO::FETCH_ASSOC);

// Fetch Approved (Fully Approved by VPAA)
$stmt_approved = $conn->prepare("
    SELECT s.*,
           COALESCE(NULLIF(s.course_code,''),  c.course_code)  AS course_code,
           COALESCE(NULLIF(s.course_title,''), c.course_title) AS course_title,
           u.first_name, u.last_name,
           (SELECT sw.action_at FROM syllabus_workflow sw JOIN roles r ON sw.role_id = r.id WHERE sw.syllabus_id = s.id AND r.role_name = 'vpaa' AND sw.action = 'Approved' ORDER BY sw.action_at DESC LIMIT 1) as approved_at
    FROM syllabus s
    LEFT JOIN courses c ON s.course_id = c.id
    LEFT JOIN users u ON s.uploaded_by = u.id
    WHERE s.status = 'Approved'
    ORDER BY s.submitted_at DESC
");
$stmt_approved->execute();
$approved_rows = $stmt_approved->fetchAll(PDO::FETCH_ASSOC);

// Fetch Rejected (by VPAA)
$stmt_rejected = $conn->prepare("
    SELECT s.*,
           COALESCE(NULLIF(s.course_code,''),  c.course_code)  AS course_code,
           COALESCE(NULLIF(s.course_title,''), c.course_title) AS course_title,
           u.first_name, u.last_name,
           sw.comment as reject_reason,
           sw.action_at as rejected_at
    FROM syllabus s
    JOIN syllabus_workflow sw ON s.id = sw.syllabus_id
    JOIN roles r ON sw.role_id = r.id
    LEFT JOIN courses c ON s.course_id = c.id
    LEFT JOIN users u ON s.uploaded_by = u.id
    WHERE r.role_name = 'vpaa' AND sw.action = 'Rejected'
    ORDER BY sw.action_at DESC
");
$stmt_rejected->execute();
$rejected_rows = $stmt_rejected->fetchAll(PDO::FETCH_ASSOC);

$pending_count = count($pending_rows);
$unread_count = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
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
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .text-orange {
            color: #ff8800 !important;
        }

        .bg-orange {
            background-color: #ff8800 !important;
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
            <nav class="nav flex-column gap-2 mb-auto">
                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
                <a href="vpaa_dashboard.php" class="nav-link text-white p-3 rounded hover-effect">Dashboard</a>
                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
                <a href="syllabus_review.php" class="nav-link text-white active-nav-link p-3 rounded">
                    Syllabus Review
                    <?php if ($pending_count > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $pending_count ?></span>
                    <?php endif; ?>
                </a>
                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">ANALYTICS</div>
                <a href="compliance_reports.php" class="nav-link text-white p-3 rounded hover-effect">Compliance Reports</a>
                <a href="syllabus_vault.php" class="nav-link text-white p-3 rounded hover-effect">Syllabus Vault</a>
                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
                <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect">Profile</a>
                <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-grow-1 p-5" style="margin-left:260px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-orange font-serif fw-bold">Syllabus Review</h2>
                <div class="position-relative" style="cursor:pointer;" data-bs-toggle="dropdown">
                    <i class="bi bi-bell fs-4 text-secondary"></i>
                    <?php if ($unread_count > 0): ?><span class="notif-dot"></span><?php endif; ?>
                    <ul class="dropdown-menu dropdown-menu-end shadow"
                        style="width:320px;max-height:400px;overflow-y:auto;">
                        <li class="px-3 py-2 border-bottom"><strong>Notifications</strong></li>
                        <?php if (empty($notifications)): ?>
                            <li class="px-3 py-3 text-center text-muted small">No notifications</li>
                        <?php else:
                            foreach ($notifications as $n): ?>
                                <li class="px-3 py-2 border-bottom small"><?= htmlspecialchars($n['message']) ?></li>
                            <?php endforeach; endif; ?>
                    </ul>
                </div>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <div class="card premium-card p-4 shadow-sm">
                <ul class="nav nav-tabs nav-fill mb-4 border-bottom" id="syllabusTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active font-serif fw-bold text-orange" data-bs-toggle="tab"
                            data-bs-target="#tabPending">
                            Pending Reviews (<?= $pending_count ?>)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link font-serif fw-bold text-orange" data-bs-toggle="tab"
                            data-bs-target="#tabApproved">
                            Fully Approved
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link font-serif fw-bold text-orange" data-bs-toggle="tab"
                            data-bs-target="#tabDeclined">
                            Declined
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
                                        <th class="small">#</th>
                                        <th class="small">INSTRUCTOR</th>
                                        <th class="small">COURSE</th>
                                        <th class="small text-center">FILE</th>
                                        <th class="small">SUBMITTED</th>
                                        <th class="small text-center">ACTION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($pending_rows)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">No pending reviews.</td>
                                        </tr>
                                    <?php else:
                                        foreach ($pending_rows as $i => $sub): ?>
                                            <tr>
                                                <td><?= $i + 1 ?></td>
                                                <td>
                                                    <div class="fw-bold small">
                                                        <?= htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']) ?></div>
                                                    <div class="text-muted small">
                                                        <?= htmlspecialchars($sub['uploader_email']) ?></div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold small"><?= htmlspecialchars($sub['course_code']) ?>
                                                    </div>
                                                    <div class="text-muted small"><?= htmlspecialchars($sub['course_title']) ?>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($sub['file_path'])) ?>"
                                                        target="_blank" class="text-orange fs-5">
                                                        <i class="bi bi-file-earmark-pdf"></i>
                                                    </a>
                                                </td>
                                                <td class="small"><?= date('M d, Y', strtotime($sub['submitted_at'])) ?></td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-success rounded-pill px-3"
                                                        onclick="openReviewModal(<?= $sub['id'] ?>, 'approve')">Approve</button>
                                                    <button class="btn btn-sm btn-danger rounded-pill px-3"
                                                        onclick="openReviewModal(<?= $sub['id'] ?>, 'reject')">Reject</button>
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
                            <table class="table table-hover align-middle">
                                <thead class="table-light text-secondary small">
                                    <tr>
                                        <th>#</th>
                                        <th>INSTRUCTOR</th>
                                        <th>COURSE</th>
                                        <th>FILE</th>
                                        <th>APPROVED ON</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($approved_rows as $i => $row): ?>
                                        <tr>
                                            <td><?= $i + 1 ?></td>
                                            <td class="small fw-bold">
                                                <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                            <td class="small"><?= htmlspecialchars($row['course_code']) ?></td>
                                            <td class="text-center">
                                                <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($row['file_path'])) ?>"
                                                    target="_blank" class="text-orange"><i
                                                        class="bi bi-file-earmark-pdf"></i></a>
                                            </td>
                                            <td class="small"><?= date('M d, Y', strtotime($row['approved_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Declined -->
                    <div class="tab-pane fade" id="tabDeclined">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light text-secondary small">
                                    <tr>
                                        <th>#</th>
                                        <th>INSTRUCTOR</th>
                                        <th>COURSE</th>
                                        <th>REASON</th>
                                        <th>DECLINED ON</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rejected_rows as $i => $row): ?>
                                        <tr>
                                            <td><?= $i + 1 ?></td>
                                            <td class="small fw-bold">
                                                <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                            <td class="small"><?= htmlspecialchars($row['course_code']) ?></td>
                                            <td class="small"><?= htmlspecialchars($row['reject_reason'] ?? '—') ?></td>
                                            <td class="small"><?= date('M d, Y', strtotime($row['rejected_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" method="POST">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title font-serif fw-bold" id="modalTitle">Review Syllabus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <input type="hidden" name="syllabus_id" id="modalSyllabusId">
                    <input type="hidden" name="action" id="modalAction">
                    <div id="rejectCommentGroup">
                        <label class="form-label small fw-bold">Comments / Reason for Rejection</label>
                        <textarea name="comment" class="form-control" rows="3"
                            placeholder="Enter feedback..."></textarea>
                    </div>
                    <div id="approveNote" class="text-muted small">
                        Are you sure you want to grant final approval to this syllabus?
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-light rounded-pill px-4"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-orange rounded-pill px-4"
                        id="modalSubmitBtn">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const reviewModal = new bootstrap.Modal(document.getElementById('reviewModal'));
        function openReviewModal(id, action) {
            document.getElementById('modalSyllabusId').value = id;
            document.getElementById('modalAction').value = action;
            document.getElementById('modalTitle').innerText = action === 'approve' ? 'Final Approval' : 'Decline Syllabus';
            document.getElementById('rejectCommentGroup').style.display = action === 'reject' ? 'block' : 'none';
            document.getElementById('approveNote').style.display = action === 'approve' ? 'block' : 'none';
            document.getElementById('modalSubmitBtn').className = action === 'approve' ? 'btn btn-sm btn-success rounded-pill px-4' : 'btn btn-sm btn-danger rounded-pill px-4';
            reviewModal.show();
        }
    </script>
</body>

</html>