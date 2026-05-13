<?php
/**
 * dept_dashboard.php
 * Department Head dashboard — modern analytics design.
 */
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

restrict_to_role('department_head');

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$role_display = 'Dept Head Panel';
$college_id = $_SESSION['college_id'] ?? null;

if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: dept_dashboard.php');
    exit();
}

$conn = get_db();

$pending_review_count = 0;
if ($college_id) {
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT sw.syllabus_id) FROM syllabus_workflow sw JOIN syllabus s ON sw.syllabus_id = s.id JOIN users u ON s.uploaded_by = u.id JOIN roles r ON sw.role_id = r.id WHERE r.role_name = 'dean' AND sw.action = 'Pending' AND u.college_id = ?");
    $stmt->execute([$college_id]);
    $pending_review_count = (int) $stmt->fetchColumn();
}

$my_submissions = get_faculty_submissions($user_id);
$my_total = count($my_submissions);
$my_approved = count(array_filter($my_submissions, fn($s) => $s['status'] === 'Approved'));
$my_pending = count(array_filter($my_submissions, fn($s) => $s['status'] === 'Pending'));
$my_rejected = count(array_filter($my_submissions, fn($s) => $s['status'] === 'Rejected'));

$reg_count = 0;
if ($college_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'faculty' AND u.is_approved = 0 AND u.is_deleted = 0 AND u.college_id = ?");
    $stmt->execute([$college_id]);
    $reg_count = (int) $stmt->fetchColumn();
}

$approved_repo_count = 0;
if ($college_id) {
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT s.id) FROM syllabus s JOIN users u ON s.uploaded_by = u.id WHERE s.status = 'Approved' AND u.college_id = ?");
    $stmt->execute([$college_id]);
    $approved_repo_count = (int) $stmt->fetchColumn();
}

$active_instructors = 0;
if ($college_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'faculty' AND u.is_approved = 1 AND u.is_deleted = 0 AND u.college_id = ?");
    $stmt->execute([$college_id]);
    $active_instructors = (int) $stmt->fetchColumn();
}

