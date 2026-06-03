<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

restrict_to_role('vpaa');

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'VPAA';
$role_display = 'VPAA Institutional Hub';

if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: vpaa_dashboard.php');
    exit();
}

$conn = get_db();

// ── Advanced Stats ──────────────────────────────────────────────────────────
$total_count = (int) $conn->query("SELECT COUNT(*) FROM syllabus")->fetchColumn();
$approved_count = (int) $conn->query("SELECT COUNT(*) FROM syllabus WHERE status = 'Approved'")->fetchColumn();
$pending_count = (int) $conn->query("SELECT COUNT(*) FROM syllabus WHERE status = 'Pending'")->fetchColumn();
$rejected_count = (int) $conn->query("SELECT COUNT(*) FROM syllabus WHERE status = 'Rejected'")->fetchColumn();

// Total Faculty Uploads
$faculty_role_id = (int) $conn->query("SELECT id FROM roles WHERE role_name = 'faculty'")->fetchColumn();
$total_faculty_uploads = (int) $conn->prepare("SELECT COUNT(*) FROM syllabus s JOIN users u ON s.uploaded_by = u.id WHERE u.role_id = ?")
    ->execute([$faculty_role_id]) ? $conn->prepare("SELECT COUNT(*) FROM syllabus s JOIN users u ON s.uploaded_by = u.id WHERE u.role_id = ?")->fetchColumn() : 0;
// Re-running because previous check was slightly wrong logic in one line
$total_faculty_uploads = (int) $conn->query("SELECT COUNT(*) FROM syllabus s JOIN users u ON s.uploaded_by = u.id JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'faculty'")->fetchColumn();

