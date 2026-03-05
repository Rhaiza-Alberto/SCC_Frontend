<?php
session_start();
require_once 'database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Initialize session-based "database" for prototype
if (!isset($_SESSION['submissions']) || (isset($_SESSION['submissions'][0]['uploader_name']) && $_SESSION['submissions'][0]['uploader_name'] === 'Alice Johnson')) {
    $_SESSION['submissions'] = [];
}

// Initialize session-based "users" if empty
if (!isset($_SESSION['users'])) {
    $_SESSION['users'] = [
        ['id' => 1, 'username' => 'Achy', 'email' => 'faculty@gmail.com', 'role' => 'faculty', 'dept' => 'CS'],
        ['id' => 2, 'username' => 'Dr. Jane Smith', 'email' => 'dept@gmail.com', 'role' => 'dept_head', 'dept' => 'CS'],
        ['id' => 3, 'username' => 'VPAA', 'email' => 'vpaa@gmail.com', 'role' => 'vpaa', 'dept' => 'Institutional'],
        ['id' => 4, 'username' => 'Admin User', 'email' => 'admin@gmail.com', 'role' => 'admin', 'dept' => 'CCS'],
    ];
}

// Get user information from session
$username = $_SESSION['username'] ?? 'Dean / Admin';
$email = $_SESSION['email'] ?? 'admin@gmail.com';
$role = $_SESSION['role'] ?? 'admin';
$role_display = "Dean's Panel";

// Handle Approval/Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['submission_id'])) {
    $id = (int)$_POST['submission_id'];
    $action = $_POST['action'];
    
    foreach ($_SESSION['submissions'] as &$sub) {
        if ($sub['id'] == $id) {
            $sub['status'] = ($action === 'approve') ? 'Approved' : 'Rejected';
            break;
        }
    }
    header('Location: admin_dashboard.php');
    exit();
}

$submissions = $_SESSION['submissions'];
$users = $_SESSION['users'];

// Syllabus Stats
$total_count = count($submissions);
$approved_count = count(array_filter($submissions, fn($s) => $s['status'] == 'Approved'));
$pending_count = count(array_filter($submissions, fn($s) => $s['status'] == 'Pending'));
$rejected_count = count(array_filter($submissions, fn($s) => $s['status'] == 'Rejected'));

// User Stats
$total_users = count($users);
$instructor_count = count(array_filter($users, fn($u) => $u['role'] == 'faculty'));
$dept_head_count = count(array_filter($users, fn($u) => $u['role'] == 'dept_head'));
$dean_count = count(array_filter($users, fn($u) => $u['role'] == 'admin'));

