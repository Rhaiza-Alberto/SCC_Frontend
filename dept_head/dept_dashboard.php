<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Get user information from session
$username = $_SESSION['username'] ?? 'Department Head';
$email = $_SESSION['email'] ?? '';
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
        <div class="sidebar bg-black text-white p-3 min-vh-100 d-flex flex-column"
            style="width: 280px; position: fixed;">
            <div class="text-center mb-4 mt-3">
                <img src="../css/logo.png" alt="SCC Logo" class="rounded-circle mb-2"
                    style="width: 80px; height: 80px;">
                <h4 class="font-serif fw-bold">Head Panel</h4>
            </div>

            <nav class="nav flex-column gap-2 mb-auto">
                <a href="dept_dashboard.php" class="nav-link text-white active-nav-link p-3 rounded">
                    Dashboard
                </a>
                <a href="upload_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">
                    Upload Syllabus
                </a>
                <a href="my_submissions.php" class="nav-link text-white p-3 rounded hover-effect">
                    My Submissions
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
                </div>
            </div>

            <!-- Stats/Summary Cards -->
            <div class="row g-4 mb-5">
                <div class="col-md-3">
                    <div class="card h-100 border-0 shadow-sm p-3 text-center">
                        <h6 class="text-orange fw-bold mb-3">Total</h6>
                        <h1 class="text-orange fw-bold display-4">0</h1>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 border-0 shadow-sm p-3 text-center">
                        <h6 class="text-success fw-bold mb-3">Approved</h6>
                        <h1 class="text-success fw-bold display-4">0</h1>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 border-0 shadow-sm p-3 text-center">
                        <h6 class="text-warning fw-bold mb-3">Pending</h6>
                        <h1 class="text-warning fw-bold display-4">0</h1>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 border-0 shadow-sm p-3 text-center">
                        <h6 class="text-danger fw-bold mb-3">Rejected</h6>
                        <h1 class="text-danger fw-bold display-4">0</h1>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="card border-0 shadow-sm p-4 mb-5">
                <h5 class="card-title font-serif fw-bold mb-3">Filter Submissions</h5>
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

            <!-- Faculty Submissions for Approval -->
            <div class="card border-0 shadow-sm p-4 mb-5">
                <h5 class="card-title font-serif fw-bold mb-3">Faculty Submissions Pending Approval</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="text-secondary small">#</th>
                                <th scope="col" class="text-secondary small">INSTRUCTOR</th>
                                <th scope="col" class="text-secondary small">COURSE CODE</th>
                                <th scope="col" class="text-secondary small">TITLE</th>
                                <th scope="col" class="text-secondary small">SUBJECT TYPE</th>
                                <th scope="col" class="text-secondary small">SEMESTER</th>
                                <th scope="col" class="text-secondary small">YEAR</th>
                                <th scope="col" class="text-secondary small">STATUS</th>
                                <th scope="col" class="text-secondary small">FILE</th>
                                <th scope="col" class="text-secondary small">SUBMITTED</th>
                                <th scope="col" class="text-secondary small">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // TODO: Replace with database query for faculty submissions in department
                            $faculty_submissions = []; // This should come from database
                            
                            if (empty($faculty_submissions)) {
                                echo '<tr>';
                                echo '<td colspan="11" class="text-center text-muted py-4">No submissions awaiting approval</td>';
                                echo '</tr>';
                            } else {
                                $counter = 1;
                                foreach ($faculty_submissions as $submission) {
                                    echo '<tr class="bg-light-gray">';
                                    echo '<td>' . $counter . '</td>';
                                    echo '<td>';
                                    echo '<div class="d-flex flex-column">';
                                    echo '<span class="fw-bold">' . htmlspecialchars($submission['instructor_name']) . '</span>';
                                    echo '<span class="text-muted small">' . htmlspecialchars($submission['instructor_email']) . '</span>';
                                    echo '</div>';
                                    echo '</td>';
                                    echo '<td>' . htmlspecialchars($submission['course_code']) . '</td>';
                                    echo '<td>' . htmlspecialchars($submission['course_title']) . '</td>';
                                    echo '<td>' . htmlspecialchars($submission['subject_type']) . '</td>';
                                    echo '<td>' . htmlspecialchars($submission['semester']) . '</td>';
                                    echo '<td>' . htmlspecialchars($submission['year']) . '</td>';

                                    $statusClass = $submission['status'] == 'Pending' ? 'bg-warning text-dark bg-opacity-25 border border-warning' :
                                        ($submission['status'] == 'Approved' ? 'bg-success text-success bg-opacity-25 border border-success' :
                                            'bg-danger text-danger bg-opacity-25 border border-danger');
                                    echo '<td><span class="badge ' . $statusClass . ' rounded-pill px-3">' . htmlspecialchars($submission['status']) . '</span></td>';
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

            <!-- My Submissions Table -->
            <div class="card border-0 shadow-sm p-4 mb-5">
                <h5 class="card-title font-serif fw-bold mb-3">My submissions</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="text-secondary small">#</th>
                                <th scope="col" class="text-secondary small">COURSE CODE</th>
                                <th scope="col" class="text-secondary small">TITLE</th>
                                <th scope="col" class="text-secondary small">SUBJECT TYPE</th>
                                <th scope="col" class="text-secondary small">SEMESTER</th>
                                <th scope="col" class="text-secondary small">YEAR</th>
                                <th scope="col" class="text-secondary small">STATUS</th>
                                <th scope="col" class="text-secondary small">COMMENT</th>
                                <th scope="col" class="text-secondary small">FILE</th>
                                <th scope="col" class="text-secondary small">SUBMITTED</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // TODO: Replace with database query for department head's own submissions
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
                                    echo '<td>' . htmlspecialchars($submission['course_code']) . '</td>';
                                    echo '<td>' . htmlspecialchars($submission['title']) . '</td>';
                                    echo '<td>' . htmlspecialchars($submission['subject_type']) . '</td>';
                                    echo '<td>' . htmlspecialchars($submission['semester']) . '</td>';
                                    echo '<td>' . htmlspecialchars($submission['year']) . '</td>';

                                    $statusClass = $submission['status'] == 'Pending' ? 'bg-warning text-dark bg-opacity-25 border border-warning' :
                                        ($submission['status'] == 'Approved' ? 'bg-success text-success bg-opacity-25 border border-success' :
                                            'bg-danger text-danger bg-opacity-25 border border-danger');
                                    echo '<td><span class="badge ' . $statusClass . ' rounded-pill px-3">' . htmlspecialchars($submission['status']) . '</span></td>';
                                    echo '<td>' . ($submission['comment'] ?? '—') . '</td>';
                                    echo '<td><a href="' . htmlspecialchars($submission['file_path']) . '" class="text-orange text-decoration-underline">Preview</a></td>';
                                    echo '<td>' . htmlspecialchars($submission['submitted_on']) . '</td>';
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
            <div class="card border-0 shadow-sm p-4">
                <h5 class="card-title font-serif fw-bold mb-3">Shared Syllabus</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="text-secondary small">#</th>
                                <th scope="col" class="text-secondary small">COURSE CODE</th>
                                <th scope="col" class="text-secondary small">TITLE</th>
                                <th scope="col" class="text-secondary small">SUBJECT TYPE</th>
                                <th scope="col" class="text-secondary small">SEMESTER</th>
                                <th scope="col" class="text-secondary small">YEAR</th>
                                <th scope="col" class="text-secondary small">STATUS</th>
                                <th scope="col" class="text-secondary small">FILE</th>
                                <th scope="col" class="text-secondary small">SOURCE</th>
                                <th scope="col" class="text-secondary small">DELIVERED</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // TODO: Replace with database query for shared syllabus
                            $shared_syllabus = []; // This should come from database
                            
                            if (empty($shared_syllabus)) {
                                echo '<tr>';
                                echo '<td colspan="10" class="text-center text-muted py-4">No files found</td>';
                                echo '</tr>';
                            } else {
                                $counter = 1;
                                foreach ($shared_syllabus as $syllabus) {
                                    echo '<tr class="bg-light-gray">';
                                    echo '<td>' . $counter . '</td>';
                                    echo '<td>' . htmlspecialchars($syllabus['course_code']) . '</td>';
                                    echo '<td>' . htmlspecialchars($syllabus['title']) . '</td>';
                                    echo '<td>' . htmlspecialchars($syllabus['subject_type']) . '</td>';
                                    echo '<td>' . htmlspecialchars($syllabus['semester']) . '</td>';
                                    echo '<td>' . htmlspecialchars($syllabus['year']) . '</td>';

                                    $statusClass = $syllabus['status'] == 'Approved' ? 'bg-success text-success bg-opacity-25 border border-success' :
                                        ($syllabus['status'] == 'Pending' ? 'bg-warning text-dark bg-opacity-25 border border-warning' :
                                            'bg-danger text-danger bg-opacity-25 border border-danger');
                                    echo '<td><span class="badge ' . $statusClass . ' rounded-pill px-3">' . htmlspecialchars($syllabus['status']) . '</span></td>';
                                    echo '<td><a href="' . htmlspecialchars($syllabus['file_path']) . '" class="text-orange text-decoration-underline">Preview</a></td>';
                                    echo '<td>';
                                    echo '<div class="d-flex flex-column">';
                                    echo '<span class="fw-bold small">' . htmlspecialchars($syllabus['source_name']) . '</span>';
                                    echo '<span class="text-muted small" style="font-size: 0.75rem;">' . htmlspecialchars($syllabus['source_email']) . '</span>';
                                    echo '</div>';
                                    echo '</td>';
                                    echo '<td>' . htmlspecialchars($syllabus['delivered_on']) . '</td>';
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