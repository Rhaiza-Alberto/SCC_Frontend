 <?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Get user information from session
$username = $_SESSION['username'] ?? 'User';
$email    = $_SESSION['email'] ?? '';
$role     = $_SESSION['role'] ?? 'faculty';
$role_display = 'Faculty Panel';

// ── Read submissions from session, filtered to this user ──────────────────────
$all_submissions  = $_SESSION['submissions'] ?? [];
$my_submissions   = array_values(array_filter($all_submissions, fn($s) => ($s['uploader_email'] ?? '') === $email));

$total    = count($my_submissions);
$approved = count(array_filter($my_submissions, fn($s) => $s['status'] === 'Approved'));
$pending  = count(array_filter($my_submissions, fn($s) => $s['status'] === 'Pending'));
$rejected = count(array_filter($my_submissions, fn($s) => $s['status'] === 'Rejected'));

// ── Recent 5 for the submissions table ───────────────────────────────────────
$recent_submissions = array_slice(array_reverse($my_submissions), 0, 5);

// ── Course syllabi status cards (unique courses from submissions) ─────────────
$my_courses = [];
foreach ($my_submissions as $sub) {
    $code = $sub['course_code'];
    if (!isset($my_courses[$code])) {
        $my_courses[$code] = [
            'code'   => $code,
            'title'  => $sub['course_title'],
            'status' => $sub['status'],
        ];
    }
}
$my_courses = array_values($my_courses);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - SCC-CCS Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>

