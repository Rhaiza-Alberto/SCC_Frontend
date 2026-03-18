<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Initialize session-based "database" for prototype if empty or contains dummy data
if (!isset($_SESSION['submissions']) || (isset($_SESSION['submissions'][0]['uploader_name']) && $_SESSION['submissions'][0]['uploader_name'] === 'Alice Johnson')) {
    $_SESSION['submissions'] = [];
}

// Get user information from session
$username = $_SESSION['username'] ?? 'VPAA';
$email = $_SESSION['email'] ?? 'vpaa@gmail.com';
$role = $_SESSION['role'] ?? 'vpaa';
$role_display = 'VPAA';

// Initialize session-based "database" for prototype if empty or contains dummy data
if (!isset($_SESSION['submissions']) || (isset($_SESSION['submissions'][0]['uploader_name']) && $_SESSION['submissions'][0]['uploader_name'] === 'Alice Johnson')) {
    $_SESSION['submissions'] = [];
}

// Handle Actions (Approve/Reject)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = (int) $_GET['id'];

    if (isset($_SESSION['submissions'])) {
        foreach ($_SESSION['submissions'] as &$s) {
            if ($s['id'] === $id) {
                if ($action === 'approve')
                    $s['status'] = 'Approved';
                if ($action === 'reject')
                    $s['status'] = 'Rejected';
                break;
            }
        }
        header('Location: syllabus_review.php');
        exit();
    }
}

// Get submissions from session
$submissions = $_SESSION['submissions'] ?? [];
$pending_submissions = array_filter($submissions, fn($s) => $s['status'] == 'Pending');
$approved_submissions = array_filter($submissions, fn($s) => $s['status'] == 'Approved');
$declined_submissions = array_filter($submissions, fn($s) => $s['status'] == 'Rejected');
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
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
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
                <h5 class="font-serif fw-bold text-orange mb-0">
                    <?php echo $role_display; ?>
                </h5>
                <p class="text-white-50 small fw-bold mb-0" style="font-size: 0.75rem;">
                    <?php echo htmlspecialchars($username); ?>
                </p>
            </div>

            <nav class="nav flex-column gap-2 mb-auto">
                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
                <a href="vpaa_dashboard.php"
                    class="nav-link text-white p-3 rounded hover-effect text-decoration-none d-block">
                    Dashboard
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
                <a href="syllabus_review.php"
                    class="nav-link text-white active-nav-link p-3 rounded text-decoration-none d-block">
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-orange font-serif fw-bold">Syllabus Review</h2>
                <div class="text-end">
                    <span class="badge bg-secondary opacity-50 rounded-pill px-3 py-1 shadow-sm"
                        style="font-size: 0.85rem;">
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
                    <p class="mb-0 text-muted opacity-50 small">No syllabus submissions awaiting your final review.</p>
                </div>
            </div>

            <!-- Tabbed Submissions Section -->
            <div class="card premium-card p-4 mb-5 shadow-sm border-orange border-opacity-10">
                <ul class="nav nav-tabs nav-fill mb-4 border-bottom" id="syllabusTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active font-serif fw-bold text-orange" id="pending-tab"
                            data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab"
                            aria-controls="pending" aria-selected="true">
                            Pending Final Approval
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link font-serif fw-bold text-orange" id="approved-tab" data-bs-toggle="tab"
                            data-bs-target="#approved" type="button" role="tab" aria-controls="approved"
                            aria-selected="false">
                            Fully Approved
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

                <div class="tab-content" id="syllabusTabContent">
                    <!-- Pending Tab -->
                    <div class="tab-pane fade show active" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-premium">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="text-secondary small">#</th>
                                        <th scope="col" class="text-secondary small">INSTRUCTOR</th>
                                        <th scope="col" class="text-secondary small">DEPARTMENT</th>
                                        <th scope="col" class="text-secondary small">COURSE</th>
                                        <th scope="col" class="text-secondary small">STATUS</th>
                                        <th scope="col" class="text-secondary small text-center">FILE</th>
                                        <th scope="col" class="text-secondary small">SUBMITTED</th>
                                        <th scope="col" class="text-secondary small text-center">ACTION</th>
                                    </tr>
                                <?php endforeach; endif; ?>
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
                                        <th scope="col" class="text-secondary small">DEPARTMENT</th>
                                        <th scope="col" class="text-secondary small">COURSE</th>
                                        <th scope="col" class="text-secondary small">STATUS</th>
                                        <th scope="col" class="text-secondary small text-center">FILE</th>
                                        <th scope="col" class="text-secondary small">APPROVED ON</th>
                                    </tr>
                                <?php endforeach; endif; ?>
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
                                        <th scope="col" class="text-secondary small">DEPARTMENT</th>
                                        <th scope="col" class="text-secondary small">COURSE</th>
                                        <th scope="col" class="text-secondary small">STATUS</th>
                                        <th scope="col" class="text-secondary small">REASON</th>
                                        <th scope="col" class="text-secondary small">DECLINED ON</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (empty($declined_submissions)) {
                                        echo '<tr><td colspan="7" class="text-center text-muted py-4">No declined submissions found</td></tr>';
                                    } else {
                                        foreach ($declined_submissions as $s) {
                                            echo '<tr>';
                                            echo '<td>' . $s['id'] . '</td>';
                                            echo '<td>' . htmlspecialchars($s['uploader_name']) . '</td>';
                                            echo '<td>' . htmlspecialchars($s['department']) . '</td>';
                                            echo '<td>' . htmlspecialchars($s['course_code']) . '</td>';
                                            echo '<td><span class="badge bg-danger">' . $s['status'] . '</span></td>';
                                            echo '<td>Incomplete Content</td>';
                                            echo '<td>' . $s['submitted_on'] . '</td>';
                                            echo '</tr>';
                                        }
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