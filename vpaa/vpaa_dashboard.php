<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Get user information from session
$username = $_SESSION['username'] ?? 'VPAA';
$email = $_SESSION['email'] ?? '';
$role = $_SESSION['role'] ?? 'vpaa';
$role_display = 'VPAA';
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
</head>

<body class="bg-light">

    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar sidebar-premium text-white p-3 min-vh-100 d-flex flex-column"
            style="width: 280px; position: fixed;">
            <div class="text-center mb-4 mt-3">
                <img src="../css/logo.png" alt="CCS Logo" class="rounded-circle mb-2"
                    style="width: 150px; height: 150px; border: 2px solid rgba(255, 136, 0, 0.5); padding: 5px;">
                <h4 class="font-serif fw-bold text-orange mb-0"><?php echo $role_display; ?></h4>
                <p class="text-white-50 small fw-bold mb-0"><?php echo htmlspecialchars($username); ?></p>
            </div>


            <nav class="nav flex-column gap-2 mb-auto">
                <a href="vpaa_dashboard.php" class="nav-link text-white active-nav-link p-3 rounded">
                    Dashboard
                </a>
                <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect">
                    Profile
                </a>
                <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">
                    Logout
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-grow-1 p-5" style="margin-left: 280px;">
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
            <div class="row g-4 mb-5">
                <div class="col-md-3">
                    <div class="card stat-card stat-card-total">
                        <div class="stat-card-content text-center">
                            <h6>Total</h6>
                            <h1 class="display-4">0</h1>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card stat-card-approved">
                        <div class="stat-card-content text-center">
                            <h6>Approved</h6>
                            <h1 class="display-4">0</h1>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card stat-card-pending">
                        <div class="stat-card-content text-center">
                            <h6>Pending</h6>
                            <h1 class="display-4">0</h1>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card stat-card-rejected">
                        <div class="stat-card-content text-center">
                            <h6>Rejected</h6>
                            <h1 class="display-4">0</h1>
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
                            // TODO: Replace with database query for all submissions
                            $all_submissions = []; // This should come from database
                            
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