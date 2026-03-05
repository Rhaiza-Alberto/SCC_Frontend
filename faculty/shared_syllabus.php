<?php
session_start();
require_once 'database.php';

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
    <title>Shared Syllabus - SCC-CCS Syllabus Portal</title>
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
            <a href="faculty_dashboard.php" class="nav-link text-white p-3 rounded hover-effect">
                Dashboard
            </a>

            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
            <a href="upload_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">
                Upload Syllabus
            </a>
            <a href="my_submissions.php" class="nav-link text-white p-3 rounded hover-effect">
                My Submissions
            </a>
            <a href="shared_syllabus.php" class="nav-link text-white active-nav-link p-3 rounded">
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
                <h2 class="text-orange font-serif fw-bold">Shared Syllabus Repository</h2>
                <div class="notification-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                        class="bi bi-bell" viewBox="0 0 16 16">
                        <path
                            d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zM8 1.918l-.797.161A4.002 4.002 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4.002 4.002 0 0 0-3.203-3.92L8 1.917zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5.002 5.002 0 0 1 13 6c0 .88.32 4.2 1.22 6z" />
                    </svg>
                    <span class="notification-badge-dot"></span>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="card premium-card p-4 mb-5">
                <h5 class="card-title font-serif fw-bold mb-3 text-orange">Search Repository</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control" placeholder="Search course code / title / instructor">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select">
                            <option selected>All Subject Types</option>
                            <option value="Institutional Subject">Institutional Subject</option>
                            <option value="General Education (GE)">General Education (GE)</option>
                            <option value="Core Subject">Core Subject</option>
                            <option value="Professional Subjects">Professional Subjects</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-dark w-100">Search</button>
                    </div>
                </div>
            </div>

            <!-- Shared Syllabus Table -->
            <div class="card premium-card p-4">
                <h5 class="card-title font-serif fw-bold mb-3 text-orange">Department Syllabus Repository</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-premium">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="text-secondary small">#</th>
                                <th scope="col" class="text-secondary small">COURSE</th>
                                <th scope="col" class="text-secondary small d-none d-lg-table-cell">TYPE</th>
                                <th scope="col" class="text-secondary small d-none d-xl-table-cell">SEM/YEAR</th>
                                <th scope="col" class="text-secondary small">STATUS</th>
                                <th scope="col" class="text-secondary small text-center">FILE</th>
                                <th scope="col" class="text-secondary small">SOURCE</th>
                                <th scope="col" class="text-secondary small">DELIVERED</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $shared_syllabus = [];

                            if (empty($shared_syllabus)) {
                                echo '<tr>';
                                echo '<td colspan="10" class="text-center text-muted py-4">No files found in repository</td>';
                                echo '</tr>';
                            } else {
                                $counter = 1;
                                foreach ($shared_syllabus as $syllabus) {
                                    echo '<tr class="bg-light-gray">';
                                    echo '<td>' . $counter . '</td>';
                                    echo '<td>';
                                    echo '<div class="d-flex flex-column">';
                                    echo '<span class="fw-bold small">' . htmlspecialchars($syllabus['course_code']) . '</span>';
                                    echo '<span class="text-muted d-block text-truncate" style="font-size: 0.7rem; max-width: 150px;">' . htmlspecialchars($syllabus['title']) . '</span>';
                                    echo '</div>';
                                    echo '</td>';
                                    echo '<td class="d-none d-lg-table-cell small">' . htmlspecialchars($syllabus['subject_type']) . '</td>';
                                    echo '<td class="d-none d-xl-table-cell small">' . htmlspecialchars($syllabus['semester']) . '<br>' . htmlspecialchars($syllabus['year']) . '</td>';

                                    $statusClass = $syllabus['status'] == 'Approved' ? 'bg-success text-success bg-opacity-25 border border-success' :
                                        ($syllabus['status'] == 'Pending' ? 'bg-warning text-dark bg-opacity-25 border border-warning' :
                                            'bg-danger text-danger bg-opacity-25 border border-danger');
                                    echo '<td><span class="badge ' . $statusClass . ' rounded-pill px-3" style="font-size: 0.75rem;">' . htmlspecialchars($syllabus['status']) . '</span></td>';
                                    echo '<td class="text-center"><a href="' . htmlspecialchars($syllabus['file_path']) . '" class="btn btn-sm btn-link text-orange p-0"><i class="bi bi-file-earmark-pdf fs-5"></i></a></td>';
                                    echo '<td>';
                                    echo '<div class="d-flex flex-column">';
                                    echo '<span class="fw-bold small">' . htmlspecialchars($syllabus['source_name']) . '</span>';
                                    echo '<span class="text-muted small" style="font-size: 0.75rem;">' . htmlspecialchars($syllabus['source_email']) . '</span>';
                                    echo '</div>';
                                    echo '</td>';
                                    echo '<td class="small">' . htmlspecialchars($syllabus['delivered_on']) . '</td>';
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