$unread_count = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dept Head Dashboard — SCC Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/theme.js"></script>
</head>
<body>
    <?php $active_page = 'dashboard'; include '_sidebar.php'; ?>

    <main class="scc-main">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-1" style="color:var(--text)">Welcome, <span style="color:var(--primary)"><?= htmlspecialchars($username) ?></span></h4>
                    <p style="font-size:0.85rem;color:var(--text-secondary);margin:0"><?= get_current_school_year() ?> — Department Overview</p>
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

            <!-- Action Alert -->
            <?php if ($pending_review_count > 0): ?>
                <div class="alert border-0 shadow-sm mb-4 d-flex align-items-center p-3 rounded-4 animate-in" style="background: var(--danger-light); border-left: 5px solid var(--danger) !important;">
                    <div class="bg-danger text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center shadow-sm" style="width:45px;height:45px;flex-shrink:0">
                        <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="fw-bold mb-1 text-dark">Action Required — <?= $pending_review_count ?> Syllabi for Review</h6>
                        <p class="mb-0 text-muted small">Faculty submissions currently awaiting your approval.</p>
                    </div>
                    <a href="syllabus_review.php" class="btn btn-danger rounded-pill px-4 py-2 fw-bold small ms-3 shadow-sm">Review Now</a>
                </div>
            <?php endif; ?>

            <!-- Stat Cards -->
            <div class="row g-3 mb-4">
                <?php
                $stats = [
                    ['label'=>'My Submissions','value'=>$my_total,'class'=>'stat-total','icon'=>'bi-files'],
                    ['label'=>'Approved','value'=>$my_approved,'class'=>'stat-approved','icon'=>'bi-check-circle'],
                    ['label'=>'Pending','value'=>$my_pending,'class'=>'stat-pending','icon'=>'bi-clock-history'],
                    ['label'=>'Rejected','value'=>$my_rejected,'class'=>'stat-rejected','icon'=>'bi-x-circle'],
                ];
                foreach ($stats as $i => $s): ?>
                <div class="col-xl-3 col-md-6">
                    <div class="scc-stat <?= $s['class'] ?> animate-in animate-in-delay-<?= $i ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div><div class="stat-label"><?= $s['label'] ?></div><div class="stat-value" style="color:var(--text)"><?= $s['value'] ?></div></div>
                            <div class="stat-icon"><i class="bi <?= $s['icon'] ?>"></i></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pipeline + Activity -->
            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="scc-card h-100 animate-in">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h6 class="fw-bold mb-0 text-dark">Review <span class="text-orange">Pipeline</span></h6>
                                <span class="badge bg-light text-dark border px-3 py-1 rounded-pill" style="font-size: 0.7rem;"><?= get_current_school_year() ?></span>
                            </div>
                            <div class="row g-3 mb-5">
                                <div class="col-4">
                                    <div class="p-3 rounded-4 border-0 shadow-sm text-center" style="background:var(--bg-secondary); border-bottom: 4px solid var(--primary) !important;">
                                        <div class="fw-bold fs-3 text-dark mb-1"><?= $my_total ?></div>
                                        <div class="text-muted fw-bold text-uppercase" style="font-size: 0.55rem; letter-spacing: 1px;">Global Submissions</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-3 rounded-4 border-0 shadow-sm text-center" style="background:var(--bg-secondary); border-bottom: 4px solid var(--warning) !important;">
                                        <div class="fw-bold fs-3 text-dark mb-1"><?= $pending_review_count ?></div>
                                        <div class="text-muted fw-bold text-uppercase" style="font-size: 0.55rem; letter-spacing: 1px;">Awaiting Review</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-3 rounded-4 border-0 shadow-sm text-center" style="background:var(--bg-secondary); border-bottom: 4px solid var(--success) !important;">
                                        <div class="fw-bold fs-3 text-dark mb-1"><?= $my_approved ?></div>
                                        <div class="text-muted fw-bold text-uppercase" style="font-size: 0.55rem; letter-spacing: 1px;">Approved records</div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-grid">
                                <a href="syllabus_review.php" class="btn btn-primary-scc py-3 fw-bold"><i class="bi bi-arrow-right-circle me-2"></i> Manage Review Queue</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="scc-card h-100">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3" style="color:var(--text);font-family:var(--font-serif)">Recent Activity</h6>
                            <?php if (empty($notifications)): ?>
                                <div class="text-center py-3"><i class="bi bi-bell-slash fs-3 opacity-25" style="color:var(--text-muted)"></i><p class="small mt-2" style="color:var(--text-muted)">No activity</p></div>
                            <?php else: foreach ($notifications as $n): ?>
                                <div class="d-flex align-items-start mb-3 pb-2 border-bottom" style="border-color:var(--border)!important">
                                    <i class="bi bi-bell-fill me-2 mt-1" style="font-size:.75rem;color:var(--primary)"></i>
                                    <div>
                                        <p class="mb-0 small <?= !$n['is_read'] ? 'fw-semibold' : '' ?>" style="color:var(--text)"><?= htmlspecialchars($n['message']) ?></p>
                                        <span style="font-size:.65rem;color:var(--text-muted)"><?= date('M d, Y', strtotime($n['created_at'])) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Access -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="scc-card h-100" style="border-top:3px solid var(--primary)">
                        <div class="card-body text-center py-4">
                            <i class="bi bi-clipboard-check fs-2 mb-3" style="color:var(--primary)"></i>
                            <h6 class="fw-bold" style="color:var(--text)">Syllabus Review</h6>
                            <p class="small" style="color:var(--text-secondary)"><?= $pending_review_count ?> pending</p>
                            <a href="syllabus_review.php" class="btn btn-outline-scc btn-sm px-3">Review</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="scc-card h-100" style="border-top:3px solid var(--warning)">
                        <div class="card-body text-center py-4">
                            <i class="bi bi-person-check fs-2 mb-3" style="color:var(--warning)"></i>
                            <h6 class="fw-bold" style="color:var(--text)">Registration Requests</h6>
                            <p class="small" style="color:var(--text-secondary)"><?= $reg_count ?> pending</p>
                            <a href="registration_requests.php" class="btn btn-outline-scc btn-sm px-3">Manage</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="scc-card h-100" style="border-top:3px solid var(--success)">
                        <div class="card-body text-center py-4">
                            <i class="bi bi-archive fs-2 mb-3" style="color:var(--success)"></i>
                            <h6 class="fw-bold" style="color:var(--text)">Repository</h6>
                            <p class="small" style="color:var(--text-secondary)"><?= $approved_repo_count ?> approved · <?= $active_instructors ?> instructors</p>
                            <a href="shared_syllabus.php" class="btn btn-outline-scc btn-sm px-3">Explore</a>
                        </div>
                    </div>
                </div>
            </div>
        </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/common.js"></script>
    <script>
    function toggleSidebar(){
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('active');
    }
    </script>
</body>
</html>