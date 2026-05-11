<?php
/**
 * vpaa/compliance_reports.php
 * DB-driven departmental compliance report for VPAA.
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

if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: compliance_reports.php');
    exit();
}

$conn = get_db();

// ── College compliance stats ────────────────────────────────────────────
$college_rows = $conn->query("
    SELECT col.college_name,
           COUNT(DISTINCT s.id)                                    AS total,
           SUM(CASE WHEN s.status = 'Approved' THEN 1 ELSE 0 END) AS approved,
           SUM(CASE WHEN s.status = 'Pending'  THEN 1 ELSE 0 END) AS pending,
           SUM(CASE WHEN s.status = 'Rejected' THEN 1 ELSE 0 END) AS rejected
    FROM syllabus s
    JOIN users u       ON s.uploaded_by = u.id
    JOIN colleges col  ON u.college_id  = col.id
    GROUP BY col.id, col.college_name
    ORDER BY col.college_name
")->fetchAll(PDO::FETCH_ASSOC);

// ── Audit log: last 20 submissions ──────────────────────────────────────────
$audit_stmt = $conn->prepare("
    SELECT s.id, s.status, s.submitted_at,
           COALESCE(NULLIF(s.course_code,''), c.course_code) AS course_code,
           u.first_name, u.last_name,
           col.college_name,
           r.role_name AS uploader_role
    FROM syllabus s
    JOIN users u        ON s.uploaded_by = u.id
    JOIN roles r        ON u.role_id     = r.id
    LEFT JOIN courses c ON s.course_id   = c.id
    LEFT JOIN colleges col ON u.college_id = col.id
    ORDER BY s.submitted_at DESC
    LIMIT 20
");
$audit_stmt->execute();
$audit_log = $audit_stmt->fetchAll(PDO::FETCH_ASSOC);

$vpaa_pending_count = (int) $conn->query("
    SELECT COUNT(DISTINCT sw.syllabus_id) FROM syllabus_workflow sw
    JOIN roles r ON sw.role_id = r.id WHERE r.role_name='vpaa' AND sw.action='Pending'
")->fetchColumn();

$unread_count = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compliance Reports - VPAA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .text-orange {
            color: #ff8800 !important;
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
                <a href="syllabus_review.php" class="nav-link text-white p-3 rounded hover-effect">Syllabus Review
                    <?php if ($vpaa_pending_count > 0): ?><span
                            class="badge bg-danger ms-1"><?= $vpaa_pending_count ?></span><?php endif; ?>
                </a>
                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">ANALYTICS</div>
                <a href="compliance_reports.php" class="nav-link text-white active-nav-link p-3 rounded">Compliance
                    Reports</a>
                <a href="syllabus_vault.php" class="nav-link text-white p-3 rounded hover-effect">Syllabus Vault</a>
                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
                <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect">Profile</a>
                <a href="javascript:void(0)" class="nav-link text-white p-3 rounded hover-effect mt-5" onclick="confirmLogout()">Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-grow-1 p-5" style="margin-left:260px;">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="text-orange font-serif fw-bold mb-0">College Compliance Reports</h2>
                    <p class="text-muted small mb-0">Academic year <?= get_current_school_year() ?></p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button
                        class="btn btn-sm btn-white border shadow-sm rounded-1 px-3 py-1 fw-bold text-dark d-flex align-items-center"
                        onclick="window.print()">
                        <i class="bi bi-printer me-2"></i> PRINT
                    </button>
                    <div class="dropdown">
                        <div class="position-relative" style="cursor:pointer;" data-bs-toggle="dropdown">
                            <i class="bi bi-bell fs-4 text-dark"></i>
                            <?php if ($unread_count > 0): ?>
                                <span class="notif-dot"></span>
                            <?php endif; ?>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0"
                            style="width:320px;max-height:400px;overflow-y:auto;">
                            <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom sticky-top bg-white"
                                style="z-index:11;">
                                <strong>Notifications</strong>
                                <?php if ($unread_count > 0): ?>
                                    <a href="?mark_read=1" class="text-decoration-none small text-orange">Mark all read</a>
                                <?php endif; ?>
                            </li>
                            <?php if (empty($notifications)): ?>
                                <li class="px-3 py-3 text-center text-muted small">No notifications yet</li>
                            <?php else:
                                foreach ($notifications as $n):
                                    $color = get_notification_color($n['message']); ?>
                                    <li class="border-bottom <?= !$n['is_read'] ? 'bg-light' : '' ?>">
                                        <a href="notifications.php?notif_id=<?= $n['id'] ?>"
                                            class="text-decoration-none text-dark d-block px-3 py-2">
                                            <p class="mb-0 small">
                                                <span class="<?= $color['text'] ?> fw-bold me-1"><?= $color['icon'] ?></span>
                                                <span
                                                    class="<?= $color['text'] ?>"><?= htmlspecialchars($n['message']) ?></span>
                                            </p>
                                            <span class="text-muted"
                                                style="font-size:.7rem;"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; endif; ?>
                            <li class="dropdown-menu-sticky-footer">
                                <a href="notifications.php"
                                    class="d-block text-center text-orange text-decoration-none small fw-bold py-2">View
                                    all notifications</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- College Cards -->
            <div class="row g-4 mb-5">
                <?php if (empty($college_rows)): ?>
                    <div class="col-12">
                        <p class="text-muted">No submission data available yet.</p>
                    </div>
                <?php else:
                    foreach ($college_rows as $dept):
                        $pct = $dept['total'] > 0 ? round(($dept['approved'] / $dept['total']) * 100) : 0;
                        [$bar_class, $badge_class, $badge_label] = $pct >= 80
                            ? ['bg-success', 'bg-success', 'Ready for Audit']
                            : ($pct >= 50
                                ? ['bg-warning', 'bg-warning text-dark', 'In Progress']
                                : ['bg-danger', 'bg-danger', 'Action Required']);
                        ?>
                        <div class="col-md-4">
                            <div class="card premium-card p-4 border-0 shadow-sm">
                                <h5 class="font-serif fw-bold mb-1"><?= htmlspecialchars($dept['college_name']) ?></h5>
                                <div class="progress mb-2 mt-3" style="height:18px;">
                                    <div class="progress-bar <?= $bar_class ?> fw-bold" style="width:<?= $pct ?>%"><?= $pct ?>%
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between small text-muted mb-3">
                                    <span><i class="bi bi-check-circle-fill text-success me-1"></i> Approved:
                                        <?= $dept['approved'] ?></span>
                                    <span><i class="bi bi-clock-fill text-warning me-1"></i> Pending:
                                        <?= $dept['pending'] ?></span>
                                    <span><i class="bi bi-x-circle-fill text-danger me-1"></i> Rejected:
                                        <?= $dept['rejected'] ?></span>
                                </div>
                                <p class="text-muted small mb-3">Total Submissions: <strong><?= $dept['total'] ?></strong></p>
                                <span class="badge <?= $badge_class ?> rounded-pill px-3"><?= $badge_label ?></span>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
            </div>

            <!-- Audit Log -->
            <div class="card premium-card p-4 border-0 shadow-sm">
                <h5 class="font-serif fw-bold mb-4 text-orange">Audit Log — Recent Submissions</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="text-secondary small">#</th>
                                <th class="text-secondary small">INSTRUCTOR</th>
                                <th class="text-secondary small">ROLE</th>
                                <th class="text-secondary small">COURSE CODE</th>
                                <th class="text-secondary small">COLLEGE</th>
                                <th class="text-secondary small">STATUS</th>
                                <th class="text-secondary small">SUBMITTED</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($audit_log)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No submissions recorded</td>
                                </tr>
                            <?php else:
                                foreach ($audit_log as $i => $row):
                                    $badge = match ($row['status']) {
                                        'Approved' => 'bg-success',
                                        'Rejected' => 'bg-danger',
                                        default => 'bg-warning text-dark',
                                    };
                                    ?>
                                    <tr>
                                        <td class="small"><?= $i + 1 ?></td>
                                        <td class="small fw-bold"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border rounded-pill px-2" style="font-size: .65rem;">
                                                <?= ucfirst(htmlspecialchars($row['uploader_role'])) ?>
                                            </span>
                                        </td>
                                        <td class="small fw-bold"><?= htmlspecialchars($row['course_code']) ?></td>
                                        <td class="small"><?= htmlspecialchars($row['college_name'] ?? '—') ?></td>
                                        <td><span class="badge <?= $badge ?> rounded-pill px-3"><?= $row['status'] ?></span>
                                        </td>
                                        <td class="small"><?= date('M d, Y', strtotime($row['submitted_at'])) ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmLogout() {
            Swal.fire({
                title: 'Sign Out?',
                text: "Are you sure you want to end your current session?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff8800',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Logout',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../logout.php';
                }
            });
        }
    </script>
</body>

</html>