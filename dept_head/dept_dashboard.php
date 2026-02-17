<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Get user information from session
$username = $_SESSION['username'] ?? 'Dr. Jane Smith';
$email = $_SESSION['email'] ?? '';
$role = $_SESSION['role'] ?? 'dept_head';
$role_display = 'Dept Head Panel';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Head Dashboard - SCC-CCS Syllabus Portal</title>
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

            <nav class="nav flex-column gap-2 mb-auto">
                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
                <a href="dept_dashboard.php" class="nav-link text-white active-nav-link p-3 rounded">
                    Dashboard
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
                <a href="syllabus_review.php"
                    class="nav-link text-white p-3 rounded hover-effect d-flex justify-content-between align-items-center">
                    Syllabus Review
                    <span class="badge bg-danger rounded-circle p-1"
                        style="width: 8px; height: 8px; border: 2px solid white;"></span>
                </a>
                <a href="upload_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">
                    Upload Syllabus
                </a>
                <a href="my_submissions.php" class="nav-link text-white p-3 rounded hover-effect">
                    My Submissions
                </a>
                <a href="shared_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">
                    Shared Syllabus
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">ACCOUNT MANAGEMENT</div>
                <a href="registration_requests.php" class="nav-link text-white p-3 rounded hover-effect">
                    Registration Requests
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
                </div>
            </div>

            <!-- Stats/Summary Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card stat-card stat-card-total shadow-sm border-0 bg-white"
                        style="border-left: 5px solid #ff8800 !important;">
                        <div class="stat-card-content p-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="text-uppercase fw-bold text-muted small mb-0">Total Submissions</h6>
                                <i class="bi bi-files text-orange opacity-50 fs-4"></i>
                            </div>
                            <h1 class="display-5 fw-bold text-dark mb-0">0</h1>
                            <p class="text-muted small mb-0 mt-1">No submissions yet</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card stat-card-approved shadow-sm border-0 bg-white"
                        style="border-left: 5px solid #28a745 !important;">
                        <div class="stat-card-content p-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="text-uppercase fw-bold text-muted small mb-0">Approved</h6>
                                <i class="bi bi-check-circle text-success opacity-50 fs-4"></i>
                            </div>
                            <h1 class="display-5 fw-bold text-dark mb-0">0</h1>
                            <p class="text-muted small mb-0 mt-1">Files approved</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card stat-card-pending shadow-sm border-0 bg-white"
                        style="border-left: 5px solid #ffc107 !important;">
                        <div class="stat-card-content p-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="text-uppercase fw-bold text-muted small mb-0">Pending</h6>
                                <i class="bi bi-clock-history text-warning opacity-50 fs-4"></i>
                            </div>
                            <h1 class="display-5 fw-bold text-dark mb-0">0</h1>
                            <p class="text-muted small mb-0 mt-1">All caught up</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card stat-card-rejected shadow-sm border-0 bg-white"
                        style="border-left: 5px solid #dc3545 !important;">
                        <div class="stat-card-content p-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="text-uppercase fw-bold text-muted small mb-0">Rejected</h6>
                                <i class="bi bi-x-circle text-danger opacity-50 fs-4"></i>
                            </div>
                            <h1 class="display-5 fw-bold text-dark mb-0">0</h1>
                            <p class="text-muted small mb-0 mt-1">No revisions</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <!-- Syllabus Review Pipeline -->
                <div class="col-md-8">
                    <div class="card premium-card p-4 shadow-sm h-100">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title font-serif fw-bold mb-0 text-orange">Syllabus Review Pipeline</h5>
                            <span class="badge bg-orange bg-opacity-10 text-orange rounded-pill px-3 py-1 small">Active
                                Review Cycle</span>
                        </div>
                        <div class="row g-4 mb-4">
                            <div class="col-4">
                                <div class="p-3 rounded-3 bg-light text-center border-bottom border-warning border-4">
                                    <h3 class="mb-1 fw-bold text-dark">0</h3>
                                    <span class="text-muted small fw-bold text-uppercase">Submitted</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-3 rounded-3 bg-light text-center border-bottom border-primary border-4">
                                    <h3 class="mb-1 fw-bold text-dark">0</h3>
                                    <span class="text-muted small fw-bold text-uppercase">Under Review</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-3 rounded-3 bg-light text-center border-bottom border-success border-4">
                                    <h3 class="mb-1 fw-bold text-dark">0</h3>
                                    <span class="text-muted small fw-bold text-uppercase">Approved</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-auto text-center py-3">
                            <a href="syllabus_review.php" class="btn btn-orange rounded-pill px-5 shadow-sm">Go to Review Queue</a>
                        </div>

                    </div>
                </div>

                <!-- Recent Activity Feed -->
                <div class="col-md-4">
                    <div class="card premium-card p-4 shadow-sm h-100">
                        <h5 class="card-title font-serif fw-bold mb-4 text-orange">Recent Activity</h5>
                        <div class="activity-feed">
                            <div class="text-center py-4">
                                <i class="bi bi-inbox text-muted fs-2 opacity-25"></i>
                                <p class="text-muted small mt-2">No recent activity to show</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Management Hubs -->
            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <div class="card premium-card p-4 h-100 shadow-sm border-warning border-opacity-50">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <i class="bi bi-file-earmark-check text-orange fs-2"></i>
                            <a href="syllabus_review.php" class="btn btn-sm btn-orange rounded-pill px-3">View
                                All</a>
                        </div>
                        <h5 class="card-title font-serif fw-bold mb-1 text-orange">Syllabus Review</h5>
                        <p class="text-muted small">Manage faculty syllabus submissions awaiting your approval.</p>
                        <div class="d-flex align-items-center mt-3">
                            <span class="badge bg-secondary opacity-50 shadow-sm px-2 py-1 rounded-pill me-2"
                                style="font-size: 0.75rem;">0 New</span>
                            <span class="text-muted small">no pending review</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card premium-card p-4 h-100 shadow-sm border-orange border-opacity-25">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <i class="bi bi-person-check text-orange fs-2"></i>
                            <a href="registration_requests.php"
                                class="btn btn-sm btn-orange rounded-pill px-3">Manage</a>
                        </div>
                        <h5 class="card-title font-serif fw-bold mb-1 text-orange">Registration Requests</h5>
                        <p class="text-muted small">Review and approve new faculty registration requests.</p>
                        <div class="d-flex align-items-center mt-3">
                            <span class="badge bg-secondary opacity-50 px-2 py-1 rounded-pill me-2"
                                style="font-size: 0.75rem;">0
                                New</span>
                            <span class="text-muted small">no pending approval</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shared Repository Overview -->
            <div class="row g-4 mb-5">
                <div class="col-md-12">
                    <div class="card premium-card p-4 shadow-sm bg-gradient-light border-0">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title font-serif fw-bold mb-0 text-orange">Department Repository Overview
                            </h5>
                            <a href="shared_syllabus.php"
                                class="btn btn-sm btn-orange rounded-pill px-4 shadow-sm">Explore Repository</a>
                        </div>
                        <div class="row g-4 align-items-center">
                            <div class="col-md-3 text-center border-end">
                                <div class="display-6 fw-bold text-muted opacity-50 mb-0">0</div>
                                <span class="text-muted small">Approved Syllabus Files</span>
                            </div>
                            <div class="col-md-3 text-center border-end">
                                <div class="display-6 fw-bold text-muted opacity-50 mb-0">0</div>
                                <span class="text-muted small">Active Instructors</span>
                            </div>
                            <div class="col-md-6 ps-4">
                                <p class="text-muted small mb-0">Your department repository acts as the single source of
                                    truth for all validated academic content. It ensures consistency across all subject
                                    offerings and facilitates peer review and quality management.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>