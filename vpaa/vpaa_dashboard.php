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
$username = $_SESSION['username'] ?? 'VPAA';
$email = $_SESSION['email'] ?? 'vpaa@gmail.com';
$role = $_SESSION['role'] ?? 'vpaa';
$role_display = 'VPAA Institutional Hub';

// Initialize session-based "database" for prototype if empty
if (!isset($_SESSION['submissions']) || (isset($_SESSION['submissions'][0]['uploader_name']) && $_SESSION['submissions'][0]['uploader_name'] === 'Alice Johnson')) {
    $_SESSION['submissions'] = [];
}

$submissions = $_SESSION['submissions'];

// Calculate Stats
$total_count = count($submissions);
$pending_count = count(array_filter($submissions, fn($s) => $s['status'] == 'Pending'));
$approved_count = count(array_filter($submissions, fn($s) => $s['status'] == 'Approved'));
$rejected_count = count(array_filter($submissions, fn($s) => $s['status'] == 'Rejected'));

// Department Specific Stats
$dept_stats = [
    'CS' => ['total' => 0, 'approved' => 0],
    'IT' => ['total' => 0, 'approved' => 0],
    'IS' => ['total' => 0, 'approved' => 0],
];

foreach ($submissions as $s) {
    if (isset($dept_stats[$s['department']])) {
        $dept_stats[$s['department']]['total']++;
        if ($s['status'] == 'Approved')
            $dept_stats[$s['department']]['approved']++;
    }
}

function get_readiness($dept)
{
    global $dept_stats;
    if ($dept_stats[$dept]['total'] == 0)
        return 0;
    return round(($dept_stats[$dept]['approved'] / $dept_stats[$dept]['total']) * 100);
}

