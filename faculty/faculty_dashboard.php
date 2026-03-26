<?php
/**
 * faculty_dashboard.php
 * Faculty dashboard — all data from database.
 */
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

ensure_role_in_session();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$role_display = 'Faculty Panel';

// Handle mark-all-read
if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: faculty_dashboard.php');
    exit();
}

// Fetch all submissions from DB
$submissions = get_faculty_submissions($user_id);

$total = count($submissions);
$approved = count(array_filter($submissions, fn($s) => $s['status'] === 'Approved'));
$pending = count(array_filter($submissions, fn($s) => $s['status'] === 'Pending'));
$rejected = count(array_filter($submissions, fn($s) => $s['status'] === 'Rejected'));

// Recent 5
$recent = array_slice($submissions, 0, 5);

// Unique courses status list
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

// Notifications
$unread_count = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - SCC-CCS Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .text-orange {
            color: #ff8800 !important;
        }

        .border-orange {
            border-color: #ff8800 !important;
        }

        .notif-dot {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 10px;
            height: 10px;
            background: #dc3545;
            border-radius: 50%;
            border: 2px solid #fff;
        }

        .stat-card {
            transition: transform .3s ease, box-shadow .3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, .1) !important;
        }
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
            <a href="faculty_dashboard.php" class="nav-link text-white active-nav-link p-3 rounded">Dashboard</a>

            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
            <a href="upload_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">Upload Syllabus</a>
            <a href="my_submissions.php" class="nav-link text-white p-3 rounded hover-effect">My Submissions</a>
            <a href="shared_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">Shared Syllabus</a>

            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
            <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect">Profile</a>
            <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-grow-1 p-5" style="margin-left:260px;">

            <div class="d-flex justify-content-between align-items-center mb-5">
                <h2 class="text-orange font-serif fw-bold">Welcome, <?= htmlspecialchars($username) ?>!</h2>

                <!-- Notification Bell -->
                <div class="position-relative" style="cursor:pointer;" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell fs-4 text-secondary"></i>
                    <?php if ($unread_count > 0): ?>
                        <span class="notif-dot"></span>
                    <?php endif; ?>
                    <ul class="dropdown-menu dropdown-menu-end shadow"
                        style="width:320px;max-height:400px;overflow-y:auto;">
                        <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom">
                            <strong>Notifications</strong>
                            <?php if ($unread_count > 0): ?>
                                <a href="?mark_read=1" class="text-decoration-none small text-orange">Mark all read</a>
                            <?php endif; ?>
                        </li>
                        <?php if (empty($notifications)): ?>
                            <li class="px-3 py-3 text-center text-muted small">No notifications yet</li>
                        <?php else:
                            foreach ($notifications as $n): ?>
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

            <!-- Stat Cards -->
            <div class="row g-4 mb-4">
                <?php
                $stats = [
                    ['label' => 'Total Submissions', 'value' => $total, 'color' => '#ff8800', 'icon' => 'bi-files', 'sub' => 'All uploaded syllabi'],
                    ['label' => 'Approved', 'value' => $approved, 'color' => '#28a745', 'icon' => 'bi-check-circle', 'sub' => 'Validated content'],
                    ['label' => 'Pending', 'value' => $pending, 'color' => '#ffc107', 'icon' => 'bi-clock-history', 'sub' => 'Awaiting review'],
                    ['label' => 'Rejected', 'value' => $rejected, 'color' => '#dc3545', 'icon' => 'bi-x-circle', 'sub' => 'Needs revision'],
                ];
                foreach ($stats as $s): ?>
                    <div class="col-md-3">
                        <div class="card stat-card shadow-sm border-0 bg-white"
                            style="border-left:5px solid <?= $s['color'] ?> !important;">
                            <div class="stat-card-content p-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="text-uppercase fw-bold text-muted small mb-0"><?= $s['label'] ?></h6>
                                    <i class="bi <?= $s['icon'] ?> opacity-50 fs-4" style="color:<?= $s['color'] ?>"></i>
                                </div>
                                <h1 class="display-5 fw-bold text-dark mb-0"><?= $s['value'] ?></h1>
                                <p class="text-muted small mb-0 mt-1"><?= $s['sub'] ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Course Syllabi Status -->
            <div class="row g-4 mb-5">
                <div class="col-md-12">
                    <div class="card premium-card p-4 shadow-sm h-100">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title font-serif fw-bold mb-0 text-orange h-100">My Course Syllabi Status
                            </h5>
                            <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3 py-1 small">
                                <?= get_current_school_year() ?>
                            </span>
                        </div>
                        <?php if (empty($my_courses)): ?>
                            <div class="text-center py-4 text-muted small">No courses submitted yet.
                                <a href="upload_syllabus.php" class="text-orange">Upload one →</a>
                            </div>
                        <?php else:
                            foreach ($my_courses as $course):
                                $badge_class = match ($course['status']) {
                                    'Approved' => 'badge-approved',
                                    'Pending'  => 'badge-pending',
                                    default    => 'badge-rejected',
                                };
                            ?>
                                <div class="d-flex justify-content-between align-items-center p-3 mb-2 rounded-3 bg-light border-start border-4"
                                    style="border-color:#ff8800 !important;">
                                    <div>
                                        <h6 class="mb-1 fw-bold"><?= htmlspecialchars($course['code']) ?></h6>
                                        <p class="text-muted small mb-0"><?= htmlspecialchars($course['title']) ?></p>
                                    </div>
                                    <span class="badge <?= $badge_class ?> rounded-pill px-3">
                                        <?= format_syllabus_status($course['status'], $course['current_role'], $course['rejecting_role']) ?>
                                    </span>
                                </div>
                            <?php endforeach; endif; ?>
                    </div>
                </div>


                <!-- Filter -->
                <div class="card premium-card p-4 mb-4">
                    <h5 class="card-title font-serif fw-bold mb-3 text-orange">Filter Submissions</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input type="text" id="filterSearch" class="form-control"
                                placeholder="Search course code / title">
                        </div>
                        <div class="col-md-3">
                            <select id="filterStatus" class="form-select">
                                <option value="">All statuses</option>
                                <option value="Pending">Pending</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="date" id="filterDate" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-dark w-100" onclick="applyFilter()">Filter</button>
                        </div>
                    </div>
                </div>

                <!-- My Submissions Table -->
                <div class="card premium-card p-4 mb-5 border-0 shadow-sm bg-white">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="card-title font-serif fw-bold mb-0 text-orange">My Submissions</h5>
        <button class="btn btn-sm btn-clean d-flex align-items-center" onclick="window.print()">
            <i class="bi bi-printer me-2"></i> PRINT
        </button>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle table-premium" id="submissionsTable">
            <thead class="table-light">
                <tr class="text-muted small text-uppercase border-bottom">
                    <th class="py-3 px-3">#</th>
                    <th class="py-3">COURSE</th>
                    <th class="py-3 d-none d-xl-table-cell">SEM / YEAR</th>
                    <th class="py-3">STATUS</th>
                    <th class="py-3">COMMENT</th>
                    <th class="py-3 text-center">FILE</th>
                    <th class="py-3">SUBMITTED</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($submissions)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5 small">
                            No submissions yet.
                            <a href="upload_syllabus.php" class="text-orange text-decoration-none fw-bold">Upload one now →</a>
                        </td>
                    </tr>
                <?php else:
                    foreach ($submissions as $i => $sub):
                        $sc = match ($sub['status']) {
                            'Approved' => 'badge-approved',
                            'Pending'  => 'badge-pending',
                            default    => 'badge-rejected',
                        };
                        $file_date = date('Y-m-d', strtotime($sub['submitted_at']));
                        ?>
                        <tr class="submission-row" 
                            data-code="<?= strtolower($sub['course_code']) ?>"
                            data-title="<?= strtolower($sub['course_title']) ?>"
                            data-status="<?= $sub['status'] ?>" 
                            data-date="<?= $file_date ?>">
                            <td class="px-3"><?= $i + 1 ?></td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold small"><?= htmlspecialchars($sub['course_code']) ?></span>
                                    <span class="text-muted text-truncate" style="font-size:.7rem; max-width:150px;">
                                        <?= htmlspecialchars($sub['course_title']) ?>
                                    </span>
                                </div>
                            </td>
                            <td class="d-none d-xl-table-cell small text-secondary">
                                <?= htmlspecialchars($sub['school_year'] ?? '—') ?>
                            </td>
                            <td>
                                <span class="badge <?= $sc ?> rounded-pill px-3 py-1" style="font-size:.75rem;">
                                    <?= format_syllabus_status($sub['status'], $sub['current_stage_role'] ?? null, $sub['rejecting_role'] ?? null) ?>
                                </span>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars($sub['reject_comment'] ?? '—') ?></td>
                            <td class="text-center">
                                <a href="view_syllabus.php?file=<?= urlencode(basename($sub['file_path'])) ?>"
                                   target="_blank" rel="noopener" class="text-orange">
                                    <i class="bi bi-file-earmark-pdf fs-5"></i>
                                </a>
                            </td>
                            <td class="small text-muted"><?= date('M d, Y', strtotime($sub['submitted_at'])) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

                <!-- Recent Shared Syllabus -->
                <div class="card premium-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title font-serif fw-bold mb-0 text-orange">Recent Shared Syllabus</h5>
                        <a href="shared_syllabus.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                            View All Repository
                        </a>
                    </div>
                    <?php
                    $shared = array_slice(get_shared_syllabi(), 0, 5);
                    ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-premium">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-secondary small">#</th>
                                    <th class="text-secondary small">COURSE</th>
                                    <th class="text-secondary small d-none d-xl-table-cell">YEAR</th>
                                    <th class="text-secondary small">STATUS</th>
                                    <th class="text-secondary small text-center">FILE</th>
                                    <th class="text-secondary small">UPLOADER</th>
                                    <th class="text-secondary small">SUBMITTED</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($shared)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            No approved syllabi in the shared repository yet.
                                        </td>
                                    </tr>
                                <?php else:
                                    foreach ($shared as $i => $sh): ?>
                                        <tr>
                                            <td><?= $i + 1 ?></td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span
                                                        class="fw-bold small"><?= htmlspecialchars($sh['course_code']) ?></span>
                                                    <span class="text-muted text-truncate"
                                                        style="font-size:.7rem;max-width:150px;">
                                                        <?= htmlspecialchars($sh['course_title']) ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="d-none d-xl-table-cell small">
                                                <?= htmlspecialchars($sh['school_year'] ?? '—') ?>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-success bg-opacity-25 text-success border border-success rounded-pill px-3"
                                                    style="font-size:.75rem;">Approved</span>
                                            </td>
                                            <td class="text-center">
                                                <a href="view_syllabus.php?file=<?= urlencode(basename($sh['file_path'])) ?>"
                                                    target="_blank" rel="noopener" class="btn btn-sm btn-link text-orange p-0">
                                                    <i class="bi bi-file-earmark-pdf fs-5"></i>
                                                </a>
                                            </td>
                                            <td class="small">
                                                <?= htmlspecialchars($sh['first_name'] . ' ' . $sh['last_name']) ?>
                                            </td>
                                            <td class="small"><?= date('M d, Y', strtotime($sh['submitted_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div><!-- /main-content -->
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            function applyFilter() {
                const search = document.getElementById('filterSearch').value.toLowerCase();
                const status = document.getElementById('filterStatus').value;
                const date = document.getElementById('filterDate').value;

                document.querySelectorAll('.submission-row').forEach(row => {
                    const matchSearch = !search ||
                        row.dataset.code.includes(search) ||
                        row.dataset.title.includes(search);
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