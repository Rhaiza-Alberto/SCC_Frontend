<?php
/**
 * my_submissions.php (Admin)
 * Shows all admin syllabus submissions from the database.
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
$role_display = "Dean's Panel";

if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: my_submissions.php');
    exit();
}

$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

$all = get_faculty_submissions($user_id);

$pending  = array_values(array_filter($all, fn($s) => $s['status'] === 'Pending'));
$approved = array_values(array_filter($all, fn($s) => $s['status'] === 'Approved'));
$rejected = array_values(array_filter($all, fn($s) => $s['status'] === 'Rejected'));

$unread_count  = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);

// Sidebar counts
$conn = get_db();
$pending_review_count = (int) $conn->query("
    SELECT COUNT(DISTINCT sw.syllabus_id)
    FROM syllabus_workflow sw
    JOIN roles r ON sw.role_id = r.id
    WHERE r.role_name = 'dean' AND sw.action = 'Pending'
")->fetchColumn();

$reg_count = (int) $conn->query("SELECT COUNT(*) FROM users WHERE is_approved = 0 AND is_deleted = 0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submissions — SCC Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php $active_page = 'submissions'; include '_sidebar.php'; ?>

    <main class="scc-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color:var(--text)">My <span style="color:var(--primary)">Submissions</span></h4>
                <p style="font-size:0.85rem;color:var(--text-secondary);margin:0">Track your personal syllabus submission progress</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <div class="position-relative" style="cursor:pointer" data-bs-toggle="dropdown">
                        <i class="bi bi-bell fs-5" style="color:var(--text)"></i>
                        <?php if ($unread_count > 0): ?><span class="notif-badge"><?= $unread_count > 9 ? '9+' : $unread_count ?></span><?php endif; ?>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="width:340px;max-height:420px;overflow-y:auto;border-radius:var(--radius-md);background:var(--bg-card)">
                        <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom sticky-top" style="background:var(--bg-card)">
                            <strong style="font-size:0.9rem;color:var(--text)">Notifications</strong>
                            <?php if ($unread_count > 0): ?><a href="?mark_read=1" class="text-decoration-none small" style="color:var(--primary)">Mark all read</a><?php endif; ?>
                        </li>
                        <?php if (empty($notifications)): ?>
                            <li class="px-3 py-4 text-center" style="color:var(--text-muted)"><i class="bi bi-bell-slash fs-4 d-block mb-2 opacity-50"></i><span class="small">No notifications</span></li>
                        <?php else: foreach ($notifications as $n): $color = get_notification_color($n['message']); ?>
                            <li class="border-bottom" style="<?= !$n['is_read'] ? 'background:var(--primary-light)' : '' ?>">
                                <a href="notifications.php?notif_id=<?= $n['id'] ?>" class="text-decoration-none d-block px-3 py-2">
                                    <p class="mb-0 small" style="color:var(--text)"><span class="<?= $color['text'] ?> fw-bold me-1"><?= $color['icon'] ?></span><?= htmlspecialchars($n['message']) ?></p>
                                    <span style="font-size:.7rem;color:var(--text-muted)"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></span>
                                </a>
                            </li>
                        <?php endforeach; endif; ?>
                        <li style="background:var(--bg-card);border-top:1px solid var(--border)"><a href="notifications.php" class="d-block text-center text-decoration-none small fw-bold py-2" style="color:var(--primary)">View all notifications</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success border-0 shadow-sm mb-4 d-flex align-items-center animate-in" style="border-radius:var(--radius-md)">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="scc-card animate-in">
            <div class="card-body p-0">
                <ul class="nav nav-tabs nav-fill border-bottom px-3 pt-3" id="submissionTabs" role="tablist" style="background:var(--bg-subtle)">
                    <li class="nav-item">
                        <button class="nav-link active fw-bold py-3" data-bs-toggle="tab" data-bs-target="#tabPending" type="button" style="color:var(--text); border-bottom: 2px solid transparent;">
                            <i class="bi bi-clock-history me-2 text-warning"></i> Pending Review
                            <?php if (count($pending) > 0): ?><span class="badge bg-warning text-dark ms-1"><?= count($pending) ?></span><?php endif; ?>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-bold py-3" data-bs-toggle="tab" data-bs-target="#tabApproved" type="button" style="color:var(--text); border-bottom: 2px solid transparent;">
                            <i class="bi bi-check-circle me-2 text-success"></i> Approved
                            <?php if (count($approved) > 0): ?><span class="badge bg-success ms-1"><?= count($approved) ?></span><?php endif; ?>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-bold py-3" data-bs-toggle="tab" data-bs-target="#tabDeclined" type="button" style="color:var(--text); border-bottom: 2px solid transparent;">
                            <i class="bi bi-x-circle me-2 text-danger"></i> Declined
                            <?php if (count($rejected) > 0): ?><span class="badge bg-danger ms-1"><?= count($rejected) ?></span><?php endif; ?>
                        </button>
                    </li>
                </ul>

                <div class="tab-content p-4">
                    <!-- Pending Tab -->
                    <div class="tab-pane fade show active" id="tabPending">
                        <div class="table-responsive">
                            <table class="scc-table">
                                <thead>
                                    <tr>
                                        <th style="width: 50px">ID</th>
                                        <th>COURSE</th>
                                        <th>YEAR</th>
                                        <th>STATUS</th>
                                        <th class="text-center">FILE</th>
                                        <th>SUBMITTED</th>
                                        <th class="text-center">ACTION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($pending)): ?>
                                        <tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-inbox fs-2 d-block opacity-25 mb-2"></i>No pending submissions.</td></tr>
                                    <?php else: foreach ($pending as $sub): ?>
                                        <tr>
                                            <td class="small text-muted fw-bold"><?= $sub['id'] ?></td>
                                            <td>
                                                <div class="fw-bold small" style="color:var(--text)"><?= htmlspecialchars($sub['course_code']) ?></div>
                                                <div class="text-muted" style="font-size:0.7rem;"><?= htmlspecialchars($sub['course_title']) ?></div>
                                            </td>
                                            <td class="small" style="color:var(--text-secondary)"><?= htmlspecialchars($sub['school_year'] ?? '—') ?></td>
                                            <td><?= format_syllabus_status($sub['status'], $sub['current_stage_role'] ?? null, $sub['rejecting_role'] ?? null, $sub['rejecting_name'] ?? null) ?></td>
                                            <td class="text-center">
                                                <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($sub['file_path'])) ?>" target="_blank" class="btn btn-sm btn-light border" title="Preview PDF">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                            <td class="small" style="color:var(--text-secondary)"><?= date('M d, Y', strtotime($sub['submitted_at'])) ?></td>
                                            <td class="text-center">
                                                <a href="../faculty/edit_syllabus.php?id=<?= $sub['id'] ?>" class="btn btn-sm btn-light border fw-bold text-primary px-3">Edit</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Approved Tab -->
                    <div class="tab-pane fade" id="tabApproved">
                        <div class="table-responsive">
                            <table class="scc-table">
                                <thead>
                                    <tr>
                                        <th style="width: 50px">ID</th>
                                        <th>COURSE</th>
                                        <th>YEAR</th>
                                        <th>STATUS</th>
                                        <th>REVIEWER</th>
                                        <th class="text-center">FILE</th>
                                        <th>SUBMITTED</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($approved)): ?>
                                        <tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-check2-all fs-2 d-block opacity-25 mb-2"></i>No approved submissions yet.</td></tr>
                                    <?php else: foreach ($approved as $sub): ?>
                                        <tr>
                                            <td class="small text-muted fw-bold"><?= $sub['id'] ?></td>
                                            <td>
                                                <div class="fw-bold small" style="color:var(--text)"><?= htmlspecialchars($sub['course_code']) ?></div>
                                                <div class="text-muted" style="font-size:0.7rem;"><?= htmlspecialchars($sub['course_title']) ?></div>
                                            </td>
                                            <td class="small" style="color:var(--text-secondary)"><?= htmlspecialchars($sub['school_year'] ?? '—') ?></td>
                                            <td><?= format_syllabus_status($sub['status'], null, $sub['rejecting_role'] ?? null, $sub['rejecting_name'] ?? null) ?></td>
                                            <td class="small" style="color:var(--text-secondary)"><?= htmlspecialchars($sub['last_reviewer'] ?? '—') ?></td>
                                            <td class="text-center">
                                                <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($sub['file_path'])) ?>" target="_blank" class="btn btn-sm btn-light border" title="Preview PDF">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                            <td class="small" style="color:var(--text-secondary)"><?= date('M d, Y', strtotime($sub['submitted_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Declined Tab -->
                    <div class="tab-pane fade" id="tabDeclined">
                        <div class="table-responsive">
                            <table class="scc-table">
                                <thead>
                                    <tr>
                                        <th style="width: 50px">ID</th>
                                        <th>COURSE</th>
                                        <th>YEAR</th>
                                        <th>STATUS</th>
                                        <th>REASON</th>
                                        <th class="text-center">FILE</th>
                                        <th>SUBMITTED</th>
                                        <th class="text-center">ACTION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($rejected)): ?>
                                        <tr><td colspan="8" class="text-center text-muted py-5"><i class="bi bi-x-circle fs-2 d-block opacity-25 mb-2"></i>No declined submissions.</td></tr>
                                    <?php else: foreach ($rejected as $sub): ?>
                                        <tr>
                                            <td class="small text-muted fw-bold"><?= $sub['id'] ?></td>
                                            <td>
                                                <div class="fw-bold small" style="color:var(--text)"><?= htmlspecialchars($sub['course_code']) ?></div>
                                                <div class="text-muted" style="font-size:0.7rem;"><?= htmlspecialchars($sub['course_title']) ?></div>
                                            </td>
                                            <td class="small" style="color:var(--text-secondary)"><?= htmlspecialchars($sub['school_year'] ?? '—') ?></td>
                                            <td><?= format_syllabus_status($sub['status'], null, $sub['rejecting_role'] ?? null, $sub['rejecting_name'] ?? null) ?></td>
                                            <td class="small" style="color:var(--text-secondary)"><?= htmlspecialchars($sub['reject_comment'] ?? 'No reason provided') ?></td>
                                            <td class="text-center">
                                                <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($sub['file_path'])) ?>" target="_blank" class="btn btn-sm btn-light border" title="Preview PDF">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                            <td class="small" style="color:var(--text-secondary)"><?= date('M d, Y', strtotime($sub['submitted_at'])) ?></td>
                                            <td class="text-center">
                                                <div class="d-flex flex-column gap-1">
                                                    <a href="../faculty/edit_syllabus.php?id=<?= $sub['id'] ?>" class="btn btn-sm btn-light border fw-bold text-primary">Edit</a>
                                                    <a href="upload_syllabus.php?resubmit=<?= $sub['id'] ?>" class="btn btn-sm btn-light border fw-bold text-danger">Resubmit</a>
                                                </div>
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
    </main>
    </div><!-- /main-content -->
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/common.js"></script>
    <script>
        function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('active');}
    </script>
</body>
</html>