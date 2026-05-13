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

// Fetch all roles except department_head
$stmt = $conn->prepare("SELECT * FROM roles WHERE role_name != 'department_head' ORDER BY role_name");
$stmt->execute();
$roles = $stmt->fetchAll();

// Fetch all colleges
$stmt = $conn->prepare("SELECT * FROM colleges ORDER BY college_name");
$stmt->execute();
$colleges = $stmt->fetchAll();

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $role_id = (int) $_POST['role_id'];
    $college_id = !empty($_POST['college_id']) ? (int) $_POST['college_id'] : null;

    // Validate
    if (empty($first_name) || empty($last_name) || empty($email) || empty($role_id)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Check if email already exists for another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $error = 'Email already exists.';
        } else {
            // Check if role is Dean/Admin and if one already exists (excluding current user)
            $stmt = $conn->prepare("SELECT role_name FROM roles WHERE id = ?");
            $stmt->execute([$role_id]);
            $target_role = $stmt->fetchColumn();

            if ($target_role === 'dean' || $target_role === 'admin') {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE (r.role_name = 'dean' OR r.role_name = 'admin') AND u.id != ? AND u.is_deleted = 0");
                $stmt->execute([$user_id]);
                if ($stmt->fetchColumn() > 0) {
                    $error = "A Dean/Admin account already exists. Only one is allowed.";
                }
            }

            if (!$error) {
                // Update user
                $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, role_id = ?, college_id = ? WHERE id = ?");
                $stmt->execute([$first_name, $last_name, $email, $role_id, $college_id, $user_id]);

                // If password is provided, update it
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
    <title>Edit User — SCC Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php $active_page = 'manage_users'; include '_sidebar.php'; ?>

    <main class="scc-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color:var(--text)">Edit <span style="color:var(--primary)">User Account</span></h4>
                <p style="font-size:0.85rem;color:var(--text-secondary);margin:0">Update member details or change administrative roles</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <div class="position-relative" style="cursor:pointer" data-bs-toggle="dropdown">
                        <i class="bi bi-bell fs-5" style="color:var(--text)"></i>
                        <?php if ($unread_count > 0): ?><span class="notif-badge"><?= $unread_count > 9 ? '9+' : $unread_count ?></span><?php endif; ?>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="width:340px;max-height:420px;overflow-y:auto;border-radius:var(--radius-md);background:var(--bg-card)">
                        <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom sticky-top" style="background:var(--bg-card)">
                            <strong style="font-size:0.9rem;color:var(--text)">Notifications</strong>
                            <?php if ($unread_count > 0): ?><a href="?id=<?= $user_id ?>&mark_read=1" class="text-decoration-none small" style="color:var(--primary)">Mark all read</a><?php endif; ?>
                        </li>
                        <?php if (empty($notifications)): ?>
                            <li class="px-3 py-4 text-center" style="color:var(--text-muted)"><i class="bi bi-bell-slash fs-4 d-block mb-2 opacity-50"></i><span class="small">No notifications</span></li>
                        <?php else: foreach ($notifications as $n): $color = get_notification_color($n['message']); ?>
                            <li class="border-bottom" style="<?= !$n['is_read'] ? 'background:var(--primary-light)' : '' ?>">
                                <a href="notifications.php?notif_id=<?= $n['id'] ?>" class="text-decoration-none d-block px-3 py-2">
                                    <p class="mb-0 small" style="color:var(--text)"><span class="<?= $color['text'] ?> fw-bold me-1"><?= $color['icon'] ?></span><?= htmlspecialchars($n['message']) ?></p>
                                    <span style="font-size:.7rem;color:var(--text-muted)"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></span>
                                </a>
                            </li>
                        <?php endforeach; endif; ?>
                        <li style="background:var(--bg-card);border-top:1px solid var(--border)"><a href="notifications.php" class="d-block text-center text-decoration-none small fw-bold py-2" style="color:var(--primary)">View all notifications</a></li>
                    </ul>
                </div>
                <a href="manage_user.php" class="btn btn-light border fw-bold text-secondary rounded-pill px-4">
                    <i class="bi bi-arrow-left me-2"></i> Back to Users
                </a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success border-0 shadow-sm mb-4 d-flex align-items-center animate-in" style="border-radius:var(--radius-md)">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger border-0 shadow-sm mb-4 d-flex align-items-center animate-in" style="border-radius:var(--radius-md)">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="scc-card animate-in" style="max-width: 900px; margin: 0 auto;">
            <div class="card-header border-0 bg-transparent p-4 pb-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary-light rounded-circle d-flex align-items-center justify-content-center" style="width:64px;height:64px;">
                        <i class="bi bi-person-gear text-primary fs-3"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0" style="color:var(--text)">User <span style="color:var(--primary)">Profile Update</span></h6>
                        <p class="small text-muted mb-0">Modifying account for: <span class="fw-bold text-dark"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></span></p>
                    </div>
                </div>
            </div>
            <div class="card-body p-4 p-md-5">
                <form method="POST">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Email Address <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                                <input type="email" name="email" class="form-control border-start-0" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Assigned Role <span class="text-danger">*</span></label>
                            <select name="role_id" class="form-select" required>
                                <option value="">Select Role</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['id'] ?>" <?= $role['id'] == $user['role_id'] ? 'selected' : '' ?>><?= ucfirst($role['role_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">College / Institution</label>
                            <select name="college_id" class="form-select">
                                <option value="">Select College</option>
                                <?php foreach ($colleges as $col): ?>
                                    <option value="<?= $col['id'] ?>" <?= $col['id'] == $user['college_id'] ? 'selected' : '' ?>><?= htmlspecialchars($col['college_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Reset Password <small class="text-muted">(Optional)</small></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                                <input type="password" name="password" class="form-control border-start-0" placeholder="Enter new password to reset">
                            </div>
                        </div>

                        <div class="col-12 mt-5 border-top pt-4">
                            <div class="row g-2">
                                <div class="col-md-8">
                                    <button type="button" onclick="confirmEdit()" class="btn btn-primary btn-lg w-100 fw-bold rounded-pill shadow-sm">
                                        <i class="bi bi-check2-circle me-2"></i> Save Changes
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <a href="manage_user.php" class="btn btn-light btn-lg w-100 fw-bold rounded-pill border">Cancel</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
        </div>
    </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/common.js"></script>
    <script>
        function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('active');}
        
        function confirmEdit() {
            Swal.fire({
                title: 'Confirm Changes',
                text: "Are you sure you want to update this user's account information?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: 'var(--primary)',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Save Changes'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.querySelector('form').submit();
                }
            });
        }
    </script>
</body>

</html>