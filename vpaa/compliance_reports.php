<?php
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


$unread_count = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compliance Reports — SCC Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php $active_page = 'compliance'; include '_sidebar.php'; ?>

    <main class="scc-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color:var(--text)">Compliance <span style="color:var(--primary)">Reports</span></h4>
                <p style="font-size:0.85rem;color:var(--text-secondary);margin:0">Academic year <?= get_current_school_year() ?> — Readiness Audit</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm d-flex align-items-center gap-1" style="color:var(--text);border:1px solid var(--border);border-radius:var(--radius-sm);padding:0.4rem 0.8rem;background:var(--bg-card)" onclick="window.print()"><i class="bi bi-printer"></i></button>
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
        
        <!-- Summary Stats -->
        <div class="row g-3 mb-4">
            <?php 
                $total_global = array_sum(array_column($college_rows, 'total'));
                $approved_global = array_sum(array_column($college_rows, 'approved'));
                $pending_global = array_sum(array_column($college_rows, 'pending'));
                $rejected_global = array_sum(array_column($college_rows, 'rejected'));
                $compliance_avg = $total_global > 0 ? round(($approved_global / $total_global) * 100) : 0;
            ?>
            <div class="col-md-3">
                <div class="scc-stat stat-total animate-in">
                    <div class="stat-icon"><i class="bi bi-files"></i></div>
                    <div class="stat-label">Total Submissions</div>
                    <div class="stat-value"><?= $total_global ?></div>
                    <div class="small text-secondary mt-1">Institutional volume</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="scc-stat stat-approved animate-in">
                    <div class="stat-icon"><i class="bi bi-patch-check"></i></div>
                    <div class="stat-label">Fully Compliant</div>
                    <div class="stat-value text-success"><?= $approved_global ?></div>
                    <div class="small text-secondary mt-1"><?= $compliance_avg ?>% Approval rate</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="scc-stat stat-pending animate-in">
                    <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
                    <div class="stat-label">Awaiting Review</div>
                    <div class="stat-value text-warning"><?= $pending_global ?></div>
                    <div class="small text-secondary mt-1">Pending verification</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="scc-stat stat-rejected animate-in">
                    <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
                    <div class="stat-label">Revision Required</div>
                    <div class="stat-value text-danger"><?= $rejected_global ?></div>
                    <div class="small text-secondary mt-1">Institutional rejections</div>
                </div>
            </div>
        </div>

            <!-- College Cards -->
            <div class="row g-4 mb-5">
                <?php if (empty($college_rows)): ?>
                    <div class="col-12">
                        <p class="text-secondary">No submission data available yet.</p>
                    </div>
                <?php else:
                    foreach ($college_rows as $dept):
                        $pct = $dept['total'] > 0 ? round(($dept['approved'] / $dept['total']) * 100) : 0;
                        [$bar_class, $badge_class, $badge_label, $border_color] = $pct >= 85
                            ? ['bg-success', 'badge-approved', 'Ready for Audit', 'var(--success)']
                            : ($pct >= 60
                                ? ['bg-warning', 'badge-pending', 'In Progress', 'var(--warning)']
                                : ['bg-danger', 'badge-rejected', 'High Attention', 'var(--danger)']);
                        ?>
                        <div class="col-md-4">
                            <div class="scc-card border-0 shadow-sm animate-in h-100" style="border-top: 4px solid <?= $border_color ?> !important;">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h6 class="fw-bold mb-0" style="color:var(--text)"><?= htmlspecialchars($dept['college_name']) ?></h6>
                                        <span class="badge rounded-pill px-3 py-2 <?= $badge_class ?>" style="font-size: 0.65rem; font-weight: 700;"><?= $badge_label ?></span>
                                    </div>
                                    
                                    <div class="progress mb-2" style="height:6px; background:var(--bg-secondary)">
                                        <div class="progress-bar <?= $bar_class ?> rounded-pill" style="width:<?= $pct ?>%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <span class="small fw-bold text-secondary"><?= $pct ?>% Complete</span>
                                        <span class="small text-secondary"><?= $dept['approved'] ?> / <?= $dept['total'] ?> Approved</span>
                                    </div>

                                    <div class="row g-2 pt-3 border-top">
                                        <div class="col-4 border-end text-center">
                                            <div style="font-size: 0.6rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Pending</div>
                                            <div class="fw-bold text-warning small"><?= $dept['pending'] ?></div>
                                        </div>
                                        <div class="col-4 border-end text-center">
                                            <div style="font-size: 0.6rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Rejected</div>
                                            <div class="fw-bold text-danger small"><?= $dept['rejected'] ?></div>
                                        </div>
                                        <div class="col-4 text-center">
                                            <div style="font-size: 0.6rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Total</div>
                                            <div class="fw-bold small" style="color:var(--text)"><?= $dept['total'] ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
            </div>

            <!-- Audit Log -->
            <div class="scc-card animate-in">
                <div class="card-header border-0 bg-transparent p-4 pb-0">
                    <h6 class="fw-bold mb-0" style="color:var(--text)">Audit Log — <span style="color:var(--primary)">Recent Submissions</span></h6>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="scc-table">
                            <thead>
                                <tr>
                                    <th class="text-secondary small">INSTRUCTOR</th>
                                    <th class="text-secondary small">ROLE</th>
                                    <th class="text-secondary small">COURSE</th>
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
                                            'Approved' => 'bg-success-subtle text-success',
                                            'Rejected' => 'bg-danger-subtle text-danger',
                                            default => 'bg-warning-subtle text-warning',
                                        };
                                        ?>
                                        <tr>
                                            <td class="py-3">
                                                <div class="fw-bold small" style="color:var(--text)"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></div>
                                            </td>
                                            <td class="py-3">
                                                <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-1" style="font-size: .65rem; font-weight: 600;">
                                                    <?= ucfirst(htmlspecialchars($row['uploader_role'])) ?>
                                                </span>
                                            </td>
                                            <td class="py-3 small fw-bold" style="color:var(--text)"><?= htmlspecialchars($row['course_code']) ?></td>
                                            <td class="py-3 small text-secondary"><?= htmlspecialchars($row['college_name'] ?? '—') ?></td>
                                            <td class="py-3"><?= format_syllabus_status($row['status']) ?></td>
                                            <td class="py-3 small text-secondary"><?= date('M d, Y', strtotime($row['submitted_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/common.js"></script>
    <script>
    function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('active');}
    </script>
</body>

</html>