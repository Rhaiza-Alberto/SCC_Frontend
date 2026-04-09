<?php
/**
 * dept_head/shared_syllabus.php
 * Repository of all approved syllabi — fetched from DB, searchable.
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
$role_display = 'Dept Head Panel';

if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: shared_syllabus.php');
    exit();
}

if (isset($_GET['notif_id'])) {
    $notif_id = (int) $_GET['notif_id'];
    mark_single_notification_read($notif_id, $user_id);
    header('Location: syllabus_review.php');
    exit();
}

// ── DB Query: ALL approved syllabi (we filter with JS) ──────────────────────
$conn = get_db();
$sql = "
    SELECT s.*,
           COALESCE(NULLIF(s.course_code,  ''), c.course_code)  AS course_code,
           COALESCE(NULLIF(s.course_title, ''), c.course_title) AS course_title,
           u.first_name, u.last_name, 
           CONCAT(u.first_name, ' ', u.last_name) AS faculty_name,
           d.department_name,
           col.college_name
    FROM syllabus s
    LEFT JOIN courses c     ON s.course_id      = c.id
    LEFT JOIN users u       ON s.uploaded_by    = u.id
    LEFT JOIN departments d ON COALESCE(c.department_id, u.department_id) = d.id
    LEFT JOIN colleges col  ON d.college_id     = col.id
    WHERE s.status = 'Approved'
    ORDER BY s.submitted_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$approved_syllabi = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Unique stats / filters
$total_approved = count($approved_syllabi);
$major_count    = count(array_filter($approved_syllabi, fn($s) => $s['subject_type'] === 'Major'));
$minor_count    = count(array_filter($approved_syllabi, fn($s) => $s['subject_type'] === 'Minor'));
$ge_count       = count(array_filter($approved_syllabi, fn($s) => $s['subject_type'] === 'GE'));

$departments_list = array_unique(array_filter(array_column($approved_syllabi, 'department_name')));
$semesters_list  = array_unique(array_filter(array_column($approved_syllabi, 'semester')));
$years_list      = array_unique(array_filter(array_column($approved_syllabi, 'school_year')));
sort($departments_list); sort($semesters_list); rsort($years_list);

$unread_count  = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);
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
        .syllabus-card {
            transition: transform .25s ease, box-shadow .25s ease;
            border-left: 4px solid #ff8800;
        }
        .syllabus-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,.10) !important;
        }
        .badge-approved { background:#d1fae5; color:#065f46; font-size:.7rem; }
        .search-box:focus { border-color:#ff8800; box-shadow:0 0 0 .2rem rgba(255,136,0,.2); }
        #syllabi-table tbody tr.hidden-row { display: none; }
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
        <nav class="nav flex-column gap-2 mb-auto">
            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
            <a href="dept_dashboard.php" class="nav-link text-white p-3 rounded hover-effect">Dashboard</a>

            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
            <a href="syllabus_review.php" class="nav-link text-white p-3 rounded hover-effect">Syllabus Review</a>
            <a href="upload_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">Upload Syllabus</a>
            <a href="my_submissions.php" class="nav-link text-white p-3 rounded hover-effect">My Submissions</a>
            <a href="shared_syllabus.php" class="nav-link text-white p-3 rounded active-nav-link">Shared Syllabus</a>

            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">USER MANAGEMENT</div>
            <a href="registration_requests.php" class="nav-link text-white p-3 rounded hover-effect">Registration Requested</a>

            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
            <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect">Profile</a>
            <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
        </nav>
    </div>

    <!-- ── Main Content ── -->
    <div class="main-content flex-grow-1 p-5" style="margin-left:260px;">

        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h3 class="text-orange font-serif fw-bold mb-0">Shared Syllabus Repository</h3>
                <p class="text-muted small mb-0">Browse and download VPAA-approved syllabi across all departments.</p>
            </div>

            <!-- Notifications -->
            <div class="dropdown">
                <div class="position-relative" style="cursor:pointer;" data-bs-toggle="dropdown">
                    <i class="bi bi-bell fs-4 text-secondary"></i>
                    <?php if ($unread_count > 0): ?><span class="notif-dot"></span><?php endif; ?>
                </div>
                <ul class="dropdown-menu dropdown-menu-end shadow" style="width:320px;max-height:400px;overflow-y:auto;">
                    <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom">
                        <strong>Notifications</strong>
                        <?php if ($unread_count > 0): ?>
                            <a href="?mark_read=1" class="text-decoration-none small text-orange">Mark all read</a>
                        <?php endif; ?>
                    </li>
                    <?php if (empty($notifications)): ?>
                        <li class="px-3 py-3 text-center text-muted small">No notifications</li>
                    <?php else: foreach ($notifications as $n): ?>
                        <li class="border-bottom <?= !$n['is_read'] ? 'bg-light' : '' ?>">
                            <a href="?notif_id=<?= $n['id'] ?>" class="d-block px-3 py-2 text-decoration-none">
                                <p class="mb-0 small text-dark"><?= htmlspecialchars($n['message']) ?></p>
                                <span class="text-muted" style="font-size:.7rem;"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></span>
                            </a>
                        </li>
                    <?php endforeach; endif; ?>
                    <li class="border-top">
                        <a href="dept_dashboard.php" class="d-block text-center text-orange text-decoration-none small fw-bold py-2">
                            Back to Dashboard
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- ── Stats Row ── -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card premium-card border-0 shadow-sm p-3 syllabus-card" style="border-left-color:#ff8800;">
                    <div class="text-muted small fw-bold">TOTAL SHARED</div>
                    <div class="h3 fw-bold mb-0"><?= $total_approved ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card premium-card border-0 shadow-sm p-3 syllabus-card" style="border-left-color:#0d6efd;">
                    <div class="text-muted small fw-bold">MAJOR SUBJECTS</div>
                    <div class="h3 fw-bold mb-0"><?= $major_count ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card premium-card border-0 shadow-sm p-3 syllabus-card" style="border-left-color:#6610f2;">
                    <div class="text-muted small fw-bold">MINOR SUBJECTS</div>
                    <div class="h3 fw-bold mb-0"><?= $minor_count ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card premium-card border-0 shadow-sm p-3 syllabus-card" style="border-left-color:#198754;">
                    <div class="text-muted small fw-bold">GE SUBJECTS</div>
                    <div class="h3 fw-bold mb-0"><?= $ge_count ?></div>
                </div>
            </div>
        </div>

        <!-- ── Search & Filters ── -->
        <div class="card premium-card p-4 border-0 shadow-sm bg-white mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="search-input" class="form-control border-start-0 ps-0 search-box" placeholder="Search course code, title, or faculty...">
                    </div>
                </div>
                <div class="col-md-2">
                    <select id="filter-dept" class="form-select search-box">
                        <option value="">All Departments</option>
                        <?php foreach ($departments_list as $d): ?>
                            <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filter-type" class="form-select search-box">
                        <option value="">All Types</option>
                        <option value="Major">Major</option>
                        <option value="Minor">Minor</option>
                        <option value="GE">GE</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filter-year" class="form-select search-box">
                        <option value="">All Years</option>
                        <?php foreach ($years_list as $y): ?>
                            <option value="<?= htmlspecialchars($y) ?>"><?= htmlspecialchars($y) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-orange w-100 rounded shadow-sm" onclick="resetFilters()">Reset Filters</button>
                </div>
            </div>
        </div>

        <!-- ── Main Table ── -->
        <div class="card premium-card p-0 border-0 shadow-sm bg-white overflow-hidden mb-5">
            <div class="table-responsive">
                <table class="table table-hover align-middle table-premium mb-0" id="syllabi-table">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 text-secondary small py-3">COURSE</th>
                            <th class="text-secondary small py-3">TYPE</th>
                            <th class="text-secondary small py-3">DEPARTMENT</th>
                            <th class="text-secondary small py-3">YEAR/SEM</th>
                            <th class="text-secondary small py-3">FACULTY</th>
                            <th class="text-center text-secondary small py-3">FILE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($approved_syllabi)): ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted small">No approved syllabi found in the repository.</td></tr>
                        <?php else: foreach ($approved_syllabi as $s): ?>
                            <tr class="syllabus-row" 
                                data-dept="<?= htmlspecialchars($s['department_name']) ?>"
                                data-type="<?= htmlspecialchars($s['subject_type']) ?>"
                                data-year="<?= htmlspecialchars($s['school_year']) ?>">
                                <td class="ps-4">
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold small text-dark row-course-code"><?= htmlspecialchars($s['course_code']) ?></span>
                                        <span class="text-muted small text-truncate row-course-title" style="max-width:200px;"><?= htmlspecialchars($s['course_title']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                        $type_class = match($s['subject_type']){ 'Major'=>'info','Minor'=>'primary','default'=>'success'};
                                        echo '<span class="badge bg-'.$type_class.' bg-opacity-10 text-'.$type_class.' border border-'.$type_class.'-subtle rounded-pill px-3" style="font-size:.65rem;">'.$s['subject_type'].'</span>';
                                    ?>
                                </td>
                                <td class="small text-muted"><?= htmlspecialchars($s['department_name'] ?? 'General') ?></td>
                                <td>
                                    <div class="small fw-bold"><?= htmlspecialchars($s['school_year']) ?></div>
                                    <div class="text-muted" style="font-size:.65rem;"><?= htmlspecialchars($s['semester']) ?></div>
                                </td>
                                <td class="small row-faculty-name">
                                    <?= htmlspecialchars($s['faculty_name']) ?>
                                </td>
                                <td class="text-center">
                                    <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($s['file_path'])) ?>" 
                                       target="_blank" class="btn btn-sm btn-link text-orange p-0">
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

<script>
const searchInput = document.getElementById('search-input');
const filterDept  = document.getElementById('filter-dept');
const filterType  = document.getElementById('filter-type');
const filterYear  = document.getElementById('filter-year');
const tableRows   = document.querySelectorAll('.syllabus-row');

function applyFilters() {
    const query = searchInput.value.toLowerCase();
    const dept  = filterDept.value;
    const type  = filterType.value;
    const year  = filterYear.value;

    tableRows.forEach(row => {
        const text = row.innerText.toLowerCase();
        const rowDept = row.getAttribute('data-dept');
        const rowType = row.getAttribute('data-type');
        const rowYear = row.getAttribute('data-year');

        const matchesSearch = text.includes(query);
        const matchesDept   = !dept || rowDept === dept;
        const matchesType   = !type || rowType === type;
        const matchesYear   = !year || rowYear === year;

        if (matchesSearch && matchesDept && matchesType && matchesYear) {
            row.classList.remove('hidden-row');
        } else {
            row.classList.add('hidden-row');
        }
    });
}

function resetFilters() {
    searchInput.value = '';
    filterDept.value = '';
    filterType.value = '';
    filterYear.value = '';
    applyFilters();
}

searchInput.addEventListener('input', applyFilters);
filterDept.addEventListener('change', applyFilters);
filterType.addEventListener('change', applyFilters);
filterYear.addEventListener('change', applyFilters);
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>