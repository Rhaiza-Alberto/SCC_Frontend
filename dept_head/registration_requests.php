<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$username     = $_SESSION['username'] ?? 'Dr. Jane Smith';
$email        = $_SESSION['email'] ?? '';
$role         = $_SESSION['role'] ?? 'dept_head';
$role_display = 'Dept Head Panel';
$dept_id      = $_SESSION['department_id'] ?? null;

$conn = get_db();

// Handle Approve / Reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'])) {
    $target_id = (int) $_POST['user_id'];
    $action    = $_POST['action'];

    if ($action === 'approve') {
        $conn->prepare("UPDATE users SET is_approved = 1 WHERE id = ?")
             ->execute([$target_id]);

        // Notify the faculty user
        notify_user($target_id, " Your registration has been approved by the Department Head. You may now log in.", null);

    } elseif ($action === 'reject') {
        $conn->prepare("UPDATE users SET is_deleted = 1 WHERE id = ?")
             ->execute([$target_id]);
    }

    header('Location: registration_requests.php');
    exit();
}

// Fetch pending faculty registrations for this department
$pending_registrations = [];
if ($dept_id) {
    $stmt = $conn->prepare("
        SELECT u.id, u.first_name, u.middle_name, u.last_name,
               u.email, u.created_at, d.department_name
        FROM users u
        JOIN departments d ON u.department_id = d.id
        JOIN roles r ON u.role_id = r.id
        WHERE r.role_name = 'faculty'
          AND u.is_approved = 0
          AND u.is_deleted = 0
          AND u.department_id = ?
        ORDER BY u.created_at DESC
    ");
    $stmt->execute([$dept_id]);
    $pending_registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$reg_count = count($pending_registrations);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Requests - SCC-CCS Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
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
                <a href="dept_dashboard.php" class="nav-link text-white p-3 rounded hover-effect">Dashboard</a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
                <a href="syllabus_review.php" class="nav-link text-white p-3 rounded hover-effect">Syllabus Review</a>
                <a href="upload_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">Upload Syllabus</a>
                <a href="my_submissions.php" class="nav-link text-white p-3 rounded hover-effect">My Submissions</a>
                <a href="shared_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">Shared Syllabus</a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">ACCOUNT MANAGEMENT</div>
                <a href="registration_requests.php" class="nav-link text-white active-nav-link p-3 rounded">
                    Registration Requests
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
                <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect">Profile</a>
                <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-grow-1 p-5" style="margin-left: 260px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-orange font-serif fw-bold">Registration Requests</h2>
                <div class="text-end">
                    <span class="badge bg-secondary opacity-50 rounded-pill px-3 py-1 shadow-sm"
                        style="font-size: 0.85rem;">
                        <i class="bi bi-person-plus-fill me-1"></i><?php echo $reg_count; ?> New
                    </span>
                </div>
            </div>

            <!-- Alert: all caught up vs pending -->
            <?php if ($reg_count === 0): ?>
            <div class="alert alert-danger border-0 shadow-sm mb-4 d-flex align-items-center p-3 rounded-3 bg-opacity-10"
                style="background-color: rgba(220, 53, 69, 0.1);">
                <div class="bg-danger text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center shadow-sm"
                    style="width: 45px; height: 45px;">
                    <i class="bi bi-megaphone-fill fs-5"></i>
                </div>
                <div>
                    <h6 class="alert-heading font-serif fw-bold mb-1 text-muted opacity-75">All Caught Up</h6>
                    <p class="mb-0 text-muted opacity-50 small">No pending faculty registration requests at this time.</p>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-warning border-0 shadow-sm mb-4 d-flex align-items-center p-3 rounded-3"
                style="background-color: rgba(255, 193, 7, 0.1);">
                <div class="bg-warning text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center shadow-sm"
                    style="width: 45px; height: 45px;">
                    <i class="bi bi-megaphone-fill fs-5"></i>
                </div>
                <div>
                    <h6 class="alert-heading font-serif fw-bold mb-1 text-muted opacity-75">Action Required</h6>
                    <p class="mb-0 text-muted opacity-50 small"><?php echo $reg_count; ?> faculty registration(s) awaiting your approval.</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Pending Faculty Registrations Table -->
            <div class="card premium-card p-4 mb-5 shadow-sm border-orange border-opacity-25">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title font-serif fw-bold mb-0 text-orange">Pending Faculty Registrations</h5>
                    <?php if ($reg_count > 0): ?>
                        <span class="badge bg-orange rounded-pill px-3"><?php echo $reg_count; ?> New Requests</span>
                    <?php endif; ?>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-premium">
                        <thead class="table-light">
                            <tr>
                                <th class="text-secondary small">#</th>
                                <th class="text-secondary small">FULL NAME</th>
                                <th class="text-secondary small">EMAIL ADDRESS</th>
                                <th class="text-secondary small">DEPARTMENT</th>
                                <th class="text-secondary small">DATE REGISTERED</th>
                                <th class="text-secondary small">ACTION</th>
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
                                        <td><?php echo $index + 1; ?></td>
                                        <td><span class="fw-bold">
                                            <?php echo htmlspecialchars(trim($reg['first_name'] . ' ' . ($reg['middle_name'] ? $reg['middle_name'] . ' ' : '') . $reg['last_name'])); ?>
                                        </span></td>
                                        <td><?php echo htmlspecialchars($reg['email']); ?></td>
                                        <td><?php echo htmlspecialchars($reg['department_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($reg['created_at'])); ?></td>
                                        <td>
                                            <button onclick="handleAction('approve', <?php echo $reg['id']; ?>, 'Approve')"
                                                class="btn btn-sm btn-outline-success me-2 px-3 rounded-pill">Approve</button>
                                            <button onclick="handleAction('reject', <?php echo $reg['id']; ?>, 'Reject')"
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

    <!-- Hidden forms for approve/reject -->
    <form id="actionForm" method="POST" action="registration_requests.php">
        <input type="hidden" name="action" id="formAction">
        <input type="hidden" name="user_id" id="formUserId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function handleAction(action, userId, label) {
            Swal.fire({
                title: 'Are you sure?',
                text: `You are about to ${label} this registration request.`,
                icon: action === 'approve' ? 'success' : 'warning',
                showCancelButton: true,
                confirmButtonColor: action === 'approve' ? '#198754' : '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Yes, ${label}!`
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('formAction').value = action;
                    document.getElementById('formUserId').value = userId;
                    document.getElementById('actionForm').submit();
                }
            });
        }
    </script>
</body>
</html>