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
$stmt = $conn->prepare("SELECT users.*, roles.role_name, departments.department_name 
                        FROM users
                        LEFT JOIN roles ON users.role_id = roles.id
                        LEFT JOIN departments ON users.department_id = departments.id
                        WHERE users.id = ? AND users.is_deleted = 0");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: manage_user.php');
    exit();
}

// Fetch all roles
$stmt = $conn->prepare("SELECT * FROM roles ORDER BY role_name");
$stmt->execute();
$roles = $stmt->fetchAll();

// Fetch all departments
$stmt = $conn->prepare("SELECT * FROM departments ORDER BY department_name");
$stmt->execute();
$departments = $stmt->fetchAll();

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $role_id = (int) $_POST['role_id'];
    $department_id = !empty($_POST['department_id']) ? (int) $_POST['department_id'] : null;
    
    // Validate
    if (empty($first_name) || empty($last_name) || empty($email) || empty($role_id)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Check if email already exists for another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? AND is_deleted = 0");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $error = 'Email already exists.';
        } else {
            // Update user
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, role_id = ?, department_id = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $email, $role_id, $department_id, $user_id]);
            
            // If password is provided, update it
            if (!empty($_POST['password'])) {
                $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
            }
            
            $success = 'User updated successfully!';
            
            // Refresh user data
            $stmt = $conn->prepare("SELECT users.*, roles.role_name, departments.department_name 
                                    FROM users
                                    LEFT JOIN roles ON users.role_id = roles.id
                                    LEFT JOIN departments ON users.department_id = departments.id
                                    WHERE users.id = ? AND users.is_deleted = 0");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
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
    </style>
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
                <a href="admin_dashboard.php" class="nav-link text-white p-3 rounded hover-effect">Dashboard</a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
                <a href="syllabus_review.php" class="nav-link text-white p-3 rounded hover-effect">Syllabus Review</a>
                <a href="upload_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">Upload Syllabus</a>
                <a href="my_submissions.php" class="nav-link text-white p-3 rounded hover-effect">My Submissions</a>
                <a href="shared_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">Shared Syllabus</a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">USER MANAGEMENT</div>
                <a href="registration_requests.php" class="nav-link text-white p-3 rounded hover-effect">Registration Requests</a>
                <a href="manage_user.php" class="nav-link text-white p-3 rounded active-nav-link">Manage Users</a>
                <a href="add_user.php" class="nav-link text-white p-3 rounded hover-effect">Add User</a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
                <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect">Profile</a>
                <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
            </nav>
        </div>

        <div class="main-content flex-grow-1 p-5" style="margin-left: 260px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-orange font-serif fw-bold">Edit User</h2>
                <a href="manage_user.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left me-2"></i> Back to Users</a>
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
                            <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Role <span class="text-danger">*</span></label>
                            <select name="role_id" class="form-select" required>
                                <option value="">Select Role</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['id'] ?>" <?= $role['id'] == $user['role_id'] ? 'selected' : '' ?>>
                                        <?= ucfirst($role['role_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Department</label>
                            <select name="department_id" class="form-select">
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>" <?= $dept['id'] == $user['department_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dept['department_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">New Password <small class="text-muted">(leave blank to keep current)</small></label>
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