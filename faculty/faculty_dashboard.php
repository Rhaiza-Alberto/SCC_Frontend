<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Get user information from session
$username = $_SESSION['username'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$role = $_SESSION['role'] ?? 'faculty';
$role_display = 'Faculty Panel';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - SCC-CCS Syllabus Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap"
        rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
</head>

<body class="bg-light">

    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar sidebar-premium text-white p-2 min-vh-100 d-flex flex-column"
            style="width: 260px; position: fixed; z-index: 1100;">
            <div class="text-center mb-3 mt-2">
                <img src="../css/logo.png" alt="CCS Logo" class="rounded-circle mb-2"
                    style="width: 80px; height: 80px; border: 2px solid rgba(255, 136, 0, 0.5); padding: 3px;">
                <h5 class="font-serif fw-bold text-orange mb-0"><?php echo $role_display; ?></h5>
                <p class="text-white-50 small fw-bold mb-0" style="font-size: 0.75rem;">
                    <?php echo htmlspecialchars($username); ?>
                </p>
            </div>

            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
            <a href="faculty_dashboard.php" class="nav-link text-white active-nav-link p-3 rounded">
                Dashboard
            </a>

            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
            <a href="upload_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">
                Upload Syllabus
            </a>
            <a href="my_submissions.php" class="nav-link text-white p-3 rounded hover-effect">
                My Submissions
            </a>
            <a href="shared_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">
                Shared Syllabus
            </a>

            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
            <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect">
                Profile
            </a>
            <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">
                Logout
            </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-grow-1 p-5" style="margin-left: 260px;">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <h2 class="text-orange font-serif fw-bold">Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
                <div class="notification-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                        class="bi bi-bell" viewBox="0 0 16 16">
                        <path
                            d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zM8 1.918l-.797.161A4.002 4.002 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4.002 4.002 0 0 0-3.203-3.92L8 1.917zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5.002 5.002 0 0 1 13 6c0 .88.32 4.2 1.22 6z" />
                    </svg>
                    <span class="notification-badge-dot"></span>
                </div>
            </div>

            <!-- Stats/Summary Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm border-0 bg-white"
                        style="border-left: 5px solid #ff8800 !important;">
                        <div class="stat-card-content p-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="text-uppercase fw-bold text-muted small mb-0">Total Submissions</h6>
                                <i class="bi bi-files text-orange opacity-50 fs-4"></i>
                            </div>
                            <h1 class="display-5 fw-bold text-dark mb-0">0</h1>
                            <p class="text-muted small mb-0 mt-1">Ready for upload</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm border-0 bg-white"
                        style="border-left: 5px solid #28a745 !important;">
                        <div class="stat-card-content p-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="text-uppercase fw-bold text-muted small mb-0">Approved</h6>
                                <i class="bi bi-check-circle text-success opacity-50 fs-4"></i>
                            </div>
                            <h1 class="display-5 fw-bold text-dark mb-0">0</h1>
                            <p class="text-muted small mb-0 mt-1">Validated content</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm border-0 bg-white"
                        style="border-left: 5px solid #ffc107 !important;">
                        <div class="stat-card-content p-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="text-uppercase fw-bold text-muted small mb-0">Pending</h6>
                                <i class="bi bi-clock-history text-warning opacity-50 fs-4"></i>
                            </div>
                            <h1 class="display-5 fw-bold text-dark mb-0">0</h1>
                            <p class="text-muted small mb-0 mt-1">Awaiting review</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm border-0 bg-white"
                        style="border-left: 5px solid #dc3545 !important;">
                        <div class="stat-card-content p-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="text-uppercase fw-bold text-muted small mb-0">Rejected</h6>
                                <i class="bi bi-x-circle text-danger opacity-50 fs-4"></i>
                            </div>
                            <h1 class="display-5 fw-bold text-dark mb-0">0</h1>
                            <p class="text-muted small mb-0 mt-1">Needs revision</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <!-- My Course Syllabi Status -->
                <div class="col-md-8">
                    <div class="card premium-card p-4 shadow-sm h-100">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title font-serif fw-bold mb-0 text-orange">My Course Syllabi Status</h5>
                            <span class="badge bg-orange bg-opacity-10 text-orange rounded-pill px-3 py-1 small">Current
                                Semester</span>
                        </div>
                        <div class="syllabus-status-list">
                            <?php
                            $my_courses = []; // Cleared mock data
                            
                            if (empty($my_courses)) {
                                echo '<div class="text-center py-4 text-muted small">No courses assigned yet</div>';
                            } else {
                                foreach ($my_courses as $course) {
                                    $statusBadge = '';
                                    if ($course['status'] == 'Approved') {
                                        $statusBadge = '<span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Approved</span>';
                                    } elseif ($course['status'] == 'Pending') {
                                        $statusBadge = '<span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3">Under Review</span>';
                                    } else {
                                        $statusBadge = '<span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3">Initial Stage</span>';
                                    }

                                    echo '<div class="d-flex justify-content-between align-items-center p-3 mb-2 rounded-3 bg-light border-start border-orange border-4 border-opacity-10">';
                                    echo '<div>';
                                    echo '<h6 class="mb-1 fw-bold">' . htmlspecialchars($course['code']) . '</h6>';
                                    echo '<p class="text-muted small mb-0">' . htmlspecialchars($course['title']) . '</p>';
                                    echo '</div>';
                                    echo '<div>' . $statusBadge . '</div>';
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Personal Notifications -->
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

            <!-- Filter Section -->
            <div class="card premium-card p-4 mb-5">
                <h5 class="card-title font-serif fw-bold mb-3 text-orange">Filter Submissions</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" placeholder="Search course code / title">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select">
                            <option selected>All status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control" placeholder="dd/mm/yy">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-dark w-100">Filter</button>
                    </div>
                </div>
            </div>

            <!-- My Submissions Table -->
            <div class="card premium-card p-4 mb-5">
                <h5 class="card-title font-serif fw-bold mb-3 text-orange">My submissions</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-premium">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="text-secondary small">#</th>
                                <th scope="col" class="text-secondary small">COURSE</th>
                                <th scope="col" class="text-secondary small d-none d-lg-table-cell">TYPE</th>
                                <th scope="col" class="text-secondary small d-none d-xl-table-cell">SEM/YEAR</th>
                                <th scope="col" class="text-secondary small">STATUS</th>
                                <th scope="col" class="text-secondary small">COMMENT</th>
                                <th scope="col" class="text-secondary small text-center">FILE</th>
                                <th scope="col" class="text-secondary small">SUBMITTED</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // TODO: Replace with database query for faculty's own submissions
                            $my_submissions = []; // This should come from database
                            
                            if (empty($my_submissions)) {
                                echo '<tr>';
                                echo '<td colspan="10" class="text-center text-muted py-4">No files found</td>';
                                echo '</tr>';
                            } else {
                                $counter = 1;
                                foreach ($my_submissions as $submission) {
                                    echo '<tr class="bg-light-gray">';
                                    echo '<td>' . $counter . '</td>';
                                    echo '<td>';
                                    echo '<div class="d-flex flex-column">';
                                    echo '<span class="fw-bold small">' . htmlspecialchars($submission['course_code']) . '</span>';
                                    echo '<span class="text-muted d-block text-truncate" style="font-size: 0.7rem; max-width: 150px;">' . htmlspecialchars($submission['title']) . '</span>';
                                    echo '</div>';
                                    echo '</td>';
                                    echo '<td class="d-none d-lg-table-cell small">' . htmlspecialchars($submission['subject_type']) . '</td>';
                                    echo '<td class="d-none d-xl-table-cell small">' . htmlspecialchars($submission['semester']) . '<br>' . htmlspecialchars($submission['year']) . '</td>';

                                    $statusClass = $submission['status'] == 'Pending' ? 'bg-warning text-dark bg-opacity-25 border border-warning' :
                                        ($submission['status'] == 'Approved' ? 'bg-success text-success bg-opacity-25 border border-success' :
                                            'bg-danger text-danger bg-opacity-25 border border-danger');
                                    echo '<td><span class="badge ' . $statusClass . ' rounded-pill px-3" style="font-size: 0.75rem;">' . htmlspecialchars($submission['status']) . '</span></td>';
                                    echo '<td class="small mt-1">' . ($submission['comment'] ?? '—') . '</td>';
                                    echo '<td class="text-center"><a href="' . htmlspecialchars($submission['file_path']) . '" class="btn btn-sm btn-link text-orange p-0"><i class="bi bi-file-earmark-pdf fs-5"></i></a></td>';
                                    echo '<td class="small">' . htmlspecialchars($submission['submitted_on']) . '</td>';
                                    echo '</tr>';
                                    $counter++;
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Shared Syllabus Table -->
            <div class="card premium-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="card-title font-serif fw-bold mb-0 text-orange">Recent Shared Syllabus</h5>
                    <a href="shared_syllabus.php" class="btn btn-sm btn-outline-orange rounded-pill px-3">View All
                        Repository</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-premium">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="text-secondary small">#</th>
                                <th scope="col" class="text-secondary small">COURSE</th>
                                <th scope="col" class="text-secondary small d-none d-lg-table-cell">TYPE</th>
                                <th scope="col" class="text-secondary small">STATUS</th>
                                <th scope="col" class="text-secondary small text-center">FILE</th>
                                <th scope="col" class="text-secondary small">SOURCE</th>
                                <th scope="col" class="text-secondary small">DELIVERED</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Showing only high-level recent items for dashboard
                            $recent_shared = [];

                            if (empty($recent_shared)) {
                                echo '<tr><td colspan="7" class="text-center text-muted py-4">No recent shared files found</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>