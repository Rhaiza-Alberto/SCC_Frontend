 <?php
/**
 * shared_syllabus.php
 * Shows all approved syllabi from the database (shared repository).
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
$role_display = 'Faculty Panel';

// Handle mark-all-read
if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: shared_syllabus.php');
    exit();
}

$unread_count  = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);

// Fetch all approved syllabi
$all_shared = get_shared_syllabi();

// Search / filter
$search       = trim($_GET['search']       ?? '');
$filter_type  = trim($_GET['subject_type'] ?? '');

$shared = $all_shared;

// Note: shared syllabi don't have subject_type stored in DB currently,
// so we filter on course_code / course_title / uploader name.
if ($search !== '') {
    $needle = strtolower($search);
    $shared = array_filter($shared, function($s) use ($needle) {
        return str_contains(strtolower($s['course_code']),  $needle)
            || str_contains(strtolower($s['course_title']), $needle)
            || str_contains(strtolower($s['first_name'] . ' ' . $s['last_name']), $needle);
    });
}
$shared = array_values($shared);
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
        .notif-dot { position:absolute;top:2px;right:2px;width:10px;height:10px;
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
            <p class="text-white-50 small fw-bold mb-0" style="font-size:.75rem;">
                <?= htmlspecialchars($username) ?>
            </p>
        </div>
        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
        <a href="faculty_dashboard.php" class="nav-link text-white p-3 rounded hover-effect">Dashboard</a>

        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
        <a href="upload_syllabus.php"  class="nav-link text-white p-3 rounded hover-effect">Upload Syllabus</a>
        <a href="my_submissions.php"   class="nav-link text-white p-3 rounded hover-effect">My Submissions</a>
        <a href="shared_syllabus.php"  class="nav-link text-white active-nav-link p-3 rounded">Shared Syllabus</a>

        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
        <a href="profile.php"   class="nav-link text-white p-3 rounded hover-effect">Profile</a>
        <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 p-5" style="margin-left:260px;">

        <div class="d-flex justify-content-between align-items-center mb-5">
            <h2 class="text-orange font-serif fw-bold">Shared Syllabus Repository</h2>

            <!-- Notification Bell -->
            <div class="position-relative" style="cursor:pointer;" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-bell fs-4 text-secondary"></i>
                <?php if ($unread_count > 0): ?>
                    <span class="notif-dot"></span>
                <?php endif; ?>
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
                            <span class="text-muted" style="font-size:.7rem;">
                                <?= date('M d, Y h:i A', strtotime($n['created_at'])) ?>
                            </span>
                        </li>
                    <?php endforeach; endif; ?>
                </ul>
            </div>
        </div>

        <!-- Search / Filter -->
        <div class="card premium-card p-4 mb-5">
            <h5 class="card-title font-serif fw-bold mb-3 text-orange">Search Repository</h5>
            <form method="GET" action="shared_syllabus.php">
                <div class="row g-3">
                    <div class="col-md-8">
                        <input type="text" class="form-control" name="search"
                               placeholder="Search course code / title / instructor"
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-dark w-100">
                            <i class="bi bi-search me-1"></i>Search
                        </button>
                    </div>
                    <?php if ($search): ?>
                    <div class="col-md-2">
                        <a href="shared_syllabus.php" class="btn btn-outline-secondary w-100">Clear</a>
                    </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Shared Syllabus Table -->
        <div class="card premium-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title font-serif fw-bold mb-0 text-orange">Department Syllabus Repository</h5>
                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1 small">
                    <?= count($shared) ?> Approved Syllab<?= count($shared) === 1 ? 'us' : 'i' ?>
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-premium">
                    <thead class="table-light">
                        <tr>
                            <th class="text-secondary small">#</th>
                            <th class="text-secondary small">COURSE</th>
                            <th class="text-secondary small d-none d-xl-table-cell">SCHOOL YEAR</th>
                            <th class="text-secondary small">DEPARTMENT</th>
                            <th class="text-secondary small">STATUS</th>
                            <th class="text-secondary small text-center">FILE</th>
                            <th class="text-secondary small">UPLOADER</th>
                            <th class="text-secondary small">SUBMITTED</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($shared)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <?= $search ? 'No results found for "' . htmlspecialchars($search) . '".' : 'No approved syllabi in the repository yet.' ?>
                                </td>
                            </tr>
                        <?php else: foreach ($shared as $i => $s): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold small"><?= htmlspecialchars($s['course_code']) ?></span>
                                        <span class="text-muted text-truncate" style="font-size:.7rem;max-width:160px;">
                                            <?= htmlspecialchars($s['course_title']) ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="d-none d-xl-table-cell small">
                                    <?= htmlspecialchars($s['school_year'] ?? '—') ?>
                                </td>
                                <td class="small"><?= htmlspecialchars($s['department_name'] ?? '—') ?></td>
                                <td>
                                    <span class="badge bg-success bg-opacity-25 text-success border border-success rounded-pill px-3"
                                          style="font-size:.75rem;">Approved</span>
                                </td>
                                <td class="text-center">
                                    <a href="view_syllabus.php?file=<?= urlencode(basename($s['file_path'])) ?>"
                                       target="_blank" rel="noopener" class="btn btn-sm btn-link text-orange p-0">
                                        <i class="bi bi-file-earmark-pdf fs-5"></i>
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold small">
                                            <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?>
                                        </span>
                                        <span class="text-muted" style="font-size:.7rem;">
                                            <?= htmlspecialchars($s['email']) ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="small"><?= date('M d, Y', strtotime($s['submitted_at'])) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /main-content -->
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>