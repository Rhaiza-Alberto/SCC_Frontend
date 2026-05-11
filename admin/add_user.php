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
$user_id = $_SESSION['user_id'];

if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: add_user.php');
    exit();
}

$unread_count = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);

$db = new Database();
$conn = $db->connect();

// Fetch all roles except department_head
$stmt = $conn->prepare("SELECT * FROM roles WHERE role_name != 'department_head' ORDER BY role_name");
$stmt->execute();
$roles = $stmt->fetchAll();

// Fetch all colleges
$stmt = $conn->prepare("SELECT * FROM colleges ORDER BY college_name");
$stmt->execute();
$colleges = $stmt->fetchAll();

// Handle Form Submission
$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role_id = (int) $_POST['role_id'];
    $college_id = !empty($_POST['college_id']) ? (int) $_POST['college_id'] : null;

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($role_id)) {
        $error = "Please fill in all required fields.";
    } else {
        // Check if role is Dean/Admin and if one already exists
        $stmt = $conn->prepare("SELECT role_name FROM roles WHERE id = ?");
        $stmt->execute([$role_id]);
        $target_role = $stmt->fetchColumn();

        if ($target_role === 'dean' || $target_role === 'admin') {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE (r.role_name = 'dean' OR r.role_name = 'admin') AND u.is_deleted = 0");
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                $error = "A Dean/Admin account already exists. Only one is allowed.";
            }
        }

        if (!$error) {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND is_deleted = 0");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email already registered.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role_id, college_id, is_approved, is_deleted, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, 0, NOW())");
                $stmt->execute([$first_name, $last_name, $email, $hashed_password, $role_id, $college_id]);
                $success = "User created successfully!";
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
    <title>Add User - SCC-CCS</title>
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
                <p class="text-white-50 small fw-bold mb-0" style="font-size:.75rem;"><?= htmlspecialchars($username) ?>
                </p>
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
                <a href="registration_requests.php" class="nav-link text-white p-3 rounded hover-effect">Registration
                    Requests</a>
                <a href="manage_user.php" class="nav-link text-white p-3 rounded hover-effect">Manage Users</a>
                <a href="add_user.php" class="nav-link text-white active-nav-link p-3 rounded">Add User</a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
                <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect">Profile</a>
                <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5 logout-link">Logout</a>
            </nav>
        </div>

        <div class="main-content flex-grow-1 p-5" style="margin-left: 260px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="text-orange font-serif fw-bold mb-0">Add New User</h2>
                    <p class="text-muted small">Enter details to register a new system member.</p>
                </div>
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
                                <a href="?mark_read=1" class="text-decoration-none small text-orange">Mark all read</a>
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
                                        <span class="text-muted"
                                            style="font-size:.7rem;"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></span>
                                    </a>
                                </li>
                            <?php endforeach; endif; ?>
                        <li class="dropdown-menu-sticky-footer">
                            <a href="notifications.php"
                                class="d-block text-center text-orange text-decoration-none small fw-bold py-2">View all
                                notifications</a>
                        </li>
                    </ul>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?>
                    <a href="manage_user.php" class="ms-3 fw-bold text-success text-decoration-none">View User List</a>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-center">
                <div class="card premium-card p-4 shadow-sm border-0 bg-white" style="max-width: 800px; width: 100%;">
                    <form method="POST">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">FIRST NAME</label>
                                <input type="text" class="form-control" name="first_name" placeholder="E.g. John"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">LAST NAME</label>
                                <input type="text" class="form-control" name="last_name" placeholder="E.g. Doe"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">GMAIL ADDRESS</label>
                                <input type="email" class="form-control" name="email" placeholder="user@gmail.com"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">PASSWORD</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">ASSIGNED ROLE</label>
                                <select class="form-select" name="role_id" required>
                                    <option value="" disabled selected>-- Select Role --</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= $role['id'] ?>"><?= ucfirst($role['role_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">COLLEGE</label>
                                <select class="form-select" name="college_id">
                                    <option value="">N/A</option>
                                    <?php foreach ($colleges as $col): ?>
                                        <option value="<?= $col['id'] ?>"><?= htmlspecialchars($col['college_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="text-center mt-4 pt-3 border-top">
                            <button type="reset" class="btn btn-light rounded-pill px-4 me-2">Clear</button>
                            <button type="submit" class="btn btn-orange rounded-pill px-5">Add User Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>