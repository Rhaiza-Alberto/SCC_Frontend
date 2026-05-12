<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$username = $_SESSION['username'] ?? 'Dean / Admin';
$role_display = "Dean's Panel";
$user_id_session = $_SESSION['user_id'];

if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id_session);
    $current_id = (int) ($_GET['id'] ?? 0);
    header('Location: edit_user.php?id=' . $current_id);
    exit();
}

$unread_count = count_unread_notifications($user_id_session);
$notifications = get_notifications($user_id_session, 5);

// CONNECT (PDO)
$db = new Database();
$conn = $db->connect();

// Get user ID from URL
if (!isset($_GET['id'])) {
    header('Location: manage_user.php');
    exit();
}

$user_id = (int) $_GET['id'];

// Fetch user information
$stmt = $conn->prepare("SELECT users.*, roles.role_name, colleges.college_name 
                        FROM users
                        LEFT JOIN roles ON users.role_id = roles.id
                        LEFT JOIN colleges ON users.college_id = colleges.id
                        WHERE users.id = ? AND users.is_deleted = 0");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: manage_user.php');
    exit();
}

// Fetch only faculty and vpaa roles (dean and department_head managed via Transfer Role)
$stmt = $conn->prepare("SELECT * FROM roles WHERE role_name NOT IN ('dean', 'department_head') ORDER BY role_name");
$stmt->execute();
$roles = $stmt->fetchAll();

// Fetch all colleges
$stmt = $conn->prepare("SELECT * FROM colleges ORDER BY college_name");
$stmt->execute();
$colleges = $stmt->fetchAll();

