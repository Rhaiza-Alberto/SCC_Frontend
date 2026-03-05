<?php
session_start();
require_once 'database.php';

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
    <title>Registration Requests - SCC-CCS Syllabus Portal</title>
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
                <h5 class="font-serif fw-bold text-orange mb-0"><?php echo $role_display; ?></h5>
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
                <a href="syllabus_review.php" class="nav-link text-white p-3 rounded hover-effect">
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
                <a href="registration_requests.php" class="nav-link text-white active-nav-link p-3 rounded">
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
                <h2 class="text-orange font-serif fw-bold">Registration Requests</h2>
                <div class="text-end">
                    <span class="badge bg-secondary opacity-50 rounded-pill px-3 py-1 shadow-sm"
                        style="font-size: 0.85rem;">
                        <i class="bi bi-person-plus-fill me-1"></i>0 New
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
                    <p class="mb-0 text-muted opacity-50 small">No pending faculty registration requests at this time.
                    </p>
                </div>
            </div>

            <!-- Pending Faculty Registrations -->
            <div class="card premium-card p-4 mb-5 shadow-sm border-orange border-opacity-25">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title font-serif fw-bold mb-0 text-orange">Pending Faculty Registrations</h5>
                    <?php
                    // Dynamic count based on pending registrations array
                    $pending_registrations = [];
                    $reg_count = count($pending_registrations);
                    if ($reg_count > 0): ?>
                        <span class="badge bg-orange rounded-pill px-3">
                            <?php echo $reg_count; ?> New Requests
                        </span>
                    <?php endif; ?>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-premium">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="text-secondary small">#</th>
                                <th scope="col" class="text-secondary small">FULL NAME</th>
                                <th scope="col" class="text-secondary small">EMAIL ADDRESS</th>
                                <th scope="col" class="text-secondary small">DEPARTMENT</th>
                                <th scope="col" class="text-secondary small">DATE REGISTERED</th>
                                <th scope="col" class="text-secondary small">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pending_registrations)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No pending registrations found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pending_registrations as $index => $reg): ?>
                                    <tr class="bg-light-gray">
                                        <td>
                                            <?php echo $index + 1; ?>
                                        </td>
                                        <td><span class="fw-bold">
                                                <?php echo htmlspecialchars($reg['name']); ?>
                                            </span></td>
                                        <td>
                                            <?php echo htmlspecialchars($reg['email']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($reg['dept']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($reg['date']); ?>
                                        </td>
                                        <td>
                                            <button onclick="handleAction('Approve', 'registration request')"
                                                class="btn btn-sm btn-outline-success me-2 px-3 rounded-pill">Approve</button>
                                            <button onclick="handleAction('Reject', 'registration request')"
                                                class="btn btn-sm btn-outline-danger px-3 rounded-pill">Reject</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
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