$compliance_pct = $total_count > 0 ? round(($approved_count / $total_count) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VPAA Dashboard - SCC-CCS Syllabus Portal</title>
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
        .text-orange {
            color: #ff8800 !important;
        }

        .btn-orange {
            background-color: #ff8800 !important;
            color: white !important;
            border: none;
        }

        .btn-orange:hover {
            background-color: #e67a00 !important;
            color: white !important;
        }

        .stat-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
        }
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
                <a href="vpaa_dashboard.php"
                    class="nav-link text-white active-nav-link p-3 rounded text-decoration-none d-block">
                    Dashboard
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
                <a href="syllabus_review.php"
                    class="nav-link text-white p-3 rounded hover-effect text-decoration-none d-block">
                    Syllabus Review
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">ANALYTICS</div>
                <a href="compliance_reports.php"
                    class="nav-link text-white p-3 rounded hover-effect text-decoration-none d-block">
                    Compliance Reports
                </a>
                <a href="syllabus_vault.php"
                    class="nav-link text-white p-3 rounded hover-effect text-decoration-none d-block">
                    Syllabus Vault
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
                <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect text-decoration-none d-block">
                    Profile
                </a>
                <a href="../logout.php"
                    class="nav-link text-white p-3 rounded hover-effect mt-5 text-decoration-none d-block">
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

            <!-- Stats/Summary Cards (Refined White Minimalist) -->
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

            <!-- CCS Compliance and Analytics (Department Level) -->
            <div class="row g-4 mb-5">
                <!-- Compliance Overview -->
                <div class="col-md-4">
                    <div class="card premium-card p-4 shadow-sm h-100 border-0 bg-white">
                        <h5 class="card-title font-serif fw-bold mb-4 text-orange">CCS Compliance Overview</h5>
                        <div class="text-center py-3">
                            <div class="position-relative d-inline-block">
                                <svg width="120" height="120" viewBox="0 0 120 120">
                                    <circle cx="60" cy="60" r="54" fill="none" stroke="#f3f3f3" stroke-width="12" />
                                    <circle cx="60" cy="60" r="54" fill="none" stroke="#ff8800" stroke-width="12"
                                        stroke-dasharray="339.292"
                                        stroke-dashoffset="<?php echo 339.292 * (1 - ($compliance_pct / 100)); ?>"
                                        style="transition: stroke-dashoffset 1s ease-out;" />
                                </svg>
                                <div class="position-absolute top-50 start-50 translate-middle text-center">
                                    <h2 class="mb-0 fw-bold"><?php echo $compliance_pct; ?>%</h2>
                                    <span class="text-muted small">Validated</span>
                                </div>
                            </div>
                            <p class="mt-4 text-muted small fw-bold">Overall CCS Readiness</p>
                            <div class="d-flex justify-content-between mt-3 px-3">
                                <div class="text-start">
                                    <span class="text-secondary small d-block">Required</span>
                                    <span class="fw-bold"><?php echo $total_count; ?></span>
                                </div>
                                <div class="text-end">
                                    <span class="text-secondary small d-block">Approved</span>
                                    <span class="fw-bold text-success"><?php echo $approved_count; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Department Readiness Breakdown -->
                <div class="col-md-8">
                    <div class="card premium-card p-4 shadow-sm h-100 border-0 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title font-serif fw-bold mb-0 text-orange">Department Readiness</h5>
                            <a href="compliance_reports.php"
                                class="btn btn-sm btn-outline-warning rounded-pill px-3">Full Report</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-sm border-0">
                                <thead class="border-bottom">
                                    <tr>
                                        <th class="text-muted small py-2">DEPARTMENT</th>
                                        <th class="text-muted small py-2">READINESS</th>
                                        <th class="text-muted small py-2 text-end">STATUS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="py-3 fw-bold">CS</td>
                                        <td class="py-3">
                                            <div class="progress" style="height: 8px; width: 100px;">
                                                <div class="progress-bar bg-success"
                                                    style="width: <?php echo get_readiness('CS'); ?>%"></div>
                                            </div>
                                        </td>
                                        <td class="py-3 text-end"><span
                                                class="badge bg-success-subtle text-success border border-success border-opacity-25 rounded-pill px-3"><?php echo get_readiness('CS') > 80 ? 'High' : 'Low'; ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="py-3 fw-bold">IT</td>
                                        <td class="py-3">
                                            <div class="progress" style="height: 8px; width: 100px;">
                                                <div class="progress-bar bg-warning"
                                                    style="width: <?php echo get_readiness('IT'); ?>%"></div>
                                            </div>
                                        </td>
                                        <td class="py-3 text-end"><span
                                                class="badge bg-warning-subtle text-warning border border-warning border-opacity-25 rounded-pill px-3"><?php echo get_readiness('IT') > 50 ? 'Average' : 'Critical'; ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="py-3 fw-bold">IS</td>
                                        <td class="py-3">
                                            <div class="progress" style="height: 8px; width: 100px;">
                                                <div class="progress-bar bg-danger"
                                                    style="width: <?php echo get_readiness('IS'); ?>%"></div>
                                            </div>
                                        </td>
                                        <td class="py-3 text-end"><span
                                                class="badge bg-danger-subtle text-danger border border-danger border-opacity-25 rounded-pill px-3"><?php echo get_readiness('IS') > 30 ? 'Moderate' : 'Critical'; ?></span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Management Hubs & Vault -->
            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <div class="card premium-card p-4 shadow-sm border-0 bg-white border-start border-orange border-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-orange bg-opacity-10 p-3 rounded-circle me-3">
                                <i class="bi bi-safe2 text-orange fs-3"></i>
                            </div>
                            <div>
                                <h5 class="font-serif fw-bold mb-1">Accreditation Vault</h5>
                                <p class="text-muted small mb-0">Access approved historical syllabi for audits.</p>
                            </div>
                        </div>
                        <a href="syllabus_vault.php" class="btn btn-orange w-100 rounded-pill mt-2">Explore
                            Repository</a>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card premium-card p-4 shadow-sm border-0 bg-white border-start border-dark border-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-dark bg-opacity-10 p-3 rounded-circle me-3">
                                <i class="bi bi-calendar-event text-dark fs-3"></i>
                            </div>
                            <div>
                                <h5 class="font-serif fw-bold mb-1">Academic Countdown</h5>
                                <p class="text-muted small mb-0">Semester starts in <strong>12 days</strong>.</p>
                            </div>
                        </div>
                        <div class="alert alert-warning py-2 small mb-0 border-0">
                            <strong>Action Required:</strong> <?php echo $pending_count; ?> syllabus bottlenecks
                            detected.
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

            <!-- All Submissions Table -->
            <div class="card premium-card p-4">
                <h5 class="card-title font-serif fw-bold mb-3 text-orange">All submissions</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-premium">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="text-secondary small">#</th>
                                <th scope="col" class="text-secondary small">UPLOADER</th>
                                <th scope="col" class="text-secondary small">DEPARTMENT</th>
                                <th scope="col" class="text-secondary small">COURSE</th>
                                <th scope="col" class="text-secondary small">SUBJECT TYPE</th>
                                <th scope="col" class="text-secondary small">SEMESTER</th>
                                <th scope="col" class="text-secondary small">YEAR</th>
                                <th scope="col" class="text-secondary small">FILE</th>
                                <th scope="col" class="text-secondary small">SUBMITTED</th>
                                <th scope="col" class="text-secondary small">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $all_submissions = $submissions;

                            if (empty($all_submissions)) {
                                echo '<tr>';
                                echo '<td colspan="10" class="text-center text-muted py-4">No submissions found</td>';
                                echo '</tr>';
                            } else {
                                $counter = 1;
                                foreach ($all_submissions as $submission) {
                                    echo '<tr class="bg-light-gray">';
                                    echo '<td>' . $counter . '</td>';
                                    echo '<td>';
                                    echo '<div class="d-flex flex-column">';
                                    echo '<span class="fw-bold">' . htmlspecialchars($submission['uploader_name']) . '</span>';
                                    echo '<span class="text-muted small">' . htmlspecialchars($submission['uploader_email']) . '</span>';
                                    echo '</div>';
                                    echo '</td>';
                                    echo '<td>' . htmlspecialchars($submission['department']) . '</td>';
                                    echo '<td>';
                                    echo '<div class="d-flex flex-column">';
                                    echo '<span class="fw-bold">' . htmlspecialchars($submission['course_code']) . '</span>';
                                    echo '<span class="text-muted small">' . htmlspecialchars($submission['course_title']) . '</span>';
                                    echo '</div>';
                                    echo '</td>';
                                    echo '<td>' . htmlspecialchars($submission['subject_type']) . '</td>';
                                    echo '<td>' . htmlspecialchars($submission['semester']) . '</td>';
                                    echo '<td>' . htmlspecialchars($submission['year']) . '</td>';
                                    echo '<td><a href="' . htmlspecialchars($submission['file_path']) . '" class="text-orange text-decoration-underline">Preview</a></td>';
                                    echo '<td>' . htmlspecialchars($submission['submitted_on']) . '</td>';
                                    echo '<td>';
                                    echo '<a href="#" class="btn btn-sm btn-outline-success me-2">Approve</a>';
                                    echo '<a href="#" class="btn btn-sm btn-outline-danger">Reject</a>';
                                    echo '</td>';
                                    echo '</tr>';
                                    $counter++;
                                }
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