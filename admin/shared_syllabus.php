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

// Initialize the database connection early so $pdo is available for all queries
$pdo = get_db();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$role_display = 'Dean Panel';

if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: shared_syllabus.php');
    exit();
}

$unread_count = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);

/* ── Sidebar badge counts ── */
// This now works because $pdo is defined above
$pending_review_count = (int) $pdo->query("
    SELECT COUNT(DISTINCT sw.syllabus_id)
    FROM syllabus_workflow sw
    JOIN roles r ON sw.role_id = r.id
    WHERE r.role_name = 'dean' AND sw.action = 'Pending'
")->fetchColumn();

$reg_stmt = $pdo->prepare("
    SELECT COUNT(*) FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE r.role_name = 'faculty' AND u.is_approved = 0 AND u.is_deleted = 0
");
$reg_stmt->execute();
$reg_count = (int) $reg_stmt->fetchColumn();

/* ── Fetch all approved syllabi with uploader info ── */
// Use the already established $pdo connection
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
        col.college_name
    FROM syllabus s
    JOIN users       u ON u.id = s.uploaded_by
    LEFT JOIN colleges col ON col.id = u.college_id
    WHERE s.status = 'Approved'
    ORDER BY s.submitted_at DESC
");
$stmt->execute();
$approved_syllabi = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ── Unique filter values ── */
$colleges = array_unique(array_filter(array_column($approved_syllabi, 'college_name')));
$semesters = array_unique(array_filter(array_column($approved_syllabi, 'semester')));
$years = array_unique(array_filter(array_column($approved_syllabi, 'school_year')));
sort($colleges);
sort($semesters);
rsort($years);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared Syllabus — SCC Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                <p class="text-white-50 small fw-bold mb-0" style="font-size:.75rem;"><?= htmlspecialchars($username) ?>
                </p>
            </div>
            <nav class="nav flex-column gap-2 mb-auto">
                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
                <a href="admin_dashboard.php" class="nav-link text-white p-3 rounded hover-effect">Dashboard</a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
                <a href="syllabus_review.php" class="nav-link text-white p-3 rounded hover-effect">
                    Syllabus Review
                    <?php if ($pending_review_count > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $pending_review_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="upload_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">Upload Syllabus</a>
                <a href="manage_courses.php" class="nav-link text-white p-3 rounded hover-effect">Manage Courses</a>
                <a href="my_submissions.php" class="nav-link text-white p-3 rounded hover-effect">My Submissions</a>
                <a href="shared_syllabus.php" class="nav-link text-white active-nav-link p-3 rounded">Shared
                    Syllabus</a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">USER MANAGEMENT</div>
                <a href="registration_requests.php" class="nav-link text-white p-3 rounded hover-effect">
                    Registration Requests
                    <?php if ($reg_count > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $reg_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="manage_user.php" class="nav-link text-white p-3 rounded hover-effect">Manage Users</a>
                <a href="add_user.php" class="nav-link text-white p-3 rounded hover-effect">Add User</a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
                <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect">Profile</a>
                <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
            </nav>
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

            <!-- Stats Row -->
        <div class="row g-3 mb-4">
            <?php $stats_data = [
                ['label' => 'Approved Syllabi', 'value' => count($approved_syllabi), 'icon' => 'bi-journal-check'],
                ['label' => 'Contributing Faculty', 'value' => count(array_unique(array_column($approved_syllabi, 'faculty_name'))), 'icon' => 'bi-people'],
                ['label' => 'Active Colleges', 'value' => count($colleges), 'icon' => 'bi-building']
            ]; foreach ($stats_data as $i => $s): ?>
            <div class="col-md-4">
                <div class="scc-stat stat-total animate-in animate-in-delay-<?= $i ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label"><?= $s['label'] ?></div>
                            <div class="stat-value" style="color:var(--text)"><?= $s['value'] ?></div>
                        </div>
                        <div class="stat-icon"><i class="bi <?= $s['icon'] ?>"></i></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

            <!-- Filter / Search Bar -->
        <div class="scc-card mb-4 animate-in">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="searchInput" class="form-control border-start-0 ps-0" style="font-size:0.85rem" placeholder="Search by course or faculty...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select id="collegeFilter" class="form-select" style="font-size:0.85rem">
                            <option value="">All Colleges</option>
                            <?php foreach ($colleges as $col): ?>
                                <option value="<?= htmlspecialchars($col) ?>"><?= htmlspecialchars($col) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="semFilter" class="form-select" style="font-size:0.85rem">
                            <option value="">All Semesters</option>
                            <?php foreach ($semesters as $sem): ?>
                                <option value="<?= htmlspecialchars($sem) ?>"><?= htmlspecialchars($sem) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="yearFilter" class="form-select" style="font-size:0.85rem">
                            <option value="">All Years</option>
                            <?php foreach ($years as $yr): ?>
                                <option value="<?= htmlspecialchars($yr) ?>"><?= htmlspecialchars($yr) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-light border w-100 fw-bold" style="font-size:0.85rem" id="clearFilters">Clear All</button>
                    </div>
                </div>
            </div>
        </div>

            <!-- Syllabi Cards Grid -->
            <div id="syllabi-container" class="row g-3">
                <?php if (empty($approved_syllabi)): ?>
                    <div class="col-12 text-center py-5 text-muted">
                        <i class="bi bi-folder2-open fs-1 text-primary opacity-50 mb-3 d-block"></i>
                        <p class="mb-0">No approved syllabi currently shared in the repository.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($approved_syllabi as $row): ?>
                        <div class="col-xl-3 col-lg-4 col-md-6 syllabus-card-item animate-in" 
                             data-search="<?= strtolower(htmlspecialchars($row['course_code'] . ' ' . $row['course_title'] . ' ' . $row['faculty_name'])) ?>"
                             data-college="<?= htmlspecialchars($row['college_name'] ?? '') ?>"
                             data-sem="<?= htmlspecialchars($row['semester'] ?? '') ?>"
                             data-year="<?= htmlspecialchars($row['school_year'] ?? '') ?>">
                            <div class="scc-card h-100 border-0 shadow-sm" style="border-top: 3px solid var(--primary) !important;">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge bg-primary-light text-primary fw-bold" style="font-size: 0.65rem;"><?= htmlspecialchars($row['course_code']) ?></span>
                                        <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($row['file_path'])) ?>" target="_blank" class="text-primary"><i class="bi bi-file-earmark-pdf fs-5"></i></a>
                                    </div>
                                    <h6 class="fw-bold mb-1 text-truncate" style="color:var(--text); font-size: 0.9rem;" title="<?= htmlspecialchars($row['course_title']) ?>"><?= htmlspecialchars($row['course_title']) ?></h6>
                                    
                                    <div class="mt-3 pt-2 border-top">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="bi bi-person text-muted me-2" style="font-size: 0.8rem;"></i>
                                            <span class="small text-muted text-truncate"><?= htmlspecialchars($row['faculty_name']) ?></span>
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="bi bi-building text-muted me-2" style="font-size: 0.8rem;"></i>
                                            <span class="small text-muted text-truncate"><?= htmlspecialchars($row['college_name'] ?? '—') ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                            <div>
                                                <div style="font-size: 0.6rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Submission Date</div>
                                                <div class="fw-bold small" style="color: var(--text-secondary);"><?= date('M d, Y', strtotime($row['submitted_at'])) ?></div>
                                            </div>
                                            <div class="text-end">
                                                <div style="font-size: 0.6rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Current Status</div>
                                                <span class="badge rounded-pill bg-success-light text-success fw-bold" style="font-size: 0.6rem;">APPROVED</span>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <div>
                                                <div style="font-size: 0.6rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Reviewer</div>
                                                <div class="small fw-bold text-dark" style="font-size: 0.75rem;">Institutional VPAA</div>
                                            </div>
                                            <div class="text-end">
                                                <div style="font-size: 0.6rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Approval Stage</div>
                                                <div class="small fw-bold text-orange" style="font-size: 0.75rem;">Final Step Verified</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div id="noResults" class="text-center py-5 text-muted d-none">
                <i class="bi bi-search fs-2 mb-2 d-block opacity-50"></i>
                No syllabi match your search criteria.
            </div>
    </main>

        </div><!-- /main-content -->
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/common.js"></script>
    <script>
        function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('active');}
        (function () {
            const searchInput = document.getElementById('searchInput');
            const collegeFilter = document.getElementById('collegeFilter');
            const semFilter = document.getElementById('semFilter');
            const yearFilter = document.getElementById('yearFilter');
            const clearBtn = document.getElementById('clearFilters');
            const container = document.getElementById('syllabi-container');
            const noResults = document.getElementById('noResults');
            const cards = document.querySelectorAll('.syllabus-card-item');

            if (!container) return;

            function applyFilters() {
                const q = searchInput.value.toLowerCase().trim();
                const college = collegeFilter.value;
                const sem = semFilter.value;
                const yr = yearFilter.value;
                let visible = 0;

                cards.forEach(card => {
                    const matchSearch = !q || card.dataset.search.includes(q);
                    const matchCollege = !college || card.dataset.college === college;
                    const matchSem = !sem || card.dataset.sem === sem;
                    const matchYear = !yr || card.dataset.year === yr;
                    const show = matchSearch && matchCollege && matchSem && matchYear;
                    card.classList.toggle('d-none', !show);
                    if (show) visible++;
                });

                noResults.classList.toggle('d-none', visible > 0);
            }

            [searchInput, collegeFilter, semFilter, yearFilter].forEach(el =>
                el.addEventListener('input', applyFilters));

            clearBtn.addEventListener('click', () => {
                searchInput.value = '';
                collegeFilter.value = '';
                semFilter.value = '';
                yearFilter.value = '';
                applyFilters();
            });
        })();
    </script>
</body>

</html>