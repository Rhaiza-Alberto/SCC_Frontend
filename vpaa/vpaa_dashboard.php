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
$username     = $_SESSION['username'] ?? 'VPAA';
$role_display = 'VPAA Institutional Hub';

if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: vpaa_dashboard.php');
    exit();
}

$conn = get_db();

// ── Stats ────────────────────────────────────────────────────────────────────
$total_count    = (int) $conn->query("SELECT COUNT(*) FROM syllabus")->fetchColumn();
$approved_count = (int) $conn->query("SELECT COUNT(*) FROM syllabus WHERE status = 'Approved'")->fetchColumn();
$pending_count  = (int) $conn->query("SELECT COUNT(*) FROM syllabus WHERE status = 'Pending'")->fetchColumn();
$rejected_count = (int) $conn->query("SELECT COUNT(*) FROM syllabus WHERE status = 'Rejected'")->fetchColumn();

// Syllabi waiting for VPAA final approval
$vpaa_pending_count = (int) $conn->query("
    SELECT COUNT(DISTINCT sw.syllabus_id)
    FROM syllabus_workflow sw
    JOIN roles r ON sw.role_id = r.id
    WHERE r.role_name = 'vpaa' AND sw.action = 'Pending'
")->fetchColumn();

// ── Department compliance breakdown ─────────────────────────────────────────
$dept_rows = $conn->query("
    SELECT d.department_name,
           COUNT(DISTINCT s.id)                                      AS total,
           SUM(CASE WHEN s.status = 'Approved' THEN 1 ELSE 0 END)   AS approved
    FROM syllabus s
    JOIN users u       ON s.uploaded_by    = u.id
    LEFT JOIN courses c ON s.course_id     = c.id
    JOIN departments d  ON COALESCE(c.department_id, u.department_id) = d.id
    GROUP BY d.id, d.department_name
    ORDER BY d.department_name
")->fetchAll(PDO::FETCH_ASSOC);

$compliance_pct = $total_count > 0 ? round(($approved_count / $total_count) * 100) : 0;

// ── Recent all-submissions (last 10) ─────────────────────────────────────────
$recent_stmt = $conn->prepare("
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
    LIMIT 10
");
$recent_stmt->execute();
$recent_submissions = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

$unread_count  = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VPAA Dashboard - SCC-CCS Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .text-orange  { color: #ff8800 !important; }
        .btn-orange   { background-color: #ff8800 !important; color: #fff !important; border: none; }
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
            <a href="vpaa_dashboard.php"     class="nav-link text-white active-nav-link p-3 rounded">Dashboard</a>
            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
            <a href="syllabus_review.php"    class="nav-link text-white p-3 rounded hover-effect">
                Syllabus Review
                <?php if ($vpaa_pending_count > 0): ?>
                    <span class="badge bg-danger ms-1"><?= $vpaa_pending_count ?></span>
                <?php endif; ?>
            </a>
            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">ANALYTICS</div>
            <a href="compliance_reports.php" class="nav-link text-white p-3 rounded hover-effect">Compliance Reports</a>
            <a href="syllabus_vault.php"     class="nav-link text-white p-3 rounded hover-effect">Syllabus Vault</a>
            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
            <a href="profile.php"            class="nav-link text-white p-3 rounded hover-effect">Profile</a>
            <a href="../logout.php"          class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 p-5" style="margin-left:260px;">

        <div class="d-flex justify-content-between align-items-center mb-5">
            <h2 class="text-orange font-serif fw-bold">Welcome, <?= htmlspecialchars($username) ?>!</h2>
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
                
                <li class="border-bottom <?= !$n['is_read'] ? 'bg-light' : '' ?>">
                    <a href="notifications.php?notif_id=<?= $n['id'] ?>" class="text-decoration-none text-dark d-block px-3 py-2">
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
                    </a>
                </li>

            <?php endforeach; ?>

        <?php endif; ?>
        <a href="notifications.php" 
   class="d-block text-center text-orange text-decoration-none small fw-bold py-2">
    View all notifications
</a>
    </ul>
</div>
            </div>
        <!-- Stat Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card shadow-sm border-0 bg-white p-4 text-center border-start border-orange border-4" style="--bs-border-opacity:.99;">
                    <h6 class="text-uppercase fw-bold text-muted small mb-3">Total Submissions</h6>
                    <h1 class="display-4 fw-bold text-dark mb-0"><?= $total_count ?></h1>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm border-0 bg-white p-4 text-center border-start border-success border-4">
                    <h6 class="text-uppercase fw-bold text-success small mb-3">Fully Approved</h6>
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

        <!-- Compliance + Department Readiness -->
        <div class="row g-4 mb-5">
            <!-- Donut -->
            <div class="col-md-4">
                <div class="card premium-card p-4 shadow-sm h-100 border-0 bg-white">
                    <h5 class="card-title font-serif fw-bold mb-4 text-orange">CCS Compliance Overview</h5>
                    <div class="text-center py-3">
                        <div class="position-relative d-inline-block">
                            <svg width="120" height="120" viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="54" fill="none" stroke="#f3f3f3" stroke-width="12"/>
                                <circle cx="60" cy="60" r="54" fill="none" stroke="#ff8800" stroke-width="12"
                                    stroke-dasharray="339.292"
                                    stroke-dashoffset="<?= 339.292 * (1 - $compliance_pct / 100) ?>"
                                    transform="rotate(-90 60 60)"/>
                            </svg>
                            <div class="position-absolute top-50 start-50 translate-middle text-center">
                                <h2 class="mb-0 fw-bold"><?= $compliance_pct ?>%</h2>
                                <span class="text-muted small">Validated</span>
                            </div>
                        </div>
                        <p class="mt-4 text-muted small fw-bold">Overall CCS Readiness</p>
                        <div class="d-flex justify-content-between mt-2 px-3">
                            <div class="text-start">
                                <span class="text-secondary small d-block">Total</span>
                                <span class="fw-bold"><?= $total_count ?></span>
                            </div>
                            <div class="text-end">
                                <span class="text-secondary small d-block">Approved</span>
                                <span class="fw-bold text-success"><?= $approved_count ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Department Readiness -->
            <div class="col-md-8">
                <div class="card premium-card p-4 shadow-sm h-100 border-0 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title font-serif fw-bold mb-0 text-orange">Department Readiness</h5>
                        <a href="compliance_reports.php" class="btn btn-sm btn-outline-warning rounded-pill px-3">Full Report</a>
                    </div>
                    <?php if (empty($dept_rows)): ?>
                        <p class="text-muted small">No department data yet.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-sm border-0">
                            <thead class="border-bottom">
                                <tr>
                                    <th class="text-muted small py-2">DEPARTMENT</th>
                                    <th class="text-muted small py-2">PROGRESS</th>
                                    <th class="text-muted small py-2 text-center">APPROVED / TOTAL</th>
                                    <th class="text-muted small py-2 text-end">STATUS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dept_rows as $dept):
                                    $pct = $dept['total'] > 0 ? round(($dept['approved'] / $dept['total']) * 100) : 0;
                                    [$bar_class, $badge_class, $badge_label] = $pct >= 80
                                        ? ['bg-success', 'bg-success-subtle text-success border border-success border-opacity-25', 'High']
                                        : ($pct >= 50
                                            ? ['bg-warning', 'bg-warning-subtle text-warning border border-warning border-opacity-25', 'Average']
                                            : ['bg-danger',  'bg-danger-subtle text-danger border border-danger border-opacity-25', 'Critical']);
                                ?>
                                <tr>
                                    <td class="py-3 fw-bold small"><?= htmlspecialchars($dept['department_name']) ?></td>
                                    <td class="py-3">
                                        <div class="progress" style="height:8px;width:120px;">
                                            <div class="progress-bar <?= $bar_class ?>" style="width:<?= $pct ?>%"></div>
                                        </div>
                                        <span class="text-muted" style="font-size:.7rem;"><?= $pct ?>%</span>
                                    </td>
                                    <td class="py-3 text-center small"><?= $dept['approved'] ?> / <?= $dept['total'] ?></td>
                                    <td class="py-3 text-end">
                                        <span class="badge rounded-pill px-3 <?= $badge_class ?>"><?= $badge_label ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Access -->
        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="card premium-card p-4 shadow-sm border-0 bg-white border-start border-orange border-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-opacity-10 p-3 rounded-circle me-3" style="background:rgba(255,136,0,.1);">
                            <i class="bi bi-file-earmark-check text-orange fs-3"></i>
                        </div>
                        <div>
                            <h5 class="font-serif fw-bold mb-1">Syllabus Review Queue</h5>
                            <p class="text-muted small mb-0">
                                <?= $vpaa_pending_count > 0
                                    ? "<span class='text-warning fw-bold'>{$vpaa_pending_count} submission(s)</span> awaiting your final approval."
                                    : "No submissions awaiting final approval." ?>
                            </p>
                        </div>
                    </div>
                    <a href="syllabus_review.php" class="btn btn-orange w-100 rounded-pill mt-2">Go to Review Queue</a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card premium-card p-4 shadow-sm border-0 bg-white border-start border-dark border-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-dark bg-opacity-10 p-3 rounded-circle me-3">
                            <i class="bi bi-safe2 text-dark fs-3"></i>
                        </div>
                        <div>
                            <h5 class="font-serif fw-bold mb-1">Accreditation Vault</h5>
                            <p class="text-muted small mb-0">Access <?= $approved_count ?> approved syllabus file(s) for audit.</p>
                        </div>
                    </div>
                    <a href="syllabus_vault.php" class="btn btn-outline-dark w-100 rounded-pill mt-2">Explore Repository</a>
                </div>
            </div>
        </div>

        <!-- Recent Submissions Table -->
        <div class="card premium-card p-4 shadow-sm border-0">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title font-serif fw-bold mb-0 text-orange">Recent Submissions</h5>
                <a href="syllabus_review.php" class="btn btn-sm btn-outline-warning rounded-pill px-3">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-secondary small">#</th>
                            <th class="text-secondary small">UPLOADER</th>
                            <th class="text-secondary small">DEPT</th>
                            <th class="text-secondary small">COURSE</th>
                            <th class="text-secondary small">STATUS</th>
                            <th class="text-secondary small">SUBMITTED</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_submissions)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No submissions yet</td></tr>
                        <?php else: foreach ($recent_submissions as $i => $s):
                            $badge = match($s['status']) {
                                'Approved' => 'bg-success',
                                'Rejected' => 'bg-danger',
                                default    => 'bg-warning text-dark',
                            };
                        ?>
                            <tr>
                                <td class="small"><?= $i + 1 ?></td>
                                <td>
                                    <div class="fw-bold small"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></div>
                                    <div class="text-muted" style="font-size:.7rem;"><?= htmlspecialchars($s['uploader_email']) ?></div>
                                </td>
                                <td class="small"><?= htmlspecialchars($s['department_name'] ?? '—') ?></td>
                                <td>
                                    <div class="fw-bold small"><?= htmlspecialchars($s['course_code']) ?></div>
                                    <div class="text-muted" style="font-size:.7rem;"><?= htmlspecialchars($s['course_title']) ?></div>
                                </td>
                                <td><span class="badge <?= $badge ?> rounded-pill px-3"><?= $s['status'] ?></span></td>
                                <td class="small"><?= date('M d, Y', strtotime($s['submitted_at'])) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>