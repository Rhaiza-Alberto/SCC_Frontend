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
    <title>Syllabus Review - SCC-CCS Syllabus Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap"
        rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-light">

    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar sidebar-premium text-white p-2 min-vh-100 d-flex flex-column"
            style="width: 260px; position: fixed; z-index: 1100;">
            <div class="text-center mb-3 mt-2">
                <img src="../css/logo.png" alt="CCS Logo" class="rounded-circle mb-2"
                    style="width: 80px; height: 80px; border: 2px solid rgba(255, 136, 0, 0.5); padding: 3px;">
                <h5 class="font-serif fw-bold text-orange mb-0">
                    <?php echo $role_display; ?>
                </h5>
                <p class="text-white-50 small fw-bold mb-0" style="font-size: 0.75rem;">
                    <?php echo htmlspecialchars($username); ?>
                </p>
            </div>

            <nav class="nav flex-column gap-2 mb-auto">
                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
                <a href="dept_dashboard.php" class="nav-link text-white p-3 rounded hover-effect">
                    Dashboard
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
                <a href="syllabus_review.php" class="nav-link text-white active-nav-link p-3 rounded">
                    Syllabus Review
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-orange font-serif fw-bold">Syllabus Review</h2>
                <div class="text-end">
                    <span class="badge bg-secondary opacity-50 rounded-pill px-3 py-1 shadow-sm" style="font-size: 0.85rem;">
                        <i class="bi bi-exclamation-circle me-1"></i>0 New
                    </span>
                </div>
            </div>

            <!-- Urgent Notification Alert -->
            <div class="alert alert-danger border-0 shadow-sm mb-4 d-flex align-items-center p-3 rounded-3 bg-opacity-10"
                style="background-color: rgba(220, 53, 69, 0.1);">
                <div class="bg-danger text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center shadow-sm"
                    style="width: 45px; height: 45px;">
                    <i class="bi bi-megaphone-fill fs-5"></i>
                </div>
                <div>
                    <h6 class="alert-heading font-serif fw-bold mb-1 text-muted opacity-75">All Caught Up</h6>
                    <p class="mb-0 text-muted opacity-50 small">No faculty syllabus submissions awaiting review.</p>
                </div>
            </div>

            <!-- Tabbed Submissions Section -->
            <div class="card premium-card p-4 mb-5 shadow-sm border-orange border-opacity-10">
                <ul class="nav nav-tabs nav-fill mb-4 border-bottom" id="syllabusTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active font-serif fw-bold text-orange" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="true">
                            Pending Approval
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link font-serif fw-bold text-orange" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved" type="button" role="tab" aria-controls="approved" aria-selected="false">
                            Approved
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link font-serif fw-bold text-orange" id="declined-tab" data-bs-toggle="tab" data-bs-target="#declined" type="button" role="tab" aria-controls="declined" aria-selected="false">
                            Declined
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="syllabusTabContent">
                    <!-- Pending Tab -->
                    <div class="tab-pane fade show active" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-premium">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="text-secondary small">#</th>
                                        <th scope="col" class="text-secondary small">INSTRUCTOR</th>
                                        <th scope="col" class="text-secondary small">COURSE</th>
                                        <th scope="col" class="text-secondary small d-none d-lg-table-cell">TYPE</th>
                                        <th scope="col" class="text-secondary small">STATUS</th>
                                        <th scope="col" class="text-secondary small text-center">FILE</th>
                                        <th scope="col" class="text-secondary small">SUBMITTED</th>
                                        <th scope="col" class="text-secondary small text-center">ACTION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $pending_submissions = [];
                                    if (empty($pending_submissions)) {
                                        echo '<tr><td colspan="8" class="text-center text-muted py-4">No submissions awaiting approval</td></tr>';
                                    } else {
                                        foreach ($pending_submissions as $index => $sub) {
                                            // Handle table row logic...
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
                                        <th scope="col" class="text-secondary small">INSTRUCTOR</th>
                                        <th scope="col" class="text-secondary small">COURSE</th>
                                        <th scope="col" class="text-secondary small d-none d-lg-table-cell">TYPE</th>
                                        <th scope="col" class="text-secondary small">STATUS</th>
                                        <th scope="col" class="text-secondary small text-center">FILE</th>
                                        <th scope="col" class="text-secondary small">APPROVED ON</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $approved_submissions = [];
                                    if (empty($approved_submissions)) {
                                        echo '<tr><td colspan="7" class="text-center text-muted py-4">No approved submissions found</td></tr>';
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
                                        <th scope="col" class="text-secondary small">INSTRUCTOR</th>
                                        <th scope="col" class="text-secondary small">COURSE</th>
                                        <th scope="col" class="text-secondary small d-none d-lg-table-cell">TYPE</th>
                                        <th scope="col" class="text-secondary small">STATUS</th>
                                        <th scope="col" class="text-secondary small">REASON</th>
                                        <th scope="col" class="text-secondary small">DECLINED ON</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $declined_submissions = [];
                                    if (empty($declined_submissions)) {
                                        echo '<tr><td colspan="7" class="text-center text-muted py-4">No declined submissions found</td></tr>';
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
    <script>
        function handleAction(action, type) {
            Swal.fire({
                title: 'Are you sure?',
                text: `You are about to ${action} this ${type}.`,
                icon: action === 'Approve' ? 'success' : 'warning',
                showCancelButton: true,
                confirmButtonColor: action === 'Approve' ? '#198754' : '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Yes, ${action}!`
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire(
                        `${action}d!`,
                        `The ${type} has been ${action.toLowerCase()}d successfully.`,
                        'success'
                    );
                }
            });
        }
    </script>
</body>

</html>