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
    <title>My Submissions - SCC-CCS Syllabus Portal</title>
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


            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
            <a href="faculty_dashboard.php" class="nav-link text-white p-3 rounded hover-effect">
                Dashboard
            </a>

            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
            <a href="upload_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">
                Upload Syllabus
            </a>
            <a href="my_submissions.php" class="nav-link text-white active-nav-link p-3 rounded">
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
                <h3 class="text-orange font-serif fw-bold mb-0">My Syllabus Submissions</h3>
                <div class="notification-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                        class="bi bi-bell" viewBox="0 0 16 16">
                        <path
                            d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zM8 1.918l-.797.161A4.002 4.002 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4.002 4.002 0 0 0-3.203-3.92L8 1.917zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5.002 5.002 0 0 1 13 6c0 .88.32 4.2 1.22 6z" />
                    </svg>
                    <span class="notification-badge-dot"></span>
                </div>
            </div>

            <!-- Tabbed Submissions Section -->
            <div class="card premium-card p-4 mb-5 shadow-sm border-orange border-opacity-10">
                <ul class="nav nav-tabs nav-fill mb-4 border-bottom" id="submissionTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active font-serif fw-bold text-orange" id="pending-tab"
                            data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab"
                            aria-controls="pending" aria-selected="true">
                            Pending Approval
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link font-serif fw-bold text-orange" id="approved-tab" data-bs-toggle="tab"
                            data-bs-target="#approved" type="button" role="tab" aria-controls="approved"
                            aria-selected="false">
                            Approved
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link font-serif fw-bold text-orange" id="declined-tab" data-bs-toggle="tab"
                            data-bs-target="#declined" type="button" role="tab" aria-controls="declined"
                            aria-selected="false">
                            Declined
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="submissionTabContent">
                    <!-- Pending Tab -->
                    <div class="tab-pane fade show active" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-premium">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="text-secondary small">#</th>
                                        <th scope="col" class="text-secondary small">COURSE</th>
                                        <th scope="col" class="text-secondary small d-none d-lg-table-cell">TYPE</th>
                                        <th scope="col" class="text-secondary small">STATUS</th>
                                        <th scope="col" class="text-secondary small text-center">FILE</th>
                                        <th scope="col" class="text-secondary small">SUBMITTED ON</th>
                                        <th scope="col" class="text-secondary small text-center">ACTION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $pending_submissions = [];
                                    if (empty($pending_submissions)) {
                                        echo '<tr><td colspan="7" class="text-center text-muted py-4">No pending submissions</td></tr>';
                                    } else {
                                        foreach ($pending_submissions as $index => $sub) {
                                            // Handle table row logic...
                                            echo '<tr><td colspan="6">...</td>';
                                            echo '<td class="text-center"><a href="edit_syllabus.php?id=1" class="btn btn-sm btn-outline-orange rounded-pill px-3">Edit</a></td></tr>';
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Approved Tab -->
                    <div class="tab-pane fade" id="approved" role="tabpanel" aria-labelledby="approved-tab">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-premium">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="text-secondary small">#</th>
                                        <th scope="col" class="text-secondary small">COURSE</th>
                                        <th scope="col" class="text-secondary small d-none d-lg-table-cell">TYPE</th>
                                        <th scope="col" class="text-secondary small">STATUS</th>
                                        <th scope="col" class="text-secondary small">REVIEWER</th>
                                        <th scope="col" class="text-secondary small text-center">FILE</th>
                                        <th scope="col" class="text-secondary small">SUBMITTED ON</th>
                                        <th scope="col" class="text-secondary small text-center">ACTION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $approved_submissions = [];
                                    if (empty($approved_submissions)) {
                                        echo '<tr><td colspan="8" class="text-center text-muted py-4">No approved submissions found</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Declined Tab -->
                    <div class="tab-pane fade" id="declined" role="tabpanel" aria-labelledby="declined-tab">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-premium">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="text-secondary small">#</th>
                                        <th scope="col" class="text-secondary small">COURSE</th>
                                        <th scope="col" class="text-secondary small d-none d-lg-table-cell">TYPE</th>
                                        <th scope="col" class="text-secondary small">STATUS</th>
                                        <th scope="col" class="text-secondary small">COMMENT</th>
                                        <th scope="col" class="text-secondary small text-center">FILE</th>
                                        <th scope="col" class="text-secondary small">SUBMITTED ON</th>
                                        <th scope="col" class="text-secondary small text-center">ACTION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $declined_submissions = [];
                                    if (empty($declined_submissions)) {
                                        echo '<tr><td colspan="8" class="text-center text-muted py-4">No declined submissions found</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
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