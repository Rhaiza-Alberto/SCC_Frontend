<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

restrict_to_role('dean');

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Dean / Admin';
$email = $_SESSION['email'] ?? '';
$role_display = "Dean's Panel";
$college_id = $_SESSION['college_id'] ?? null;

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
    $action = $_POST['action'] === 'approve' ? 'Approved' : 'Rejected';
    $comment = trim($_POST['comment'] ?? '') ?: null;
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

$total_count = (int) $conn->query("SELECT COUNT(*) FROM syllabus")->fetchColumn();
$approved_count = (int) $conn->query("SELECT COUNT(*) FROM syllabus WHERE status = 'Approved'")->fetchColumn();
$pending_count = (int) $conn->query("SELECT COUNT(*) FROM syllabus WHERE status = 'Pending'")->fetchColumn();
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
$total_users = (int) $conn->query("SELECT COUNT(*) FROM users WHERE is_deleted = 0")->fetchColumn();
$instructor_count = (int) $conn->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id=r.id WHERE r.role_name='faculty' AND u.is_deleted=0")->fetchColumn();
$dean_count = (int) $conn->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id=r.id WHERE r.role_name='dean' AND u.is_deleted=0")->fetchColumn();
$vpaa_count = (int) $conn->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id=r.id WHERE r.role_name='vpaa' AND u.is_deleted=0")->fetchColumn();

// ── Pending Syllabi for Dean Review ─────────────────────────────────────────
$pending_syllabi_stmt = $conn->prepare("
    SELECT s.*,
           COALESCE(NULLIF(s.course_code,''),  c.course_code)  AS course_code,
           COALESCE(NULLIF(s.course_title,''), c.course_title) AS course_title,
           u.first_name, u.last_name, u.email AS uploader_email,
           sw.id AS workflow_id,
           -- Status tracking subqueries
           (SELECT r2.role_name FROM syllabus_workflow sw3 JOIN roles r2 ON sw3.role_id = r2.id WHERE sw3.syllabus_id = s.id AND sw3.action = 'Pending' ORDER BY sw3.step_order ASC LIMIT 1) AS current_stage_role,
           (SELECT r3.role_name FROM syllabus_workflow sw4 JOIN roles r3 ON sw4.role_id = r3.id WHERE sw4.syllabus_id = s.id AND sw4.action = 'Rejected' ORDER BY sw4.action_at DESC LIMIT 1) AS rejecting_role
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
           col.college_name,
           -- Status tracking subqueries
           (SELECT r2.role_name FROM syllabus_workflow sw3 JOIN roles r2 ON sw3.role_id = r2.id WHERE sw3.syllabus_id = s.id AND sw3.action = 'Pending' ORDER BY sw3.step_order ASC LIMIT 1) AS current_stage_role,
           (SELECT r3.role_name FROM syllabus_workflow sw4 JOIN roles r3 ON sw4.role_id = r3.id WHERE sw4.syllabus_id = s.id AND sw4.action = 'Rejected' ORDER BY sw4.action_at DESC LIMIT 1) AS rejecting_role
    FROM syllabus s
    LEFT JOIN courses c     ON s.course_id      = c.id
    LEFT JOIN users u       ON s.uploaded_by    = u.id
    LEFT JOIN colleges col ON COALESCE(c.college_id, u.college_id) = col.id
    ORDER BY s.submitted_at DESC
");
$all_stmt->execute();
$all_submissions = $all_stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Monthly Submission Trends (Last 6 Months) ────────────────────────────────
$monthly_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $month_name = date('M', strtotime("-$i months"));
    $stmt = $conn->prepare("SELECT COUNT(*) FROM syllabus WHERE DATE_FORMAT(submitted_at, '%Y-%m') = ?");
    $stmt->execute([$month]);
    $monthly_data[] = ['month_name' => $month_name, 'count' => (int) $stmt->fetchColumn()];
}

