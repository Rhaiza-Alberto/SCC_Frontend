<?php
/**
 * dean/shared_syllabus.php
 * Displays all VPAA-approved syllabi shared across faculty and dean.
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
$role_display = 'Dean Panel';

if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: shared_syllabus.php');
    exit();
}

$unread_count  = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);

/* ── Fetch all approved syllabi with uploader info ── */
$pdo  = get_db();
$stmt = $pdo->prepare("
    SELECT
        s.id,
        s.course_code,
        s.course_title,
        s.subject_type,
        s.semester,
        s.school_year,
        s.file_path,
        s.submitted_at,
        CONCAT(u.first_name, ' ', u.last_name) AS faculty_name,
        d.department_name
    FROM syllabus s
    JOIN users       u ON u.id = s.uploaded_by
    LEFT JOIN departments d ON d.id = u.department_id
    WHERE s.status = 'Approved'
    ORDER BY s.submitted_at DESC
");
$stmt->execute();
$approved_syllabi = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ── Unique filter values ── */
$departments = array_unique(array_filter(array_column($approved_syllabi, 'department_name')));
$semesters   = array_unique(array_filter(array_column($approved_syllabi, 'semester')));
$years       = array_unique(array_filter(array_column($approved_syllabi, 'school_year')));
sort($departments); sort($semesters); rsort($years);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared Syllabus - SCC-CCS Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .text-orange  { color: #ff8800 !important; }
        .btn-orange   { background-color: #ff8800 !important; color: #fff !important; border: none; }
        .btn-orange:hover { background-color: #e67a00 !important; }
        .notif-dot    { position:absolute;top:2px;right:2px;width:10px;height:10px;
                        background:#dc3545;border-radius:50%;border:2px solid #fff; }
        .badge-approved { background:#d1fae5; color:#065f46; font-size:.7rem; }
        .search-box:focus { border-color:#ff8800; box-shadow:0 0 0 .2rem rgba(255,136,0,.2); }
    </style>
</head>
<body class="bg-light">
<div class="d-flex">

    <!-- ── Sidebar ── -->
    <div class="sidebar sidebar-premium text-white p-2 min-vh-100 d-flex flex-column"
         style="width:260px; position:fixed; z-index:1100;">
        <div class="text-center mb-3 mt-2">
            <img src="../css/logo.png" alt="CCS Logo" class="rounded-circle mb-2"
                 style="width:80px;height:80px;border:2px solid rgba(255,136,0,.5);padding:3px;">
            <h5 class="font-serif fw-bold text-orange mb-0"><?= $role_display ?></h5>
            <p class="text-white-50 small fw-bold mb-0" style="font-size:.75rem;">
                <?= htmlspecialchars($username) ?>
            </p>
        </div>

        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
        <a href="admin_dashboard.php" class="nav-link text-white p-3 rounded hover-effect">Dashboard</a>

        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
        <a href="syllabus_review.php"  class="nav-link text-white p-3 rounded hover-effect">Syllabus Review</a>
        <a href="shared_syllabus.php"  class="nav-link text-white active-nav-link p-3 rounded">Shared Syllabus</a>

        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
        <a href="profile.php"   class="nav-link text-white p-3 rounded hover-effect">Profile</a>
        <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
    </div>

    <!-- ── Main Content ── -->
    <div class="main-content flex-grow-1 p-5" style="margin-left:260px;">

        <!-- Top Bar -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="text-orange font-serif fw-bold mb-0">Shared Syllabus Repository</h2>
                <p class="text-muted small mb-0">All VPAA-approved syllabi available to faculty and dean</p>
            </div>

            <!-- Notification Bell -->
            <div class="dropdown">
                <div class="position-relative" style="cursor:pointer;" data-bs-toggle="dropdown">
                    <i class="bi bi-bell fs-4 text-dark"></i>
                    <?php if ($unread_count > 0): ?>
                        <span class="notif-dot"></span>
                    <?php endif; ?>
                </div>
                <ul class="dropdown-menu dropdown-menu-end shadow" style="width:320px;max-height:400px;overflow-y:auto;">
                    <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom">
                        <strong>Notifications</strong>
                        <?php if ($unread_count > 0): ?>
                            <a href="?mark_read=1" class="text-decoration-none small text-orange">Mark all read</a>
                        <?php endif; ?>
                    </li>
                    <?php if (empty($notifications)): ?>
                        <li class="px-3 py-3 text-center text-muted small">No notifications yet</li>
                    <?php else: foreach ($notifications as $n):
                        $color = get_notification_color($n['message']); ?>
                        <li class="px-3 py-2 border-bottom <?= !$n['is_read'] ? 'bg-light' : '' ?>">
                            <p class="mb-0 small">
                                <span class="<?= $color['text'] ?> fw-bold me-1"><?= $color['icon'] ?></span>
                                <span class="<?= $color['text'] ?>"><?= htmlspecialchars($n['message']) ?></span>
                            </p>
                            <span class="text-muted" style="font-size:.7rem;">
                                <?= date('M d, Y h:i A', strtotime($n['created_at'])) ?>
                            </span>
                        </li>
                    <?php endforeach; endif; ?>
                    <li class="border-top">
                        <a href="notifications.php"
                           class="d-block text-center text-orange text-decoration-none small fw-bold py-2">
                            View all notifications
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm p-3 d-flex flex-row align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:48px;height:48px;background:rgba(255,136,0,.12);">
                        <i class="bi bi-journal-check text-orange fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4 text-orange"><?= count($approved_syllabi) ?></div>
                        <div class="text-muted small">Approved Syllabi</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm p-3 d-flex flex-row align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:48px;height:48px;background:rgba(255,136,0,.12);">
                        <i class="bi bi-people text-orange fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4 text-orange">
                            <?= count(array_unique(array_column($approved_syllabi, 'faculty_name'))) ?>
                        </div>
                        <div class="text-muted small">Contributing Faculty</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm p-3 d-flex flex-row align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:48px;height:48px;background:rgba(255,136,0,.12);">
                        <i class="bi bi-building text-orange fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-4 text-orange"><?= count($departments) ?></div>
                        <div class="text-muted small">Departments</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter / Search Bar -->
        <div class="card border-0 shadow-sm p-3 mb-4">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-bold text-muted mb-1">Search</label>
                    <input type="text" id="searchInput" class="form-control search-box"
                           placeholder="Course code, title, or faculty…">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Department</label>
                    <select id="deptFilter" class="form-select">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">Semester</label>
                    <select id="semFilter" class="form-select">
                        <option value="">All</option>
                        <?php foreach ($semesters as $sem): ?>
                            <option value="<?= htmlspecialchars($sem) ?>"><?= htmlspecialchars($sem) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">School Year</label>
                    <select id="yearFilter" class="form-select">
                        <option value="">All Years</option>
                        <?php foreach ($years as $yr): ?>
                            <option value="<?= htmlspecialchars($yr) ?>"><?= htmlspecialchars($yr) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-1 d-flex align-items-end">
                    <button class="btn btn-outline-secondary w-100" id="clearFilters" title="Clear filters">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Syllabi Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <?php if (empty($approved_syllabi)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-folder2-open fs-1 text-orange opacity-50 mb-3 d-block"></i>
                        <p class="mb-0">No approved syllabi yet.</p>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="syllabi-table">
                        <thead class="table-light border-bottom">
                            <tr>
                                <th class="text-secondary small ps-4">#</th>
                                <th class="text-secondary small">COURSE</th>
                                <th class="text-secondary small">FACULTY</th>
                                <th class="text-secondary small d-none d-lg-table-cell">DEPARTMENT</th>
                                <th class="text-secondary small d-none d-md-table-cell">SEMESTER</th>
                                <th class="text-secondary small d-none d-xl-table-cell">SCHOOL YEAR</th>
                                <th class="text-secondary small d-none d-md-table-cell">SUBMITTED</th>
                                <th class="text-secondary small text-center">STATUS</th>
                                <th class="text-secondary small text-center pe-4">FILE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($approved_syllabi as $i => $row): ?>
                            <tr
                                data-search="<?= strtolower(htmlspecialchars($row['course_code'].' '.$row['course_title'].' '.$row['faculty_name'])) ?>"
                                data-dept="<?= htmlspecialchars($row['department_name'] ?? '') ?>"
                                data-sem="<?= htmlspecialchars($row['semester'] ?? '') ?>"
                                data-year="<?= htmlspecialchars($row['school_year'] ?? '') ?>"
                            >
                                <td class="ps-4 text-muted small"><?= $i + 1 ?></td>
                                <td>
                                    <div class="fw-bold small"><?= htmlspecialchars($row['course_code']) ?></div>
                                    <div class="text-muted" style="font-size:.72rem;max-width:180px;">
                                        <?= htmlspecialchars($row['course_title']) ?>
                                    </div>
                                </td>
                                <td class="small"><?= htmlspecialchars($row['faculty_name']) ?></td>
                                <td class="small d-none d-lg-table-cell text-muted">
                                    <?= htmlspecialchars($row['department_name'] ?? '—') ?>
                                </td>
                                <td class="small d-none d-md-table-cell">
                                    <?= htmlspecialchars($row['semester'] ?? '—') ?>
                                </td>
                                <td class="small d-none d-xl-table-cell">
                                    <?= htmlspecialchars($row['school_year'] ?? '—') ?>
                                </td>
                                <td class="small d-none d-md-table-cell text-muted">
                                    <?= date('M d, Y', strtotime($row['submitted_at'])) ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-approved rounded-pill px-3 py-1">
                                        <i class="bi bi-check-circle-fill me-1"></i>Approved
                                    </span>
                                </td>
                                <td class="text-center pe-4">
                                    <a href="view_syllabus.php?file=<?= urlencode(basename($row['file_path'])) ?>"
                                       target="_blank" rel="noopener"
                                       class="btn btn-sm btn-link text-orange p-0"
                                       title="View PDF">
                                        <i class="bi bi-file-earmark-pdf fs-5"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div id="noResults" class="text-center py-5 text-muted d-none">
                    <i class="bi bi-search fs-2 mb-2 d-block opacity-50"></i>
                    No syllabi match your filters.
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /main-content -->
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const searchInput  = document.getElementById('searchInput');
    const deptFilter   = document.getElementById('deptFilter');
    const semFilter    = document.getElementById('semFilter');
    const yearFilter   = document.getElementById('yearFilter');
    const clearBtn     = document.getElementById('clearFilters');
    const table        = document.getElementById('syllabi-table');
    const noResults    = document.getElementById('noResults');

    if (!table) return;

    function applyFilters() {
        const q    = searchInput.value.toLowerCase().trim();
        const dept = deptFilter.value;
        const sem  = semFilter.value;
        const yr   = yearFilter.value;
        let visible = 0;

        table.querySelectorAll('tbody tr').forEach(row => {
            const matchSearch = !q    || row.dataset.search.includes(q);
            const matchDept   = !dept || row.dataset.dept  === dept;
            const matchSem    = !sem  || row.dataset.sem   === sem;
            const matchYear   = !yr   || row.dataset.year  === yr;
            const show = matchSearch && matchDept && matchSem && matchYear;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        noResults.classList.toggle('d-none', visible > 0);
    }

    [searchInput, deptFilter, semFilter, yearFilter].forEach(el =>
        el.addEventListener('input', applyFilters));

    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        deptFilter.value  = '';
        semFilter.value   = '';
        yearFilter.value  = '';
        applyFilters();
    });
})();
</script>
</body>
</html>