// Dean Approval Stats (How many approved by Deans but maybe not yet by VPAA)
$dean_approved_count = (int) $conn->query("
    SELECT COUNT(DISTINCT sw.syllabus_id) 
    FROM syllabus_workflow sw 
    JOIN roles r ON sw.role_id = r.id 
    WHERE r.role_name = 'dean' AND sw.action = 'Approved'
")->fetchColumn();

// Syllabi waiting for VPAA final approval
$vpaa_pending_count = (int) $conn->query("
    SELECT COUNT(DISTINCT sw.syllabus_id)
    FROM syllabus_workflow sw
    JOIN roles r ON sw.role_id = r.id
    WHERE r.role_name = 'vpaa' AND sw.action = 'Pending'
")->fetchColumn();

// Revision Requests (Total Rejections in workflow)
$revision_requests = (int) $conn->query("SELECT COUNT(*) FROM syllabus_workflow WHERE action = 'Rejected'")->fetchColumn();

// ── Monthly Analytics (Last 6 Months) ────────────────────────────────────────
$monthly_stmt = $conn->query("
    SELECT DATE_FORMAT(submitted_at, '%b') as month_name, COUNT(*) as count 
    FROM syllabus 
    WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month_name 
    ORDER BY MIN(submitted_at)
");
$monthly_data = $monthly_stmt->fetchAll(PDO::FETCH_ASSOC);

// ── College compliance breakdown ─────────────────────────────────────────────
$college_rows = $conn->query("
    SELECT col.college_name,
           COUNT(DISTINCT s.id)                                      AS total,
           SUM(CASE WHEN s.status = 'Approved' THEN 1 ELSE 0 END)   AS approved
    FROM syllabus s
    JOIN users u        ON s.uploaded_by = u.id
    JOIN colleges col   ON u.college_id  = col.id
    GROUP BY col.id, col.college_name
    ORDER BY col.college_name
")->fetchAll(PDO::FETCH_ASSOC);

$compliance_pct = $total_count > 0 ? round(($approved_count / $total_count) * 100) : 0;
// ── Recent all-submissions (last 10) ─────────────────────────────────────────
$recent_stmt = $conn->prepare("
    SELECT s.*,
           COALESCE(NULLIF(s.course_code,''),  c.course_code)  AS course_code,
           COALESCE(NULLIF(s.course_title,''), c.course_title) AS course_title,
           u.first_name, u.last_name, u.email AS uploader_email,
           r.role_name AS uploader_role,
           col.college_name,
           (SELECT r2.role_name FROM syllabus_workflow sw3 JOIN roles r2 ON sw3.role_id = r2.id WHERE sw3.syllabus_id = s.id AND sw3.action = 'Pending' ORDER BY sw3.step_order ASC LIMIT 1) AS current_stage_role,
           (SELECT r3.role_name FROM syllabus_workflow sw4 JOIN roles r3 ON sw4.role_id = r3.id WHERE sw4.syllabus_id = s.id AND sw4.action = 'Rejected' ORDER BY sw4.action_at DESC LIMIT 1) AS rejecting_role
    FROM syllabus s
    LEFT JOIN courses c ON s.course_id   = c.id
    LEFT JOIN users u   ON s.uploaded_by = u.id
    LEFT JOIN roles r   ON u.role_id     = r.id
    LEFT JOIN colleges col ON u.college_id = col.id
    ORDER BY s.submitted_at DESC
    LIMIT 10
");
$recent_stmt->execute();
$recent_submissions = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

$unread_count = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VPAA Dashboard — SCC Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Merriweather:wght@400;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/theme.js"></script>
</head>

<body class="bg-light">
    <?php $active_page = 'dashboard';
    include '_sidebar.php'; ?>

    <main class="scc-main">
        <div class="container-fluid p-0">
            <!-- Header Section -->
            <div class="scc-page-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1" style="color:var(--text)">Institutional <span style="color:var(--primary)">Dashboard</span></h4>
                    <p class="text-muted small mb-0"><?= get_current_school_year() ?> — Academic & Compliance Overview</p>
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
            <?php if ($vpaa_pending_count > 0): ?>
                <div class="alert border-0 shadow-sm mb-4 d-flex align-items-center p-3 rounded-4 animate-in" style="background: var(--primary-light); border-left: 5px solid var(--primary) !important;">
                    <div class="bg-primary text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center shadow-sm" style="width:45px;height:45px;">
                        <i class="bi bi-shield-lock-fill fs-4"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="fw-bold mb-1 text-dark">Institutional Action Required</h6>
                        <p class="mb-0 text-muted small">There are <strong><?= $vpaa_pending_count ?></strong> syllabus submission(s) currently awaiting your final institutional approval.</p>
                    </div>
                    <a href="syllabus_review.php" class="btn btn-primary-scc rounded-pill px-4 py-2 fw-bold small ms-3 shadow-sm">Review Now</a>
                </div>
            <?php endif; ?>

            <!-- Analytics Summary Cards -->
            <div class="row g-3 mb-4">
                <?php 
                $vpaa_stats = [
                    ['label' => 'Total Submissions', 'value' => $total_count, 'icon' => 'bi-files', 'color' => 'var(--primary)', 'bg' => 'var(--primary-light)'],
                    ['label' => 'Fully Approved', 'value' => $approved_count, 'icon' => 'bi-patch-check', 'color' => 'var(--success)', 'bg' => 'var(--success-light)'],
                    ['label' => 'Pending VPAA', 'value' => $vpaa_pending_count, 'icon' => 'bi-clock-history', 'color' => 'var(--warning)', 'bg' => 'var(--warning-light)'],
                    ['label' => 'Revision Requests', 'value' => $revision_requests, 'icon' => 'bi-arrow-repeat', 'color' => 'var(--danger)', 'bg' => 'var(--danger-light)'],
                    ['label' => 'Faculty Uploads', 'value' => $total_faculty_uploads, 'icon' => 'bi-person-up', 'color' => '#6366f1', 'bg' => 'rgba(99, 102, 241, 0.1)'],
                    ['label' => 'Dean Approvals', 'value' => $dean_approved_count, 'icon' => 'bi-person-check', 'color' => '#8b5cf6', 'bg' => 'rgba(139, 92, 246, 0.1)']
                ];
                foreach ($vpaa_stats as $i => $s): ?>
                    <div class="col-xl-2 col-md-4 col-sm-6">
                        <div class="scc-card p-3 h-100 animate-in" style="animation-delay: <?= $i * 0.05 ?>s">
                            <div class="d-flex align-items-center gap-3">
                                <div style="width:42px;height:42px;border-radius:10px;background:<?= $s['bg'] ?>;color:<?= $s['color'] ?>;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0">
                                    <i class="bi <?= $s['icon'] ?>"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <div class="text-muted small fw-bold text-uppercase text-truncate" style="font-size:0.6rem;letter-spacing:0.5px"><?= $s['label'] ?></div>
                                    <div class="fs-5 fw-bold" style="color:var(--text)"><?= $s['value'] ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Compliance Pulse & Analytics Visuals -->
            <div class="row g-4 mb-4">
                <div class="col-xl-4">
                    <div class="scc-card p-4 h-100 animate-in">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h6 class="fw-bold mb-0">Compliance <span class="text-orange">Pulse</span></h6>
                            <i class="bi bi-info-circle text-muted" title="Overall institutional syllabus approval rate"></i>
                        </div>
                        <div class="text-center py-2">
                            <div class="position-relative d-inline-block">
                                <svg width="160" height="160" viewBox="0 0 120 120">
                                    <circle cx="60" cy="60" r="50" fill="none" stroke="var(--bg-secondary)" stroke-width="8" />
                                    <circle cx="60" cy="60" r="50" fill="none" stroke="var(--primary)" stroke-width="8"
                                        stroke-dasharray="314.159"
                                        stroke-dashoffset="<?= 314.159 * (1 - $compliance_pct / 100) ?>"
                                        stroke-linecap="round" transform="rotate(-90 60 60)"
                                        style="transition: stroke-dashoffset 1.5s cubic-bezier(0.4, 0, 0.2, 1)" />
                                </svg>
                                <div class="position-absolute top-50 start-50 translate-middle text-center">
                                    <h2 class="mb-0 fw-bold" style="color:var(--text); font-size: 2rem;"><?= $compliance_pct ?>%</h2>
                                    <span class="text-secondary small text-uppercase fw-bold" style="font-size:0.6rem;letter-spacing:1px">Institutional Rate</span>
                                </div>
                            </div>
                            <div class="mt-4 pt-2 row g-0">
                                <div class="col-6 border-end">
                                    <span class="small d-block text-secondary">Submissions</span>
                                    <span class="fw-bold fs-5" style="color:var(--text)"><?= $total_count ?></span>
                                </div>
                                <div class="col-6">
                                    <span class="small d-block text-secondary">Fully Approved</span>
                                    <span class="fw-bold fs-5 text-success"><?= $approved_count ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-8">
                    <div class="scc-card p-4 h-100 animate-in">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h6 class="fw-bold mb-0">Submission <span class="text-orange">Trends</span></h6>
                            <div class="d-flex gap-2">
                                <span class="badge bg-light text-dark border fw-normal py-1 px-3" style="font-size:0.7rem">Monthly Volume</span>
                                <span class="badge bg-light text-dark border fw-normal py-1 px-3" style="font-size:0.7rem">Institutional Growth</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-end justify-content-between px-3" style="height: 180px; padding-top: 20px;">
                            <?php if (empty($monthly_data)): ?>
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted small">Insufficient data for trend analysis</div>
                            <?php else:
                                $max_val = max(array_column($monthly_data, 'count')) ?: 1;
                                foreach ($monthly_data as $m):
                                    $h = ($m['count'] / $max_val) * 100;
                                    ?>
                                    <div class="text-center d-flex flex-column align-items-center flex-grow-1 h-100 justify-content-end">
                                        <div class="bg-primary opacity-75 rounded-top" style="width: 35px; height: <?= max($h, 5) ?>%; transition: height 1s ease-out;" title="<?= $m['count'] ?> submissions"></div>
                                        <div class="mt-2 small fw-bold text-muted" style="font-size: 0.75rem;"><?= $m['month_name'] ?></div>
                                    </div>
                                <?php endforeach; endif; ?>
                        </div>
                        <div class="mt-4 pt-3 border-top small text-muted d-flex gap-4">
                            <span><i class="bi bi-circle-fill text-primary me-2" style="font-size: 0.6rem;"></i> Submission Volume</span>
                            <span><i class="bi bi-graph-up text-success me-2"></i> Quality Compliance</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- College Compliance Report Table -->
            <div class="scc-card mb-4 animate-in">
                <div class="card-header border-0 bg-transparent p-4 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">College <span class="text-orange">Compliance Report</span></h6>
                    <a href="compliance_reports.php" class="btn btn-sm btn-light border rounded-pill px-4 fw-bold small">Full Analysis</a>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table align-middle table-hover mb-0">
                            <thead>
                                <tr class="text-muted small" style="letter-spacing: 0.5px">
                                    <th class="border-0 pb-3">COLLEGE NAME</th>
                                    <th class="border-0 pb-3 text-center">COMPLIANCE PULSE</th>
                                    <th class="border-0 pb-3 text-center">APPROVED vs TOTAL</th>
                                    <th class="border-0 pb-3 text-end">OVERALL STATUS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($college_rows as $dept):
                                    $pct = $dept['total'] > 0 ? round(($dept['approved'] / $dept['total']) * 100) : 0;
                                    [$bar_class, $badge_class, $badge_label] = $pct >= 85
                                        ? ['bg-success', 'bg-success-subtle text-success', 'Excellent']
                                        : ($pct >= 60
                                            ? ['bg-warning', 'bg-warning-subtle text-warning', 'Good']
                                            : ['bg-danger', 'bg-danger-subtle text-danger', 'Critical']);
                                    ?>
                                    <tr>
                                        <td class="py-3">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($dept['college_name']) ?></div>
                                            <div class="text-muted" style="font-size: 0.65rem;">Institutional Academic Unit</div>
                                        </td>
                                        <td class="py-3" style="min-width: 200px;">
                                            <div class="progress rounded-pill" style="height:6px; background:var(--bg-secondary)">
                                                <div class="progress-bar <?= $bar_class ?> rounded-pill" style="width:<?= $pct ?>%"></div>
                                            </div>
                                            <div class="mt-2 small fw-bold text-muted" style="font-size: 0.65rem;"><?= $pct ?>% Complete</div>
                                        </td>
                                        <td class="py-3 text-center">
                                            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill small"><?= $dept['approved'] ?> <span class="text-muted mx-1">/</span> <?= $dept['total'] ?></span>
                                        </td>
                                        <td class="py-3 text-end">
                                            <span class="badge rounded-pill px-4 py-2 <?= $badge_class ?>" style="font-size: 0.65rem; font-weight: 700;"><?= $badge_label ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Workflow Sections & Quick Actions -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="scc-card p-4 h-100 animate-in" style="border-left: 4px solid var(--warning) !important;">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="p-3 rounded-circle" style="background:var(--warning-light)">
                                <i class="bi bi-stack text-warning fs-3"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Review <span class="text-orange">Queue</span></h6>
                                <p class="mb-0 text-muted small"><?= $vpaa_pending_count ?> syllabi currently awaiting institutional verification.</p>
                            </div>
                        </div>
                        <a href="syllabus_review.php" class="btn btn-primary-scc w-100 py-2 fw-bold">Open Review Queue</a>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="scc-card p-4 h-100 animate-in" style="border-left: 4px solid var(--success) !important;">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="p-3 rounded-circle" style="background:var(--success-light)">
                                <i class="bi bi-safe2 text-success fs-3"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">Accreditation <span class="text-orange">Vault</span></h6>
                                <p class="mb-0 text-muted small">Explore <?= $approved_count ?> verified files ready for accreditation audits.</p>
                            </div>
                        </div>
                        <a href="syllabus_vault.php" class="btn btn-outline-primary w-100 py-2 fw-bold">Explore Vault</a>
                    </div>
                </div>
            </div>

            <!-- Recent Institutional Submissions -->
            <div class="scc-card animate-in">
                <div class="card-header border-0 bg-transparent p-4 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">Recent <span class="text-orange">Institutional Submissions</span></h6>
                    <a href="syllabus_vault.php" class="btn btn-sm btn-link text-decoration-none small p-0 fw-bold">View All Activity</a>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="scc-table">
                            <thead>
                                <tr class="text-secondary small">
                                    <th>INSTRUCTOR</th>
                                    <th>COLLEGE</th>
                                    <th>COURSE INFORMATION</th>
                                    <th>STATUS</th>
                                    <th>SUBMITTED</th>
                                    <th class="text-end">ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_submissions)): ?>
                                    <tr><td colspan="6" class="text-center py-5 text-muted small">No recent activity found</td></tr>
                                <?php else: foreach ($recent_submissions as $s): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark small"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></div>
                                            <div class="text-muted" style="font-size: 0.65rem;"><?= htmlspecialchars($s['uploader_email']) ?></div>
                                        </td>
                                        <td><span class="small fw-bold text-muted"><?= htmlspecialchars($s['college_name'] ?? '—') ?></span></td>
                                        <td>
                                            <div class="fw-bold text-dark small"><?= htmlspecialchars($s['course_code']) ?></div>
                                            <div class="text-muted text-truncate" style="font-size: 0.65rem; max-width: 250px;"><?= htmlspecialchars($s['course_title']) ?></div>
                                        </td>
                                        <td><?= format_syllabus_status($s['status'], $s['current_stage_role'] ?? null, $s['rejecting_role'] ?? null) ?></td>
                                        <td class="text-muted small"><?= date('M d, Y', strtotime($s['submitted_at'])) ?></td>
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
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/common.js"></script>
    <script>
        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('open'); document.getElementById('sidebarOverlay').classList.toggle('active'); }
    </script>
    <?php include __DIR__ . '/../_tracker_modal.php'; ?>
</body>
</html>