<body class="bg-light">
<div class="d-flex">

    <!-- Sidebar -->
    <div class="sidebar sidebar-premium text-white p-2 min-vh-100 d-flex flex-column"
         style="width: 260px; position: fixed; z-index: 1100;">
        <div class="text-center mb-3 mt-2">
            <img src="../css/logo.png" alt="CCS Logo" class="rounded-circle mb-2"
                 style="width: 80px; height: 80px; border: 2px solid rgba(255,136,0,0.5); padding: 3px;">
            <h5 class="font-serif fw-bold text-orange mb-0"><?php echo $role_display; ?></h5>
            <p class="text-white-50 small fw-bold mb-0" style="font-size: 0.75rem;">
                <?php echo htmlspecialchars($username); ?>
            </p>
        </div>

        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
        <a href="faculty_dashboard.php" class="nav-link text-white active-nav-link p-3 rounded">Dashboard</a>

        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
        <a href="upload_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">Upload Syllabus</a>
        <a href="my_submissions.php"  class="nav-link text-white p-3 rounded hover-effect">My Submissions</a>
        <a href="shared_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">Shared Syllabus</a>

        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
        <a href="profile.php"    class="nav-link text-white p-3 rounded hover-effect">Profile</a>
        <a href="../logout.php"  class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 p-5" style="margin-left: 260px;">

        <div class="d-flex justify-content-between align-items-center mb-5">
            <h2 class="text-orange font-serif fw-bold">Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
            <div class="notification-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                     class="bi bi-bell" viewBox="0 0 16 16">
                    <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zM8 1.918l-.797.161A4.002 4.002 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4.002 4.002 0 0 0-3.203-3.92L8 1.917zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5.002 5.002 0 0 1 13 6c0 .88.32 4.2 1.22 6z"/>
                </svg>
                <span class="notification-badge-dot"></span>
            </div>
        </div>

        <!-- ── Stat Cards ─────────────────────────────────────────────────── -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card shadow-sm border-0 bg-white" style="border-left: 5px solid #ff8800 !important;">
                    <div class="stat-card-content p-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="text-uppercase fw-bold text-muted small mb-0">Total Submissions</h6>
                            <i class="bi bi-files text-orange opacity-50 fs-4"></i>
                        </div>
                        <h1 class="display-5 fw-bold text-dark mb-0"><?php echo $total; ?></h1>
                        <p class="text-muted small mb-0 mt-1">All uploaded syllabi</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm border-0 bg-white" style="border-left: 5px solid #28a745 !important;">
                    <div class="stat-card-content p-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="text-uppercase fw-bold text-muted small mb-0">Approved</h6>
                            <i class="bi bi-check-circle text-success opacity-50 fs-4"></i>
                        </div>
                        <h1 class="display-5 fw-bold text-dark mb-0"><?php echo $approved; ?></h1>
                        <p class="text-muted small mb-0 mt-1">Validated content</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm border-0 bg-white" style="border-left: 5px solid #ffc107 !important;">
                    <div class="stat-card-content p-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="text-uppercase fw-bold text-muted small mb-0">Pending</h6>
                            <i class="bi bi-clock-history text-warning opacity-50 fs-4"></i>
                        </div>
                        <h1 class="display-5 fw-bold text-dark mb-0"><?php echo $pending; ?></h1>
                        <p class="text-muted small mb-0 mt-1">Awaiting review</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card shadow-sm border-0 bg-white" style="border-left: 5px solid #dc3545 !important;">
                    <div class="stat-card-content p-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="text-uppercase fw-bold text-muted small mb-0">Rejected</h6>
                            <i class="bi bi-x-circle text-danger opacity-50 fs-4"></i>
                        </div>
                        <h1 class="display-5 fw-bold text-dark mb-0"><?php echo $rejected; ?></h1>
                        <p class="text-muted small mb-0 mt-1">Needs revision</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Course Syllabi Status + Notifications ──────────────────────── -->
        <div class="row g-4 mb-5">
            <div class="col-md-8">
                <div class="card premium-card p-4 shadow-sm h-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title font-serif fw-bold mb-0 text-orange">My Course Syllabi Status</h5>
                        <span class="badge bg-orange bg-opacity-10 text-orange rounded-pill px-3 py-1 small">Current Semester</span>
                    </div>
                    <div class="syllabus-status-list">
                        <?php if (empty($my_courses)): ?>
                            <div class="text-center py-4 text-muted small">No courses submitted yet</div>
                        <?php else: foreach ($my_courses as $course):
                            if ($course['status'] === 'Approved') {
                                $badge = '<span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Approved</span>';
                            } elseif ($course['status'] === 'Pending') {
                                $badge = '<span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3">Under Review</span>';
                            } else {
                                $badge = '<span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3">Rejected</span>';
                            }
                        ?>
                            <div class="d-flex justify-content-between align-items-center p-3 mb-2 rounded-3 bg-light border-start border-orange border-4 border-opacity-10">
                                <div>
                                    <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($course['code']); ?></h6>
                                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($course['title']); ?></p>
                                </div>
                                <div><?php echo $badge; ?></div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card premium-card p-4 shadow-sm h-100">
                    <h5 class="card-title font-serif fw-bold mb-4 text-orange">Notifications</h5>
                    <div class="activity-feed">
                        <div class="text-center py-4">
                            <i class="bi bi-bell-slash text-muted fs-2 opacity-25"></i>
                            <p class="text-muted small mt-2">No new notifications</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Filter Section ─────────────────────────────────────────────── -->
        <div class="card premium-card p-4 mb-5">
            <h5 class="card-title font-serif fw-bold mb-3 text-orange">Filter Submissions</h5>
            <div class="row g-3">
                <div class="col-md-4">
                    <input type="text" id="filterSearch" class="form-control" placeholder="Search course code / title">
                </div>
                <div class="col-md-3">
                    <select id="filterStatus" class="form-select">
                        <option value="">All status</option>
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

        <!-- ── My Submissions Table ───────────────────────────────────────── -->
        <div class="card premium-card p-4 mb-5">
            <h5 class="card-title font-serif fw-bold mb-3 text-orange">My Submissions</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-premium" id="submissionsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="text-secondary small">#</th>
                            <th class="text-secondary small">COURSE</th>
                            <th class="text-secondary small d-none d-lg-table-cell">TYPE</th>
                            <th class="text-secondary small d-none d-xl-table-cell">SEM / YEAR</th>
                            <th class="text-secondary small">STATUS</th>
                            <th class="text-secondary small">COMMENT</th>
                            <th class="text-secondary small text-center">FILE</th>
                            <th class="text-secondary small">SUBMITTED</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($my_submissions)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">No submissions yet. <a href="upload_syllabus.php" class="text-orange">Upload one now →</a></td></tr>
                        <?php else: foreach ($my_submissions as $i => $sub):
                            if ($sub['status'] === 'Pending') {
                                $sc = 'bg-warning text-dark bg-opacity-25 border border-warning';
                            } elseif ($sub['status'] === 'Approved') {
                                $sc = 'bg-success text-success bg-opacity-25 border border-success';
                            } else {
                                $sc = 'bg-danger text-danger bg-opacity-25 border border-danger';
                            }
                        ?>
                            <tr class="submission-row"
                                data-code="<?php echo strtolower($sub['course_code']); ?>"
                                data-title="<?php echo strtolower($sub['course_title']); ?>"
                                data-status="<?php echo $sub['status']; ?>"
                                data-date="<?php echo $sub['submitted_on']; ?>">
                                <td><?php echo $i + 1; ?></td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold small"><?php echo htmlspecialchars($sub['course_code']); ?></span>
                                        <span class="text-muted text-truncate" style="font-size:0.7rem;max-width:150px;">
                                            <?php echo htmlspecialchars($sub['course_title']); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="d-none d-lg-table-cell small"><?php echo htmlspecialchars($sub['subject_type']); ?></td>
                                <td class="d-none d-xl-table-cell small">
                                    <?php echo htmlspecialchars($sub['semester']); ?><br>
                                    <?php echo htmlspecialchars($sub['year']); ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $sc; ?> rounded-pill px-3" style="font-size:0.75rem;">
                                        <?php echo htmlspecialchars($sub['status']); ?>
                                    </span>
                                </td>
                                <td class="small"><?php echo htmlspecialchars($sub['comment'] ?? '—'); ?></td>
                                <td class="text-center">
                                    <a href="view_syllabus.php?file=<?php echo urlencode(basename($sub['file_path'])); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-link text-orange p-0">
                                        <i class="bi bi-file-earmark-pdf fs-5"></i>
                                    </a>
                                </td>
                                <td class="small"><?php echo htmlspecialchars($sub['submitted_on']); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── Shared Syllabus Table ──────────────────────────────────────── -->
        <div class="card premium-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="card-title font-serif fw-bold mb-0 text-orange">Recent Shared Syllabus</h5>
                <a href="shared_syllabus.php" class="btn btn-sm btn-outline-orange rounded-pill px-3">View All Repository</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-premium">
                    <thead class="table-light">
                        <tr>
                            <th class="text-secondary small">#</th>
                            <th class="text-secondary small">COURSE</th>
                            <th class="text-secondary small d-none d-lg-table-cell">TYPE</th>
                            <th class="text-secondary small">STATUS</th>
                            <th class="text-secondary small text-center">FILE</th>
                            <th class="text-secondary small">SOURCE</th>
                            <th class="text-secondary small">DELIVERED</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="7" class="text-center text-muted py-4">No recent shared files found</td></tr>
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
    const date   = document.getElementById('filterDate').value;

    document.querySelectorAll('.submission-row').forEach(row => {
        const matchSearch = !search ||
            row.dataset.code.includes(search) ||
            row.dataset.title.includes(search);
        const matchStatus = !status || row.dataset.status === status;
        const matchDate   = !date   || row.dataset.date === date;

        row.style.display = (matchSearch && matchStatus && matchDate) ? '' : 'none';
    });
}

// Live search on keyup
document.getElementById('filterSearch').addEventListener('keyup', applyFilter);
document.getElementById('filterStatus').addEventListener('change', applyFilter);
document.getElementById('filterDate').addEventListener('change', applyFilter);
</script>
</body>
</html>