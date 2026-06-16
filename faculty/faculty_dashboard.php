<?php
/**
 * faculty_dashboard.php
 * Faculty dashboard — modern SaaS-style with analytics.
 */
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

restrict_to_role('faculty');

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$role_display = 'Faculty Panel';

if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: faculty_dashboard.php');
    exit();
}

$submissions = get_faculty_submissions($user_id);
$total = count($submissions);
$approved = count(array_filter($submissions, fn($s) => $s['status'] === 'Approved'));
$pending = count(array_filter($submissions, fn($s) => $s['status'] === 'Pending'));
$rejected = count(array_filter($submissions, fn($s) => $s['status'] === 'Rejected'));

$unread_count = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard — SCC Syllabus Portal</title>
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

<body>
    <?php $active_page = 'dashboard';
    include '_sidebar.php'; ?>
    <main class="scc-main">
        <div class="container-fluid p-0">
            <!-- Top Bar -->
            <div class="scc-page-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1" style="color:var(--text)">
                        <i class="bi bi-geo-alt text-primary me-2"></i>Faculty <span style="color:var(--primary)">Dashboard</span>
                    </h4>
                    <p class="text-muted small mb-0"><?= get_current_school_year() ?> — Welcome back, <?= htmlspecialchars($username) ?></p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <!-- Notification Bell -->
                    <div class="dropdown">
                        <div class="position-relative" style="cursor:pointer" data-bs-toggle="dropdown">
                            <i class="bi bi-bell fs-5" style="color:var(--text)"></i>
                            <?php if ($unread_count > 0): ?><span class="notif-badge"><?= $unread_count > 9 ? '9+' : $unread_count ?></span><?php endif; ?>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="width:340px;max-height:420px;overflow-y:auto;border-radius:var(--radius-md);background:var(--bg-card)">
                            <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom sticky-top" style="background:var(--bg-card);z-index:11">
                                <strong style="font-size:0.9rem;color:var(--text)">Notifications</strong>
                                <?php if ($unread_count > 0): ?><a href="?mark_read=1" class="text-decoration-none small" style="color:var(--primary)">Mark all read</a><?php endif; ?>
                            </li>
                            <?php if (empty($notifications)): ?>
                                <li class="px-3 py-4 text-center" style="color:var(--text-muted)"><i class="bi bi-bell-slash fs-4 d-block mb-2 opacity-50"></i><span class="small">No notifications yet</span></li>
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
                    <!-- Print -->
                    <button class="btn btn-sm d-flex align-items-center gap-1"
                        style="color:var(--text);border:1px solid var(--border);border-radius:var(--radius-sm);padding:0.4rem 0.8rem;background:var(--bg-card)"
                        onclick="window.print()">
                        <i class="bi bi-printer"></i>
                    </button>
                </div>
            </div>

            <!-- Pending Alert -->
            <?php if ($pending > 0): ?>
                <div class="alert border-0 shadow-sm mb-4 d-flex align-items-center p-3 rounded-4 animate-in" style="background: var(--primary-light); border-left: 5px solid var(--primary) !important;">
                    <div class="bg-primary text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center shadow-sm" style="width:45px;height:45px;flex-shrink:0">
                        <i class="bi bi-hourglass-split fs-4"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="fw-bold mb-1 text-primary">Submissions Pending Review</h6>
                        <p class="mb-0 text-muted small">You have <?= $pending ?> syllabus submission(s) currently awaiting Dean review.</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <?php
                $stats = [
                    ['label' => 'Total Submissions', 'value' => $total, 'class' => 'stat-total', 'icon' => 'bi-files', 'sub' => 'All uploaded syllabi', 'link' => 'my_submissions.php'],
                    ['label' => 'Approved', 'value' => $approved, 'class' => 'stat-approved', 'icon' => 'bi-check-circle', 'sub' => 'Validated content', 'link' => 'my_submissions.php'],
                    ['label' => 'Pending', 'value' => $pending, 'class' => 'stat-pending', 'icon' => 'bi-clock-history', 'sub' => 'Awaiting review', 'link' => 'my_submissions.php'],
                    ['label' => 'Rejected', 'value' => $rejected, 'class' => 'stat-rejected', 'icon' => 'bi-x-circle', 'sub' => 'Needs revision', 'link' => 'my_submissions.php'],
                ];
                foreach ($stats as $s): ?>
                    <div class="col-md-3">
                        <div class="scc-stat <?= $s['class'] ?> animate-in h-100" 
                             onclick="location.href='<?= $s['link'] ?>'" 
                             style="cursor:pointer">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-label"><?= $s['label'] ?></div>
                                    <div class="stat-value"><?= $s['value'] ?></div>
                                    <div class="text-muted small mt-1" style="font-size:0.7rem"><?= $s['sub'] ?></div>
                                </div>
                                <div class="stat-icon">
                                    <i class="bi <?= $s['icon'] ?>"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="row g-4 mb-4">
                <!-- My Submissions -->
                <div class="col-xl-7">
                    <div class="scc-card h-100 animate-in" style="--animation-order: 4">
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
                                        $visible_my = array_slice($submissions, 0, 5);
                                        if (empty($submissions)): ?>
                                            <tr><td colspan="3" class="text-center py-4 text-muted small">No submissions yet. <a href="upload_syllabus.php">Upload &rarr;</a></td></tr>
                                        <?php else: foreach ($visible_my as $s): 
                                            $badge_class = match ($s['status']) {
                                                'Approved' => 'badge-approved',
                                                'Pending' => 'badge-pending',
                                                default => 'badge-rejected',
                                            };
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-dark small"><?= htmlspecialchars($s['course_code']) ?></div>
                                                    <div class="text-muted small" style="font-size: 0.65rem"><?= htmlspecialchars($s['course_title']) ?></div>
                                                </td>
                                                <td>
                                                    <?= format_syllabus_status($s['status'], $s['current_stage_role'] ?? null, $s['rejecting_role'] ?? null, $s['rejecting_name'] ?? null) ?>
                                                </td>
                                                <td class="text-end text-nowrap">
                                                    <button type="button" onclick="showTrackerModal(<?= (int)$s['id'] ?>, '<?= htmlspecialchars($s['course_code'], ENT_QUOTES) ?>', '<?= htmlspecialchars($s['course_title'], ENT_QUOTES) ?>')" class="btn btn-sm btn-light border px-2 py-1 text-primary shadow-sm" title="Track Progress">
                                                        <i class="bi bi-geo-alt"></i>
                                                    </button>
                                                    <a href="view_syllabus.php?file=<?= urlencode(basename($s['file_path'])) ?>" target="_blank" class="btn btn-sm btn-light border px-2 py-1 shadow-sm" title="View Syllabus">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Shared Syllabus -->
                <div class="col-xl-5">
                    <div class="scc-card p-4 h-100 animate-in" style="--animation-order: 5">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h6 class="fw-bold mb-0">Recent Shared <span class="text-orange">Syllabus</span></h6>
                            <a href="shared_syllabus.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                View All
                            </a>
                        </div>
                        <?php
                        $shared = array_slice(get_shared_syllabi($_SESSION['college_id'] ?? null), 0, 5);
                        ?>
                        <div class="table-responsive">
                            <table class="scc-table align-middle">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th class="text-center">File</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($shared)): ?>
                                        <tr>
                                            <td colspan="2" class="text-center text-muted py-4 small">
                                                No approved syllabi in the shared repository yet.
                                            </td>
                                        </tr>
                                    <?php else:
                                        foreach ($shared as $sh): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-bold small" style="color:var(--text)"><?= htmlspecialchars($sh['course_code']) ?></span>
                                                        <span class="text-muted text-truncate" style="font-size:.7rem;max-width:150px;">
                                                            <?= htmlspecialchars($sh['course_title']) ?>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <a href="view_syllabus.php?file=<?= urlencode(basename($sh['file_path'])) ?>" target="_blank" style="color:var(--primary)">
                                                        <i class="bi bi-file-earmark-pdf fs-5"></i>
                                                    </a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/common.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        }
    </script>
    <?php include __DIR__ . '/../_tracker_modal.php'; ?>
</body>
</html>
