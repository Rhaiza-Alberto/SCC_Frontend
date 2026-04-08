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
$username = $_SESSION['username'] ?? 'Dr. Jane Smith';
$email = $_SESSION['email'] ?? '';
$role = $_SESSION['role'] ?? 'dept_head';
$role_display = 'Dept Head Panel';

// Fetch user profile from database
$user_id = $_SESSION['user_id'];

$conn = get_db();
$stmt = $conn->prepare("
    SELECT 
        u.first_name,
        u.middle_name,
        u.last_name,
        u.birthdate,
        u.sex,
        u.email,
        d.department_name,
        c.college_name
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    LEFT JOIN colleges    c ON d.college_id    = c.id
    WHERE u.id = ? AND u.is_deleted = 0
");
$stmt->execute([$user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$profile = [
    'first_name'  => $row['first_name']      ?? '',
    'middle_name' => $row['middle_name']      ?? '',
    'last_name'   => $row['last_name']        ?? '',
    'birthdate'   => $row['birthdate']        ?? '',
    'sex'         => $row['sex']              ?? '',
    'college'     => $row['college_name']     ?? '',
    'department'  => $row['department_name']  ?? '',
    'email'       => $row['email']            ?? $email,
];

$edit_mode = isset($_GET['edit']) && $_GET['edit'] == 'true';

$user_id       = $_SESSION['user_id'];
$notifications = get_notifications($user_id, 10);
$unread_count  = count_unread_notifications($user_id);

// Handle "mark all read" action
if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: profile.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - SCC-CCS Syllabus Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap"
        rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>

<body class="bg-light">

    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar sidebar-premium text-white p-2 min-vh-100 d-flex flex-column"
            style="width:260px; position:fixed; z-index:1100;">
            <div class="text-center mb-3 mt-2">
                <img src="../css/logo.png" alt="CCS Logo" class="rounded-circle mb-2"
            style="width:80px;height:80px;border:2px solid rgba(255,136,0,.5);padding:3px;">
                <h5 class="font-serif fw-bold text-orange mb-0"><?= $role_display ?></h5>
                <p class="text-white-50 small fw-bold mb-0" style="font-size:.75rem;"><?= htmlspecialchars($username) ?></p>
            </div>
            <nav class="nav flex-column gap-2 mb-auto">
                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
                <a href="admin_dashboard.php" class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active-nav-link' : 'hover-effect' ?>">Dashboard</a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
                <a href="syllabus_review.php" class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'syllabus_review.php' ? 'active-nav-link' : 'hover-effect' ?>">
            Syllabus Review
                    <?php if (isset($pending_review_count) && $pending_review_count > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $pending_review_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="upload_syllabus.php" class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'upload_syllabus.php' ? 'active-nav-link' : 'hover-effect' ?>">Upload Syllabus</a>
                <a href="my_submissions.php" class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'my_submissions.php' ? 'active-nav-link' : 'hover-effect' ?>">My Submissions</a>
                <a href="shared_syllabus.php" class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'shared_syllabus.php' ? 'active-nav-link' : 'hover-effect' ?>">Shared Syllabus</a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">USER MANAGEMENT</div>
                <a href="registration_requests.php" class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'registration_requests.php' ? 'active-nav-link' : 'hover-effect' ?>">
            Registration Requests
                    <?php if (isset($reg_count) && $reg_count > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $reg_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="manage_user.php" class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'manage_user.php' ? 'active-nav-link' : 'hover-effect' ?>">Manage Users</a>
                <a href="add_user.php" class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'add_user.php' ? 'active-nav-link' : 'hover-effect' ?>">Add User</a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
                <a href="profile.php" class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active-nav-link' : 'hover-effect' ?>">Profile</a>
                <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-grow-1 p-5" style="margin-left: 260px;">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <h3 class="text-orange font-serif fw-bold mb-0">
                    <?php echo $edit_mode ? 'Edit my Profile' : 'My Profile'; ?>
                </h3>
                <div class="dropdown">
    <div class="position-relative" style="cursor:pointer;" data-bs-toggle="dropdown">
        <i class="bi bi-bell fs-4 text-secondary"></i>
        <?php if ($unread_count > 0): ?>
            <span class="notif-dot"></span>
        <?php endif; ?>
    </div>

    <ul class="dropdown-menu dropdown-menu-end shadow" 
        style="width:320px;max-height:400px;overflow-y:auto;">

        <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom">
            <strong>Notifications</strong>
            <?php if ($unread_count > 0): ?>
                <a href="?mark_read=1" class="text-decoration-none small text-orange">
                    Mark all read
                </a>
            <?php endif; ?>
        </li>

        <?php if (empty($notifications)): ?>
            <li class="px-3 py-3 text-center text-muted small">
                No notifications yet
            </li>

        <?php else: foreach ($notifications as $n): 
            $color = get_notification_color($n['message']); ?>

            <li class="px-3 py-2 border-bottom <?= !$n['is_read'] ? 'bg-light' : '' ?>">
                
                <p class="mb-0 small">
                    <span class="<?= $color['text'] ?> fw-bold me-1">
                        <?= $color['icon'] ?>
                    </span>

                    <span class="<?= $color['text'] ?>">
                        <?= htmlspecialchars($n['message']) ?>
                    </span>
                </p>

                <span class="text-muted" style="font-size:.7rem;">
                    <?= date('M d, Y h:i A', strtotime($n['created_at'])) ?>
                </span>

            </li>

        <?php endforeach; endif; ?>
        <li class="border-top">
    <a href="notifications.php" class="d-block text-center text-orange text-decoration-none small fw-bold py-2">
        View all notifications
    </a>
</li>
    </ul>
</div>
            </div>

            <div class="card premium-card shadow-sm p-5 bg-white mx-auto" style="max-width: 800px;">
                <p class="text-center text-muted small mb-4">Update your personal information</p>

                <form action="process_profile.php" method="POST">
                    
                    <!-- Name Fields -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="firstName" class="form-label fw-bold small">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="firstName" name="first_name" 
                                   value="<?php echo htmlspecialchars($profile['first_name']); ?>" 
                                   <?php echo !$edit_mode ? 'readonly' : 'required'; ?>>
                        </div>
                        <div class="col-md-4">
                            <label for="middleName" class="form-label fw-bold small">Middle Name</label>
                            <input type="text" class="form-control" id="middleName" name="middle_name" 
                                   value="<?php echo htmlspecialchars($profile['middle_name']); ?>" 
                                   <?php echo !$edit_mode ? 'readonly' : ''; ?>>
                        </div>
                        <div class="col-md-4">
                            <label for="lastName" class="form-label fw-bold small">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="lastName" name="last_name" 
                                   value="<?php echo htmlspecialchars($profile['last_name']); ?>" 
                                   <?php echo !$edit_mode ? 'readonly' : 'required'; ?>>
                        </div>
                    </div>

                    <!-- Birthdate and Sex -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="birthdate" class="form-label fw-bold small">Birthdate <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="birthdate" name="birthdate" 
                                   value="<?php echo htmlspecialchars($profile['birthdate']); ?>" 
                                   <?php echo !$edit_mode ? 'readonly' : 'required'; ?>>
                        </div>
                        <div class="col-md-6">
                            <label for="sex" class="form-label fw-bold small">Sex <span class="text-danger">*</span></label>
                            <select class="form-select" id="sex" name="sex" 
                                    <?php echo !$edit_mode ? 'disabled' : 'required'; ?>>
                                <option value="Male" <?php echo $profile['sex'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo $profile['sex'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>
                    </div>

                    <!-- College and Department -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="college" class="form-label fw-bold small">College</label>
                            <input type="text" class="form-control" id="college" name="college" 
                                   value="<?php echo htmlspecialchars($profile['college']); ?>" 
                                   <?php echo !$edit_mode ? 'readonly' : ''; ?>>
                        </div>
                        <div class="col-md-6">
                            <label for="department" class="form-label fw-bold small">Department</label>
                            <input type="text" class="form-control" id="department" name="department" 
                                   value="<?php echo htmlspecialchars($profile['department']); ?>" 
                                   <?php echo !$edit_mode ? 'readonly' : ''; ?>>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="mb-4">
                        <label for="email" class="form-label fw-bold small">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($profile['email']); ?>" readonly>
                    </div>

                    <!-- Buttons -->
                    <div class="d-grid">
                        <?php if ($edit_mode): ?>
                            <button type="submit" class="btn btn-login btn-lg fw-bold mb-2">Update my Profile</button>
                            <a href="profile.php" class="btn btn-outline-secondary btn-lg">Cancel</a>
                        <?php else: ?>
                            <a href="profile.php?edit=true" class="btn btn-login btn-lg fw-bold">Edit my profile</a>
                        <?php endif; ?>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
