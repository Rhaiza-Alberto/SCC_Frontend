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
$recent = array_slice($submissions, 0, 5);

$my_courses = [];
foreach ($submissions as $sub) {
    $code = $sub['course_code'];
    if (!isset($my_courses[$code])) {
        $my_courses[$code] = [
            'code' => $code,
            'title' => $sub['course_title'],
            'status' => $sub['status'],
            'current_role' => $sub['current_stage_role'] ?? null,
            'rejecting_role' => $sub['rejecting_role'] ?? null,
        ];
    }
}
$my_courses = array_values($my_courses);

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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-1" style="color:var(--text)">Faculty <span style="color:var(--primary)">Dashboard</span></h4>
                    <p class="text-muted small mb-0"><?= get_current_school_year() ?> — Welcome back, <?= htmlspecialchars($username) ?></p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <!-- Notification Bell with Badge Count -->
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
                        <p class="mb-0 text-muted small">You have <?= $pending ?> syllabus submission(s) currently being reviewed by the Dean or VPAA.</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Table Filters -->
            <div class="scc-card mb-4">
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" id="filterSearch" class="form-control border-start-0 ps-0" placeholder="Search by course code or title..." style="font-size:0.85rem">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select id="filterStatus" class="form-select" style="font-size:0.85rem">
                                <option value="">All Statuses</option>
                                <option value="Pending">Pending</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="date" id="filterDate" class="form-control" style="font-size:0.85rem">
                        </div>
                    </div>
                </div>
            </div>

            <div class="scc-card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="scc-table" id="submissionsTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Course</th>
                                    <th class="d-none d-xl-table-cell">Sem / Year</th>
                                    <th>Status</th>
                                    <th>Comment</th>
                                    <th class="text-center">File</th>
                                    <th>Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($submissions)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4" style="color:var(--text-muted)">
                                            <i class="bi bi-inbox fs-4 d-block mb-2 opacity-50"></i>
                                            No submissions yet. <a href="upload_syllabus.php"
                                                style="color:var(--primary)">Upload one →</a>
                                        </td>
                                    </tr>
                                <?php else:
                                    foreach ($submissions as $i => $sub):
                                        $file_date = date('Y-m-d', strtotime($sub['submitted_at']));
                                        ?>
                                        <tr class="submission-row" data-code="<?= strtolower($sub['course_code']) ?>"
                                            data-title="<?= strtolower($sub['course_title']) ?>" data-status="<?= $sub['status'] ?>"
                                            data-date="<?= $file_date ?>">
                                            <td><?= $i + 1 ?></td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold small" style="color:var(--text)"><?= htmlspecialchars($sub['course_code']) ?></span>
                                                    <span class="text-muted text-truncate" style="font-size:0.7rem;max-width:150px"><?= htmlspecialchars($sub['course_title']) ?></span>
                                                </div>
                                            </td>
                                            <td class="d-none d-xl-table-cell small" style="color:var(--text-secondary)">
                                                <?= htmlspecialchars($sub['school_year'] ?? '—') ?></td>
                                            <td>
                                                <?= format_syllabus_status($sub['status'], $sub['current_stage_role'] ?? null, $sub['rejecting_role'] ?? null, $sub['rejecting_name'] ?? null) ?>
                                            </td>
                                            <td class="small" style="color:var(--text-muted);max-width:120px">
                                                <?= htmlspecialchars($sub['reject_comment'] ?? '—') ?></td>
                                            <td class="text-center">
                                                <a href="view_syllabus.php?file=<?= urlencode(basename($sub['file_path'])) ?>"
                                                    target="_blank" style="color:var(--primary)"><i
                                                        class="bi bi-file-earmark-pdf fs-5"></i></a>
                                            </td>
                                            <td class="small" style="color:var(--text-muted)">
                                                <?= date('M d, Y', strtotime($sub['submitted_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                            </tbody>
                        </table>
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
        function applyFilter() {
            const search = document.getElementById('filterSearch').value.toLowerCase();
            const status = document.getElementById('filterStatus').value;
            const date = document.getElementById('filterDate').value;
            document.querySelectorAll('.submission-row').forEach(row => {
                const matchSearch = !search || row.dataset.code.includes(search) || row.dataset.title.includes(search);
                const matchStatus = !status || row.dataset.status === status;
                const matchDate = !date || row.dataset.date === date;
                row.style.display = (matchSearch && matchStatus && matchDate) ? '' : 'none';
            });
        }
        document.getElementById('filterSearch').addEventListener('keyup', applyFilter);
        document.getElementById('filterStatus').addEventListener('change', applyFilter);
        document.getElementById('filterDate').addEventListener('change', applyFilter);
    </script>
</body>

</html>