// Table Data
$pending_syllabi = array_filter($submissions, fn($s) => $s['status'] == 'Pending');
$my_submissions = array_filter($submissions, fn($s) => $s['uploader_email'] == $email);
$all_submissions = $submissions;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dean's Panel - SCC-CCS Syllabus Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap"
        rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .text-orange { color: #ff8800 !important; }
        .border-orange { border-color: #ff8800 !important; }
        .btn-orange { background-color: #ff8800 !important; color: white !important; border: none; }
        .btn-orange:hover { background-color: #e67a00 !important; color: white !important; }
        .stat-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
    </style>
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

            <nav class="nav flex-column gap-2 mb-auto">
                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
                <a href="admin_dashboard.php" class="nav-link text-white active-nav-link p-3 rounded">
                    Dashboard
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
                <a href="upload_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">
                    Upload Syllabus
                </a>
                <a href="my_submissions.php" class="nav-link text-white p-3 rounded hover-effect">
                    My Submissions
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">USER MANAGEMENT</div>
                <a href="manage_user.php" class="nav-link text-white p-3 rounded hover-effect">
                    Manage User
                </a>
                <a href="add_user.php" class="nav-link text-white p-3 rounded hover-effect">
                    Add User
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
                <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect text-decoration-none">
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
                <div class="d-flex align-items-center">
                    <h1 class="text-orange font-serif fw-bold mb-0 h2">Welcome!</h1>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="notification-icon">
                        <i class="bi bi-bell fs-4 text-dark"></i>
                        <span class="notification-badge-dot"></span>
                    </div>
                    <button class="btn btn-sm btn-white border shadow-sm rounded-1 px-3 py-1 fw-bold text-dark d-flex align-items-center" onclick="window.print()">
                        <i class="bi bi-printer me-2"></i> PRINT
                    </button>
                </div>
            </div>

            <!-- Syllabus Status Cards (Refined White Minimalist) -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm border-0 bg-white p-4 text-center border-start border-orange border-4">
                        <h6 class="text-uppercase fw-bold text-muted small mb-3">Total Submissions</h6>
                        <h1 class="display-4 fw-bold text-dark mb-0"><?php echo $total_count; ?></h1>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm border-0 bg-white p-4 text-center border-start border-success border-4">
                        <h6 class="text-uppercase fw-bold text-success small mb-3">Approved</h6>
                        <h1 class="display-4 fw-bold text-success mb-0"><?php echo $approved_count; ?></h1>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm border-0 bg-white p-4 text-center border-start border-warning border-4">
                        <h6 class="text-uppercase fw-bold text-warning small mb-3">Pending</h6>
                        <h1 class="display-4 fw-bold text-warning mb-0"><?php echo $pending_count; ?></h1>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm border-0 bg-white p-4 text-center border-start border-danger border-4">
                        <h6 class="text-uppercase fw-bold text-danger small mb-3">Rejected</h6>
                        <h1 class="display-4 fw-bold text-danger mb-0"><?php echo $rejected_count; ?></h1>
                    </div>
                </div>
            </div>

            <!-- User Statistics Cards (Clean horizontal layout) -->
            <div class="row g-4 mb-5">
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm border-0 bg-white p-4 text-center">
                        <h6 class="text-uppercase fw-bold text-muted small mb-2">Total Users</h6>
                        <h1 class="display-6 fw-bold text-dark mb-0"><?php echo $total_users; ?></h1>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm border-0 bg-white p-4 text-center">
                        <h6 class="text-uppercase fw-bold text-muted small mb-2">Instructors</h6>
                        <h1 class="display-6 fw-bold text-dark mb-0"><?php echo $instructor_count; ?></h1>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm border-0 bg-white p-4 text-center">
                        <h6 class="text-uppercase fw-bold text-muted small mb-2">Dept Heads</h6>
                        <h1 class="display-6 fw-bold text-dark mb-0"><?php echo $dept_head_count; ?></h1>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm border-0 bg-white p-4 text-center">
                        <h6 class="text-uppercase fw-bold text-muted small mb-2">Deans</h6>
                        <h1 class="display-6 fw-bold text-dark mb-0"><?php echo $dean_count; ?></h1>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="card premium-card p-4 mb-5 shadow-sm border-0">
                <h5 class="card-title font-serif fw-bold mb-3">Filter Submissions</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Search course code / title">
                        </div>
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
                        <div class="input-group">
                            <input type="date" class="form-control">
                            <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-dark w-100 fw-bold shadow-sm">Filter</button>
                    </div>
                </div>
            </div>

            <!-- Pending Syllabi for Review Table -->
            <div class="card premium-card p-4 mb-5 shadow-sm border-0">
                <h5 class="card-title font-serif fw-bold mb-4">Pending Syllabi for Review</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr class="small text-muted text-uppercase border-bottom">
                                <th class="py-3">#</th>
                                <th class="py-3">Uploader</th>
                                <th class="py-3">Course</th>
                                <th class="py-3">Subject Type</th>
                                <th class="py-3">Semester</th>
                                <th class="py-3">Year</th>
                                <th class="py-3">Status</th>
                                <th class="py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pending_syllabi)): ?>
                                <tr><td colspan="8" class="text-center py-5 text-muted">No pending syllabi found for review</td></tr>
                            <?php else: $c=1; foreach($pending_syllabi as $s): ?>
                                <tr class="border-bottom">
                                    <td class="small"><?php echo $c++; ?></td>
                                    <td>
                                        <div class="fw-bold small"><?php echo htmlspecialchars($s['uploader_name']); ?></div>
                                    </td>
                                    <td>
                                        <div class="small fw-bold"><?php echo htmlspecialchars($s['course_code']); ?> - <?php echo htmlspecialchars($s['course_title']); ?></div>
                                    </td>
                                    <td class="small">Core Subjects</td>
                                    <td class="small"><?php echo htmlspecialchars($s['semester']); ?></td>
                                    <td class="small"><?php echo htmlspecialchars($s['year']); ?></td>
                                    <td><span class="text-warning fw-bold small">Pending</span></td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button class="btn btn-sm btn-outline-primary px-2 py-0 small rounded-1">View</button>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="submission_id" value="<?php echo $s['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-sm btn-outline-success px-2 py-0 small rounded-1">Approve</button>
                                            </form>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="submission_id" value="<?php echo $s['id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-0 small rounded-1">Reject</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- My Submissions Table -->
            <div class="card premium-card p-4 mb-5 shadow-sm border-0">
                <h5 class="card-title font-serif fw-bold mb-4">My submissions</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr class="small text-muted text-uppercase border-bottom">
                                <th class="py-3">#</th>
                                <th class="py-3">Course Code</th>
                                <th class="py-3">Title</th>
                                <th class="py-3">Subject Type</th>
                                <th class="py-3">Semester</th>
                                <th class="py-3">Year</th>
                                <th class="py-3">Status</th>
                                <th class="py-3">Comment</th>
                                <th class="py-3">File</th>
                                <th class="py-3">Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($my_submissions)): ?>
                                <tr><td colspan="10" class="text-center py-5 text-muted">No submissions found</td></tr>
                            <?php else: $c=1; foreach($my_submissions as $s): ?>
                                <tr class="border-bottom">
                                    <td class="small"><?php echo $c++; ?></td>
                                    <td class="fw-bold small"><?php echo htmlspecialchars($s['course_code']); ?></td>
                                    <td class="small fw-bold"><?php echo htmlspecialchars($s['course_title']); ?></td>
                                    <td class="small"><?php echo htmlspecialchars($s['subject_type']); ?></td>
                                    <td class="small"><?php echo htmlspecialchars($s['semester']); ?></td>
                                    <td class="small"><?php echo htmlspecialchars($s['year']); ?></td>
                                    <td><span class="badge bg-warning bg-opacity-25 text-warning px-3 py-1 rounded-pill small border border-warning border-opacity-50"><?php echo $s['status']; ?></span></td>
                                    <td class="text-muted small">—</td>
                                    <td class=""><a href="#" class="text-orange text-decoration-none small fw-bold">Preview</a></td>
                                    <td class="small text-muted"><?php echo date('M d, Y', strtotime($s['submitted_on'])); ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

             <!-- All Submissions Table -->
             <div class="card premium-card p-4 shadow-sm border-0">
                <h5 class="card-title font-serif fw-bold mb-4">All submissions</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr class="small text-muted text-uppercase border-bottom">
                                <th class="py-3">#</th>
                                <th class="py-3">Uploader</th>
                                <th class="py-3">Department</th>
                                <th class="py-3">Course</th>
                                <th class="py-3">Subject Type</th>
                                <th class="py-3">Semester</th>
                                <th class="py-3">Year</th>
                                <th class="py-3">Status</th>
                                <th class="py-3">Action</th>
                                <th class="py-3">File</th>
                                <th class="py-3">Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($all_submissions)): ?>
                                <tr><td colspan="11" class="text-center py-5 text-muted">No submissions available in system</td></tr>
                            <?php else: $c=1; foreach($all_submissions as $s): ?>
                                <tr class="border-bottom bg-light bg-opacity-50">
                                    <td class="small"><?php echo $c++; ?></td>
                                    <td>
                                        <div class="fw-bold small"><?php echo htmlspecialchars($s['uploader_name']); ?></div>
                                        <div class="text-muted" style="font-size: 0.7rem;"><?php echo htmlspecialchars($s['uploader_email']); ?></div>
                                        <span class="badge bg-dark px-2 py-0 rounded-1 small mt-1" style="font-size: 0.6rem;">Instructor</span>
                                    </td>
                                    <td class="small">Department of <br><?php echo htmlspecialchars($s['department']); ?></td>
                                    <td>
                                        <div class="fw-bold small"><?php echo htmlspecialchars($s['course_code']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($s['course_title']); ?></div>
                                    </td>
                                    <td class="small">Core Subjects</td>
                                    <td class="small"><?php echo htmlspecialchars($s['semester']); ?></td>
                                    <td class="small"><?php echo htmlspecialchars($s['year']); ?></td>
                                    <td><span class="text-warning fw-bold small">Pending</span></td>
                                    <td class="">
                                        <button class="btn btn-sm btn-outline-primary px-3 py-0 small rounded-1">Share</button>
                                    </td>
                                    <td class=""><a href="#" class="text-orange text-decoration-none small fw-bold">Preview</a></td>
                                    <td class="small text-muted"><?php echo date('M d, Y', strtotime($s['submitted_on'])); ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
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