// Handle form submission
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $role_id    = (int) $_POST['role_id'];
    $college_id = !empty($_POST['college_id']) ? (int) $_POST['college_id'] : null;

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($role_id)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Check duplicate email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? AND is_deleted = 0");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $error = 'Email already exists.';
        } else {
            // Get target role name
            $stmt = $conn->prepare("SELECT role_name FROM roles WHERE id = ?");
            $stmt->execute([$role_id]);
            $target_role = $stmt->fetchColumn();

            // Block assigning dean or department_head via this form
            if (in_array($target_role, ['dean', 'department_head'])) {
                $error = 'Dean and Department Head roles cannot be assigned here. Use the Transfer Role feature.';
            }

            // Block duplicate VPAA
            if (!$error && $target_role === 'vpaa') {
                $stmt = $conn->prepare("
                    SELECT COUNT(*) FROM users u
                    JOIN roles r ON u.role_id = r.id
                    WHERE r.role_name = 'vpaa'
                      AND u.id != ?
                      AND u.is_deleted = 0
                ");
                $stmt->execute([$user_id]);
                if ($stmt->fetchColumn() > 0) {
                    $error = 'A VPAA account already exists. Only one is allowed.';
                }
            }

            if (!$error) {
                // Update user
                $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, role_id = ?, college_id = ? WHERE id = ?");
                $stmt->execute([$first_name, $last_name, $email, $role_id, $college_id, $user_id]);

                // Update password if provided
                if (!empty($_POST['password'])) {
                    $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $user_id]);
                }

                $success = 'User updated successfully!';

                // Refresh user data
                $stmt = $conn->prepare("SELECT users.*, roles.role_name, colleges.college_name 
                                        FROM users
                                        LEFT JOIN roles ON users.role_id = roles.id
                                        LEFT JOIN colleges ON users.college_id = colleges.id
                                        WHERE users.id = ? AND users.is_deleted = 0");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - SCC-CCS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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

        .notif-dot {
            height: 8px;
            width: 8px;
            background-color: #ff8800;
            border-radius: 50%;
            display: inline-block;
            position: absolute;
            top: 0;
            right: 0;
        }
    </style>
</head>

<body class="bg-light">
    <div class="d-flex">
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
                <a href="admin_dashboard.php" class="nav-link text-white p-3 rounded hover-effect">Dashboard</a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
                <a href="syllabus_review.php" class="nav-link text-white p-3 rounded hover-effect">Syllabus Review</a>
                <a href="upload_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">Upload Syllabus</a>
                <a href="manage_courses.php" class="nav-link text-white p-3 rounded hover-effect">Manage Courses</a>
                <a href="my_submissions.php" class="nav-link text-white p-3 rounded hover-effect">My Submissions</a>
                <a href="shared_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">Shared Syllabus</a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">USER MANAGEMENT</div>
                <a href="registration_requests.php" class="nav-link text-white p-3 rounded hover-effect">Registration Requests</a>
                <a href="manage_user.php" class="nav-link text-white active-nav-link p-3 rounded">Manage Users</a>
                <a href="add_user.php" class="nav-link text-white p-3 rounded hover-effect">Add User</a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
                <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect">Profile</a>
                <a href="javascript:void(0)" class="nav-link text-white p-3 rounded hover-effect mt-5 logout-link">Logout</a>
            </nav>
        </div>

        <div class="main-content flex-grow-1 p-5" style="margin-left: 260px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="text-orange font-serif fw-bold mb-0">Edit User</h2>
                    <p class="text-muted small">Update member details or change account roles.</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <!-- Notification Bell -->
                    <div class="dropdown">
                        <div class="position-relative" style="cursor:pointer;" data-bs-toggle="dropdown">
                            <i class="bi bi-bell fs-4 text-dark"></i>
                            <?php if ($unread_count > 0): ?>
                                <span class="notif-dot"></span>
                            <?php endif; ?>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0"
                            style="width:320px;max-height:400px;overflow-y:auto;">
                            <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom sticky-top bg-white"
                                style="z-index:11;">
                                <strong>Notifications</strong>
                                <?php if ($unread_count > 0): ?>
                                    <a href="?id=<?= $user_id ?>&mark_read=1"
                                        class="text-decoration-none small text-orange">Mark all read</a>
                                <?php endif; ?>
                            </li>
                            <?php if (empty($notifications)): ?>
                                <li class="px-3 py-3 text-center text-muted small">No notifications yet</li>
                            <?php else:
                                foreach ($notifications as $n):
                                    $color = get_notification_color($n['message']); ?>
                                    <li class="border-bottom <?= !$n['is_read'] ? 'bg-light' : '' ?>">
                                        <a href="notifications.php?notif_id=<?= $n['id'] ?>"
                                            class="text-decoration-none text-dark d-block px-3 py-2">
                                            <p class="mb-0 small">
                                                <span class="<?= $color['text'] ?> fw-bold me-1"><?= $color['icon'] ?></span>
                                                <span class="<?= $color['text'] ?>"><?= htmlspecialchars($n['message']) ?></span>
                                            </p>
                                            <span class="text-muted" style="font-size:.7rem;"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; endif; ?>
                            <li class="dropdown-menu-sticky-footer">
                                <a href="notifications.php"
                                    class="d-block text-center text-orange text-decoration-none small fw-bold py-2">View all notifications</a>
                            </li>
                        </ul>
                    </div>
                    <a href="manage_user.php" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="bi bi-arrow-left me-2"></i> Back to Users
                    </a>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card premium-card p-4 shadow-sm border-0 bg-white">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control"
                                value="<?= htmlspecialchars($user['first_name']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control"
                                value="<?= htmlspecialchars($user['last_name']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control"
                                value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Role <span class="text-danger">*</span></label>
                            <?php if (in_array($user['role_name'], ['dean', 'department_head'])): ?>
                                <!-- Lock role for dean/department_head — use Transfer Role instead -->
                                <input type="hidden" name="role_id" value="<?= $user['role_id'] ?>">
                                <input type="text" class="form-control bg-light"
                                    value="<?= ucfirst(str_replace('_', ' ', $user['role_name'])) ?>" disabled>
                                <small class="text-muted">
                                    Use the <a href="transfer_dean_role.php" class="text-orange">Transfer Role</a> feature to reassign this role.
                                </small>
                            <?php else: ?>
                                <select name="role_id" class="form-select" required>
                                    <option value="">Select Role</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= $role['id'] ?>" <?= $role['id'] == $user['role_id'] ? 'selected' : '' ?>>
                                            <?= ucfirst(str_replace('_', ' ', $role['role_name'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">College</label>
                            <select name="college_id" class="form-select">
                                <option value="">Select College</option>
                                <?php foreach ($colleges as $col): ?>
                                    <option value="<?= $col['id'] ?>" <?= $col['id'] == $user['college_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($col['college_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">New Password
                                <small class="text-muted">(leave blank to keep current)</small>
                            </label>
                            <input type="password" name="password" class="form-control" placeholder="Enter new password">
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-orange rounded-pill px-4">
                                <i class="bi bi-save me-2"></i>Update User
                            </button>
                            <a href="manage_user.php" class="btn btn-outline-secondary rounded-pill px-4 ms-2">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>