// ── Recently Reviewed Syllabi (By Dean) ──────────────────────────────────────
$recently_reviewed_stmt = $conn->prepare("
    SELECT s.*, 
           COALESCE(NULLIF(s.course_code,''),  c.course_code)  AS course_code,
           u.first_name, u.last_name,
           sw.action, sw.action_at
    FROM syllabus_workflow sw
    JOIN syllabus s ON sw.syllabus_id = s.id
    JOIN roles r    ON sw.role_id     = r.id
    JOIN users u    ON s.uploaded_by  = u.id
    LEFT JOIN courses c ON s.course_id = c.id
    WHERE r.role_name = 'dean' AND sw.action IN ('Approved', 'Rejected')
    ORDER BY sw.action_at DESC
    LIMIT 5
");
$recently_reviewed_stmt->execute();
$recently_reviewed = $recently_reviewed_stmt->fetchAll(PDO::FETCH_ASSOC);

$unread_count = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dean's Panel — SCC Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/theme.js"></script>
</head>

<body>
    <?php $active_page = 'dashboard'; include '_sidebar.php'; ?>

    <main class="scc-main">
        <div class="container-fluid p-0">
            <!-- Header Section -->
            <div class="scc-page-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1" style="color:var(--text)">Dean's <span style="color:var(--primary)">Dashboard</span></h4>
                    <p class="text-muted small mb-0"><?= get_current_school_year() ?> — Faculty & Syllabus Overview</p>
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
                    <button class="btn btn-sm d-flex align-items-center gap-1" style="color:var(--text);border:1px solid var(--border);border-radius:var(--radius-sm);padding:0.4rem 0.8rem;background:var(--bg-card)" onclick="window.print()"><i class="bi bi-printer"></i></button>
                </div>
            </div>

            <!-- Priority Alert -->
            <?php if ($pending_review_count > 0 || $reg_count > 0): ?>
                <div class="alert border-0 shadow-sm mb-4 d-flex align-items-center p-3 rounded-4 animate-in" style="background: var(--danger-light); border-left: 5px solid var(--danger) !important;">
                    <div class="bg-danger text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center shadow-sm" style="width:45px;height:45px;">
                        <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="fw-bold mb-1 text-dark">Administrative Action Required</h6>
                        <p class="mb-0 text-muted small">
                            <?php if ($pending_review_count > 0 && $reg_count > 0): ?>
                                You have <strong><?= $pending_review_count ?></strong> syllabus submission(s) and <strong><?= $reg_count ?></strong> registration request(s) awaiting your action.
                            <?php elseif ($pending_review_count > 0): ?>
                                You have <strong><?= $pending_review_count ?></strong> syllabus submission(s) awaiting your review.
                            <?php else: ?>
                                You have <strong><?= $reg_count ?></strong> faculty registration request(s) awaiting your approval.
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="d-flex gap-2 ms-3">
                        <?php if ($pending_review_count > 0): ?>
                            <a href="syllabus_review.php" class="btn btn-danger rounded-pill px-4 py-2 fw-bold small shadow-sm">Review Now</a>
                        <?php endif; ?>
                        <?php if ($reg_count > 0): ?>
                            <a href="registration_requests.php" class="btn btn-outline-danger rounded-pill px-4 py-2 fw-bold small shadow-sm">View Requests</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Metrics Grid -->
            <div class="row g-3 mb-4">
                <?php 
                $dean_metrics = [
                    ['label' => 'Total Syllabi', 'value' => $total_count, 'icon' => 'bi-files', 'color' => 'var(--primary)', 'bg' => 'var(--primary-light)'],
                    ['label' => 'Pending Review', 'value' => $pending_review_count, 'icon' => 'bi-clock-history', 'color' => 'var(--warning)', 'bg' => 'var(--warning-light)'],
                    ['label' => 'Approved', 'value' => $approved_count, 'icon' => 'bi-check2-circle', 'color' => 'var(--success)', 'bg' => 'var(--success-light)'],
                    ['label' => 'Revisions', 'value' => $rejected_count, 'icon' => 'bi-arrow-repeat', 'color' => 'var(--danger)', 'bg' => 'var(--danger-light)'],
                    ['label' => 'Total Faculty', 'value' => $instructor_count, 'icon' => 'bi-people', 'color' => '#6366f1', 'bg' => 'rgba(99, 102, 241, 0.1)']
                ];
                foreach($dean_metrics as $i => $m): ?>
                    <div class="col-xl col-md-4 col-sm-6">
                        <div class="scc-card p-3 h-100 animate-in" style="animation-delay: <?= $i * 0.05 ?>s">
                            <div class="d-flex align-items-center gap-3">
                                <div style="width:42px;height:42px;border-radius:50%;background:<?= $m['bg'] ?>;color:<?= $m['color'] ?>;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0">
                                    <i class="bi <?= $m['icon'] ?>"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <div class="text-muted small fw-bold text-uppercase text-truncate" style="font-size:0.6rem;letter-spacing:0.5px"><?= $m['label'] ?></div>
                                    <div class="fs-5 fw-bold" style="color:var(--text)"><?= $m['value'] ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Analytics Visuals & Activity -->
            <div class="row g-4 mb-4">
                <div class="col-xl-8">
                    <div class="scc-card p-4 h-100 animate-in">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h6 class="fw-bold mb-0">Submission <span class="text-orange">Trends</span></h6>
                            <span class="badge bg-light text-dark border fw-normal py-1 px-3" style="font-size:0.7rem">Last 6 Months</span>
                        </div>
                        <div class="d-flex align-items-end justify-content-between px-3" style="height: 200px; padding-top: 20px;">
                            <?php foreach ($monthly_data as $m): 
                                $max_val = max(array_column($monthly_data, 'count')) ?: 1;
                                $h = ($m['count'] / $max_val) * 100; ?>
                                <div class="text-center d-flex flex-column align-items-center flex-grow-1 h-100 justify-content-end">
                                    <div class="bg-primary opacity-75 rounded-top" style="width: 35px; height: <?= max($h, 5) ?>%; transition: height 1s ease-out;" title="<?= $m['count'] ?> submissions"></div>
                                    <div class="mt-2 small fw-bold text-muted" style="font-size: 0.75rem;"><?= $m['month_name'] ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="scc-card p-4 h-100 animate-in">
                        <h6 class="fw-bold mb-4">Recently <span class="text-orange">Reviewed</span></h6>
                        <div class="activity-feed">
                            <?php if (empty($recently_reviewed)): ?>
                                <div class="text-center py-5 text-muted small">No recent review activity</div>
                            <?php else: foreach ($recently_reviewed as $rev): ?>
                                <div class="d-flex gap-3 mb-4 last-child-mb-0">
                                    <div class="flex-shrink-0">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;background:<?= $rev['action'] === 'Approved' ? 'var(--success-light)' : 'var(--danger-light)' ?>;color:<?= $rev['action'] === 'Approved' ? 'var(--success)' : 'var(--danger)' ?>">
                                            <i class="bi <?= $rev['action'] === 'Approved' ? 'bi-check-lg' : 'bi-x-lg' ?>"></i>
                                        </div>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="fw-bold text-dark small text-truncate"><?= htmlspecialchars($rev['course_code']) ?></div>
                                        <div class="text-muted small text-truncate" style="font-size:0.7rem"><?= htmlspecialchars($rev['course_title']) ?></div>
                                        <div class="text-muted" style="font-size:0.65rem"><?= date('M d, Y h:i A', strtotime($rev['action_at'])) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Tables -->
            <div class="scc-card mb-4 animate-in">
                <div class="card-header border-0 bg-transparent p-4 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">Pending <span class="text-orange">Review</span></h6>
                    <a href="syllabus_review.php" class="btn btn-sm btn-link text-decoration-none small p-0 fw-bold">View All Pending</a>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="scc-table">
                            <thead>
                                <tr class="text-secondary small">
                                    <th>INSTRUCTOR</th>
                                    <th>COURSE INFORMATION</th>
                                    <th>SUBMITTED</th>
                                    <th class="text-end">ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $visible_pending = array_slice($pending_syllabi, 0, 5);
                                if (empty($pending_syllabi)): ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted small">No pending reviews found</td></tr>
                                <?php else: foreach ($visible_pending as $s): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark small"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></div>
                                            <div class="text-muted" style="font-size: 0.65rem;"><?= htmlspecialchars($s['uploader_email']) ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark small"><?= htmlspecialchars($s['course_code']) ?></div>
                                            <div class="text-muted text-truncate" style="font-size: 0.65rem; max-width: 300px;"><?= htmlspecialchars($s['course_title']) ?></div>
                                        </td>
                                        <td class="small text-muted"><?= date('M d, Y', strtotime($s['submitted_at'])) ?></td>
                                        <td class="text-end text-nowrap">
                                            <button type="button" onclick="showTrackerModal(<?= (int)$s['id'] ?>, '<?= htmlspecialchars($s['course_code'], ENT_QUOTES) ?>', '<?= htmlspecialchars($s['course_title'], ENT_QUOTES) ?>')" class="btn btn-sm btn-light border px-2 py-1 text-primary shadow-sm" title="Track Progress"><i class="bi bi-geo-alt"></i></button>
                                            <a href="syllabus_review.php?status=Pending" class="btn btn-sm btn-primary-scc px-3 py-1 small">Review</a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-xl-6">
                    <div class="scc-card h-100 animate-in">
                        <div class="card-header border-0 bg-transparent p-4 pb-0 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0">My <span class="text-orange">Submissions</span></h6>
                            <a href="my_submissions.php" class="btn btn-sm btn-link text-decoration-none small p-0 fw-bold">View All</a>
                        </div>
                        <div class="card-body p-4">
                            <div class="table-responsive">
                                <table class="scc-table">
                                    <thead>
                                        <tr class="text-secondary small">
                                            <th>COURSE</th>
                                            <th>STATUS</th>
                                            <th class="text-end">ACTION</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $visible_my = array_slice($my_submissions, 0, 5);
                                        if (empty($my_submissions)): ?>
                                            <tr><td colspan="3" class="text-center py-4 text-muted small">No submissions found</td></tr>
                                        <?php else: foreach ($visible_my as $s): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-dark small"><?= htmlspecialchars($s['course_code']) ?></div>
                                                    <div class="text-muted small" style="font-size: 0.65rem"><?= htmlspecialchars($s['course_title']) ?></div>
                                                </td>
                                                <td><?= format_syllabus_status($s['status'], $s['current_stage_role'] ?? null, $s['rejecting_role'] ?? null, $s['rejecting_name'] ?? null) ?></td>
                                                <td class="text-end text-nowrap">
                                                    <button type="button" onclick="showTrackerModal(<?= (int)$s['id'] ?>, '<?= htmlspecialchars($s['course_code'], ENT_QUOTES) ?>', '<?= htmlspecialchars($s['course_title'], ENT_QUOTES) ?>')" class="btn btn-sm btn-light border px-2 py-1 text-primary shadow-sm" title="Track Progress"><i class="bi bi-geo-alt"></i></button>
                                                    <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($s['file_path'])) ?>" target="_blank" class="btn btn-sm btn-light border px-2 py-1 shadow-sm"><i class="bi bi-eye"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="scc-card h-100 animate-in">
                        <div class="card-header border-0 bg-transparent p-4 pb-0 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0">Institutional <span class="text-orange">Activity</span></h6>
                            <a href="shared_syllabus.php" class="btn btn-sm btn-link text-decoration-none small p-0 fw-bold">Explore All</a>
                        </div>
                        <div class="card-body p-4">
                            <div class="table-responsive">
                                <table class="scc-table">
                                    <thead>
                                        <tr class="text-secondary small">
                                            <th>UPLOADER</th>
                                            <th>COURSE</th>
                                            <th class="text-end">DATE</th>
                                            <th class="text-end">ACTION</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $visible_all = array_slice($all_submissions, 0, 5);
                                        if (empty($all_submissions)): ?>
                                            <tr><td colspan="3" class="text-center py-4 text-muted small">No activity found</td></tr>
                                        <?php else: foreach ($visible_all as $s): ?>
                                            <tr>
                                                <td><div class="fw-bold text-dark small"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></div></td>
                                                <td>
                                                    <div class="small text-muted fw-bold"><?= htmlspecialchars($s['course_code']) ?></div>
                                                </td>
                                                <td class="text-end small text-muted"><?= date('M d, Y', strtotime($s['submitted_at'])) ?></td>
                                                <td class="text-end">
                                                    <button type="button" onclick="showTrackerModal(<?= (int)$s['id'] ?>, '<?= htmlspecialchars($s['course_code'], ENT_QUOTES) ?>', '<?= htmlspecialchars($s['course_title'] ?? '', ENT_QUOTES) ?>')" class="btn btn-sm btn-light border px-2 py-1 text-primary shadow-sm" title="Track Progress"><i class="bi bi-geo-alt"></i></button>
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

    <!-- Hidden POST form for SweetAlert -->
    <form id="reviewForm" method="POST" action="admin_dashboard.php">
        <input type="hidden" name="syllabus_id" id="formSyllabusId">
        <input type="hidden" name="action" id="formAction">
        <input type="hidden" name="comment" id="formComment">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/common.js"></script>
    <script>
        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('open'); document.getElementById('sidebarOverlay').classList.toggle('active'); }
        function handleReview(action, syllabusId, courseCode) {
            if (action === 'approve') {
                Swal.fire({
                    title: 'Approve Syllabus?',
                    html: `Approve <strong>${courseCode}</strong>?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--success)',
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
                    confirmButtonColor: 'var(--danger)',
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
    <?php include __DIR__ . '/../_tracker_modal.php'; ?>
</body>

</html>