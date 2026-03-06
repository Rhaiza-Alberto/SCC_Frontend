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
$dept_id      = $_SESSION['department_id'] ?? null;

if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: shared_syllabus.php');
    exit();
}

// ── Search/Filter params ─────────────────────────────────────────────────────
$search      = trim($_GET['search']       ?? '');
$type_filter = trim($_GET['subject_type'] ?? '');
$dept_filter = isset($_GET['dept_filter']) ? (int) $_GET['dept_filter'] : null;

// ── DB Query: approved syllabi ───────────────────────────────────────────────
$conn = get_db();
$params = [];
$sql = "
    SELECT s.*,
           COALESCE(NULLIF(s.course_code,  ''), c.course_code)  AS course_code,
           COALESCE(NULLIF(s.course_title, ''), c.course_title) AS course_title,
           u.first_name, u.last_name, u.email AS uploader_email,
           d.department_name,
           col.college_name
    FROM syllabus s
    LEFT JOIN courses c     ON s.course_id      = c.id
    LEFT JOIN users u       ON s.uploaded_by    = u.id
    LEFT JOIN departments d ON COALESCE(c.department_id, u.department_id) = d.id
    LEFT JOIN colleges col  ON d.college_id     = col.id
    WHERE s.status = 'Approved'
";

// Dept head sees all departments' approved syllabi by default (shared repository)
if ($dept_filter) {
    $sql .= " AND COALESCE(c.department_id, u.department_id) = ?";
    $params[] = $dept_filter;
}
if ($search !== '') {
    $sql .= " AND (
        COALESCE(NULLIF(s.course_code, ''), c.course_code)  LIKE ?  OR
        COALESCE(NULLIF(s.course_title,''), c.course_title) LIKE ?  OR
        u.first_name LIKE ? OR u.last_name LIKE ?
    )";
    $like     = '%' . $search . '%';
    $params[] = $like; $params[] = $like;
    $params[] = $like; $params[] = $like;
}
if ($type_filter !== '') {
    $sql .= " AND s.subject_type = ?";
    $params[] = $type_filter;
}
$sql .= " ORDER BY s.submitted_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$shared_syllabi = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Departments list for filter dropdown
$departments = get_departments();

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
        .text-orange { color: #ff8800 !important; }
        .notif-dot   { position:absolute;top:2px;right:2px;width:10px;height:10px;
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
        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
        <a href="dept_dashboard.php"        class="nav-link text-white p-3 rounded hover-effect">Dashboard</a>
        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
        <a href="syllabus_review.php"       class="nav-link text-white p-3 rounded hover-effect">Syllabus Review</a>
        <a href="upload_syllabus.php"       class="nav-link text-white p-3 rounded hover-effect">Upload Syllabus</a>
        <a href="my_submissions.php"        class="nav-link text-white p-3 rounded hover-effect">My Submissions</a>
        <a href="shared_syllabus.php"       class="nav-link text-white active-nav-link p-3 rounded">Shared Syllabus</a>
        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">ACCOUNT MANAGEMENT</div>
        <a href="registration_requests.php" class="nav-link text-white p-3 rounded hover-effect">Registration Requests</a>
        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
        <a href="profile.php"               class="nav-link text-white p-3 rounded hover-effect">Profile</a>
        <a href="../logout.php"             class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 p-5" style="margin-left:260px;">

        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 class="text-orange font-serif fw-bold mb-0">Shared Syllabus</h2>
                <p class="text-muted small mb-0">
                    <?= count($shared_syllabi) ?> approved file(s) in the repository
                </p>
            </div>
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
                        <li class="px-3 py-2 border-bottom <?= !$n['is_read'] ? 'bg-light' : '' ?>">
                            <p class="mb-0 small"><?= htmlspecialchars($n['message']) ?></p>
                            <span class="text-muted" style="font-size:.7rem;"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></span>
                        </li>
                    <?php endforeach; endif; ?>
                </ul>
            </div>
        </div>

        <!-- Search & Filter -->
        <div class="card premium-card p-4 mb-4 shadow-sm">
            <h5 class="card-title font-serif fw-bold mb-3 text-orange">Search Repository</h5>
            <form method="GET" action="shared_syllabus.php">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control"
                               placeholder="Course code, title, or instructor"
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="subject_type" class="form-select">
                            <option value="">All Subject Types</option>
                            <option value="Institutional Subject"  <?= $type_filter === 'Institutional Subject'  ? 'selected' : '' ?>>Institutional Subject</option>
                            <option value="General Education (GE)" <?= $type_filter === 'General Education (GE)' ? 'selected' : '' ?>>General Education (GE)</option>
                            <option value="Core Subject"           <?= $type_filter === 'Core Subject'           ? 'selected' : '' ?>>Core Subject</option>
                            <option value="Professional Subjects"  <?= $type_filter === 'Professional Subjects'  ? 'selected' : '' ?>>Professional Subjects</option>
                            <option value="Mandatory / Elect Subject" <?= $type_filter === 'Mandatory / Elect Subject' ? 'selected' : '' ?>>Mandatory / Elect Subject</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="dept_filter" class="form-select">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= $dept_filter === (int)$d['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d['department_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-orange w-100">
                            <i class="bi bi-search me-1"></i>Search
                        </button>
                        <?php if ($search || $type_filter || $dept_filter): ?>
                            <a href="shared_syllabus.php" class="btn btn-outline-secondary" title="Clear">
                                <i class="bi bi-x"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- Repository Table -->
        <div class="card premium-card p-4 shadow-sm">
            <h5 class="card-title font-serif fw-bold mb-3 text-orange">Department Syllabus Repository</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-premium">
                    <thead class="table-light">
                        <tr>
                            <th class="text-secondary small">#</th>
                            <th class="text-secondary small">COURSE</th>
                            <th class="text-secondary small d-none d-lg-table-cell">TYPE</th>
                            <th class="text-secondary small d-none d-xl-table-cell">SEM / YEAR</th>
                            <th class="text-secondary small">STATUS</th>
                            <th class="text-secondary small text-center">FILE</th>
                            <th class="text-secondary small">INSTRUCTOR</th>
                            <th class="text-secondary small">DEPT</th>
                            <th class="text-secondary small">DATE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($shared_syllabi)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">
                                    <i class="bi bi-folder2-open fs-2 opacity-25 d-block mb-2"></i>
                                    No approved syllabi found<?= $search ? ' for "' . htmlspecialchars($search) . '"' : '' ?>
                                </td>
                            </tr>
                        <?php else: foreach ($shared_syllabi as $i => $syl): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <span class="fw-bold small"><?= htmlspecialchars($syl['course_code']) ?></span><br>
                                    <span class="text-muted text-truncate d-block" style="font-size:.7rem;max-width:140px;">
                                        <?= htmlspecialchars($syl['course_title']) ?>
                                    </span>
                                </td>
                                <td class="d-none d-lg-table-cell small"><?= htmlspecialchars($syl['subject_type'] ?? '—') ?></td>
                                <td class="d-none d-xl-table-cell small">
                                    <?= htmlspecialchars($syl['semester']    ?? '—') ?><br>
                                    <span class="text-muted" style="font-size:.7rem;"><?= htmlspecialchars($syl['school_year'] ?? '') ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-success bg-opacity-25 text-success border border-success rounded-pill px-3" style="font-size:.75rem;">
                                        Approved
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($syl['file_path'])) ?>"
                                       target="_blank" rel="noopener" class="btn btn-sm btn-link text-orange p-0">
                                        <i class="bi bi-file-earmark-pdf fs-5"></i>
                                    </a>
                                </td>
                                <td>
                                    <span class="fw-bold small"><?= htmlspecialchars($syl['first_name'] . ' ' . $syl['last_name']) ?></span><br>
                                    <span class="text-muted" style="font-size:.7rem;"><?= htmlspecialchars($syl['uploader_email']) ?></span>
                                </td>
                                <td class="small"><?= htmlspecialchars($syl['department_name'] ?? '—') ?></td>
                                <td class="small"><?= date('M d, Y', strtotime($syl['submitted_at'])) ?></td>
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