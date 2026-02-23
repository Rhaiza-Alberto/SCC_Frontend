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

// Initialize session-based "database" for prototype if empty or contains dummy data
if (!isset($_SESSION['submissions']) || (isset($_SESSION['submissions'][0]['uploader_name']) && $_SESSION['submissions'][0]['uploader_name'] === 'Alice Johnson')) {
    $_SESSION['submissions'] = [];
}

$submissions = $_SESSION['submissions'];
$total_count = count($submissions);
$approved_count = count(array_filter($submissions, fn($s) => $s['status'] == 'Approved'));
$pending_count = count(array_filter($submissions, fn($s) => $s['status'] == 'Pending'));
$rejected_count = count(array_filter($submissions, fn($s) => $s['status'] == 'Rejected'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Head Dashboard - SCC-CCS Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
    
    <style>
        /* Force Sidebar Width and Position */
        .sidebar {
            width: 260px !important;
            position: fixed !important;
            top: 0;
            left: 0;
            z-index: 1100;
            transition: transform 0.3s ease;
        }

        /* Force Main Content to offset by the sidebar's width */
        .main-content {
            margin-left: 260px !important;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        /* Mobile Overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1050;
        }

        /* Responsive rules for smaller screens */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0 !important;
            }
            .sidebar-overlay.show {
                display: block;
            }
        }
    </style>
</head>

<body class="bg-light">

    <div class="d-flex">
        
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <div class="sidebar sidebar-premium text-white p-2 min-vh-100 d-flex flex-column" id="sidebar">
            
            <button class="btn btn-sm btn-link text-white d-md-none position-absolute top-0 end-0 mt-2 me-2" id="closeSidebar">
                <i class="bi bi-x-lg"></i>
            </button>

            <div class="text-center mb-3 mt-4">
                <img src="../css/logo.png" alt="CCS Logo" class="rounded-circle mb-2"
                    style="width: 80px; height: 80px; border: 2px solid rgba(255, 136, 0, 0.5); padding: 3px; background: white;">
                <h5 class="font-serif fw-bold text-orange mb-0"><?php echo $role_display; ?></h5>
                <p class="text-white-50 small fw-bold mb-0" style="font-size: 0.75rem;">
                    <?php echo htmlspecialchars($username); ?>
                </p>
            </div>

            <nav class="nav flex-column gap-2 mb-auto pb-4">
                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
                <a href="dept_dashboard.php" class="nav-link text-white active-nav-link p-3 rounded" style="background-color: rgba(255, 136, 0, 0.2);">
                    <i class="bi bi-grid-1x2-fill me-2 text-orange"></i> Dashboard
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
                <a href="syllabus_review.php" class="nav-link text-white p-3 rounded hover-effect d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-journal-check me-2"></i> Syllabus Review</span>
                    <?php if ($pending_count > 0): ?>
                        <span class="badge bg-danger rounded-circle p-1" style="width: 10px; height: 10px;"></span>
                    <?php endif; ?>
                </a>
                <a href="upload_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">
                    <i class="bi bi-cloud-upload me-2"></i> Upload Syllabus
                </a>
                <a href="my_submissions.php" class="nav-link text-white p-3 rounded hover-effect">
                    <i class="bi bi-file-earmark-person me-2"></i> My Submissions
                </a>
                <a href="shared_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">
                    <i class="bi bi-folder2-open me-2"></i> Shared Syllabus
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">ACCOUNT MANAGEMENT</div>
                <a href="registration_requests.php" class="nav-link text-white p-3 rounded hover-effect">
                    <i class="bi bi-person-lines-fill me-2"></i> Registration Requests
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
                <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect">
                    <i class="bi bi-person-circle me-2"></i> Profile
                </a>
                <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-3 border border-secondary border-opacity-25">
                    <i class="bi bi-box-arrow-left me-2"></i> Logout
                </a>
            </nav>
        </div>

        <div class="main-content flex-grow-1 p-4 p-md-5">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-outline-dark d-md-none" id="sidebarToggle">
                        <i class="bi bi-list"></i>
                    </button>
                    <h2 class="text-orange font-serif fw-bold mb-0">Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
                </div>
                
                <div class="notification-icon position-relative">
                    <i class="bi bi-bell fs-4"></i>
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                        <span class="visually-hidden">New alerts</span>
                    </span>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-3 col-sm-6">
                    <div class="card stat-card shadow-sm border-0 bg-white p-4 text-center border-start border-orange border-4 h-100">
                        <h6 class="text-uppercase fw-bold text-muted small mb-3">Total Submissions</h6>
                        <h1 class="display-4 fw-bold text-dark mb-0"><?php echo $total_count; ?></h1>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card stat-card shadow-sm border-0 bg-white p-4 text-center border-start border-success border-4 h-100">
                        <h6 class="text-uppercase fw-bold text-success small mb-3">Approved</h6>
                        <h1 class="display-4 fw-bold text-success mb-0"><?php echo $approved_count; ?></h1>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card stat-card shadow-sm border-0 bg-white p-4 text-center border-start border-warning border-4 h-100">
                        <h6 class="text-uppercase fw-bold text-warning small mb-3">Pending</h6>
                        <h1 class="display-4 fw-bold text-warning mb-0"><?php echo $pending_count; ?></h1>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card stat-card shadow-sm border-0 bg-white p-4 text-center border-start border-danger border-4 h-100">
                        <h6 class="text-uppercase fw-bold text-danger small mb-3">Rejected</h6>
                        <h1 class="display-4 fw-bold text-danger mb-0"><?php echo $rejected_count; ?></h1>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-lg-8">
                    <div class="card premium-card p-4 shadow-sm h-100 border-0 rounded-4">
                        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                            <h5 class="card-title font-serif fw-bold mb-0 text-orange">Syllabus Review Pipeline</h5>
                            <span class="badge bg-orange bg-opacity-10 text-orange border border-warning rounded-pill px-3 py-1 small">Active Review Cycle</span>
                        </div>
                        <div class="row g-4 mb-4">
                            <div class="col-4">
                                <div class="p-3 rounded-3 bg-light text-center border-bottom border-warning border-4">
                                    <h3 class="mb-1 fw-bold text-dark"><?php echo $total_count; ?></h3>
                                    <span class="text-muted small fw-bold text-uppercase d-none d-sm-block">Submitted</span>
                                    <span class="text-muted small fw-bold text-uppercase d-block d-sm-none">All</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-3 rounded-3 bg-light text-center border-bottom border-primary border-4">
                                    <h3 class="mb-1 fw-bold text-dark"><?php echo $pending_count; ?></h3>
                                    <span class="text-muted small fw-bold text-uppercase d-none d-sm-block">Under Review</span>
                                    <span class="text-muted small fw-bold text-uppercase d-block d-sm-none">Rev</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-3 rounded-3 bg-light text-center border-bottom border-success border-4">
                                    <h3 class="mb-1 fw-bold text-dark"><?php echo $approved_count; ?></h3>
                                    <span class="text-muted small fw-bold text-uppercase">Approved</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-auto text-center py-2">
                            <a href="syllabus_review.php" class="btn btn-orange rounded-pill px-4 px-md-5 shadow-sm">Go to Review Queue</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card premium-card p-4 shadow-sm h-100 border-0 rounded-4">
                        <h5 class="card-title font-serif fw-bold mb-4 text-orange">Recent Activity</h5>
                        <div class="activity-feed h-100 d-flex flex-column justify-content-center">
                            <div class="text-center py-4">
                                <i class="bi bi-inbox text-muted fs-1 opacity-25"></i>
                                <p class="text-muted small mt-2">No recent activity to show</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <div class="card premium-card p-4 h-100 shadow-sm border-0 border-top border-warning border-4 rounded-4 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <i class="bi bi-file-earmark-check text-orange fs-2"></i>
                            <a href="syllabus_review.php" class="btn btn-sm btn-outline-warning rounded-pill px-3">View All</a>
                        </div>
                        <h5 class="card-title font-serif fw-bold mb-2 text-dark">Syllabus Review</h5>
                        <p class="text-muted small mb-4">Manage faculty syllabus submissions awaiting your approval.</p>
                        <div class="d-flex align-items-center mt-auto">
                            <span class="badge bg-secondary opacity-75 shadow-sm px-2 py-1 rounded-pill me-2" style="font-size: 0.75rem;">0 New</span>
                            <span class="text-muted small">no pending review</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card premium-card p-4 h-100 shadow-sm border-0 border-top border-orange border-4 rounded-4 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <i class="bi bi-person-check text-orange fs-2"></i>
                            <a href="registration_requests.php" class="btn btn-sm btn-outline-warning rounded-pill px-3">Manage</a>
                        </div>
                        <h5 class="card-title font-serif fw-bold mb-2 text-dark">Registration Requests</h5>
                        <p class="text-muted small mb-4">Review and approve new faculty registration requests.</p>
                        <div class="d-flex align-items-center mt-auto">
                            <span class="badge bg-secondary opacity-75 px-2 py-1 rounded-pill me-2" style="font-size: 0.75rem;">0 New</span>
                            <span class="text-muted small">no pending approval</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-12">
                    <div class="card premium-card p-4 shadow-sm bg-light border-0 rounded-4">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                            <h5 class="card-title font-serif fw-bold mb-0 text-orange">Department Repository Overview</h5>
                            <a href="shared_syllabus.php" class="btn btn-sm btn-orange rounded-pill px-4 shadow-sm w-auto">Explore Repository</a>
                        </div>
                        <div class="row g-4 align-items-center">
                            <div class="col-md-3 text-center border-md-end border-bottom border-md-bottom-0 pb-3 pb-md-0">
                                <div class="display-6 fw-bold text-muted opacity-50 mb-0">0</div>
                                <span class="text-muted small">Approved Files</span>
                            </div>
                            <div class="col-md-3 text-center border-md-end border-bottom border-md-bottom-0 pb-3 pb-md-0">
                                <div class="display-6 fw-bold text-muted opacity-50 mb-0">0</div>
                                <span class="text-muted small">Active Instructors</span>
                            </div>
                            <div class="col-md-6 ps-md-4 text-center text-md-start">
                                <p class="text-muted small mb-0">Your department repository acts as the single source of truth for all validated academic content. It ensures consistency across all subject offerings and facilitates peer review and quality management.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebarToggle');
            const closeBtn = document.getElementById('closeSidebar');
            const overlay = document.getElementById('sidebarOverlay');

            if(sidebar && toggleBtn && closeBtn && overlay) {
                function openSidebar() {
                    sidebar.classList.add('show');
                    overlay.classList.add('show');
                }

                function closeSidebarFunc() {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                }

                toggleBtn.addEventListener('click', openSidebar);
                closeBtn.addEventListener('click', closeSidebarFunc);
                overlay.addEventListener('click', closeSidebarFunc);
            }
        });
    </script>
</body>
</html>