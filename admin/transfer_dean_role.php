 <?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Check if current user is dean
$db   = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("SELECT users.*, roles.role_name FROM users
                        LEFT JOIN roles ON users.role_id = roles.id
                        WHERE users.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();

if ($current_user['role_name'] !== 'dean') {
    header('Location: admin_dashboard.php');
    exit();
}

$username     = $_SESSION['username'] ?? 'Dean / Admin';
$role_display = "Dean's Panel";

// Fetch all eligible users (exclude current dean and other deans)
$stmt = $conn->prepare("SELECT users.*, roles.role_name, departments.department_name
                        FROM users
                        LEFT JOIN roles       ON users.role_id       = roles.id
                        LEFT JOIN departments ON users.department_id = departments.id
                        WHERE users.is_deleted = 0
                        AND users.id != ?
                        AND roles.role_name != 'dean'
                        ORDER BY users.first_name, users.last_name");
$stmt->execute([$_SESSION['user_id']]);
$eligible_users = $stmt->fetchAll();

// Get dean role ID
$stmt = $conn->prepare("SELECT id FROM roles WHERE role_name = 'dean'");
$stmt->execute();
$dean_role    = $stmt->fetch();
$dean_role_id = $dean_role['id'];

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_dean_id  = (int) ($_POST['new_dean_id'] ?? 0);
    $confirmation = $_POST['confirmation'] ?? '';

    if ($new_dean_id <= 0) {
        $error = 'Please select a user to transfer the dean role to.';
    } elseif ($confirmation !== 'TRANSFER') {
        $error = 'Please type "TRANSFER" to confirm the role transfer.';
    } else {
        try {
            $conn->beginTransaction();

            // Get the new dean's current role_id — also verify they are not already a dean
            // and that they actually exist and are not deleted (guard against tampered POST)
            $stmt = $conn->prepare("SELECT users.role_id, roles.role_name FROM users
                                    LEFT JOIN roles ON users.role_id = roles.id
                                    WHERE users.id = ? AND users.is_deleted = 0
                                    AND roles.role_name != 'dean'");
            $stmt->execute([$new_dean_id]);
            $new_dean_old = $stmt->fetch();

            if (!$new_dean_old) {
                // User doesn't exist, is deleted, or is already a dean — abort
                $conn->rollBack();
                $error = 'Invalid user selected. Please try again.';
            } else {
                // Promote selected user to dean
                $stmt = $conn->prepare("UPDATE users SET role_id = ? WHERE id = ?");
                $stmt->execute([$dean_role_id, $new_dean_id]);

                // Demote current dean to the new dean's old role
                $stmt = $conn->prepare("UPDATE users SET role_id = ? WHERE id = ?");
                $stmt->execute([$new_dean_old['role_id'], $_SESSION['user_id']]);

                $conn->commit();

                // Destroy current dean's session and redirect to login
                session_destroy();
                header('Location: ../login.php?msg=role_transferred');
                exit();
            }
        } catch (Exception $e) {
            $conn->rollBack();
            $error = 'An error occurred during the transfer. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Dean Role - SCC-CCS</title>
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

        .warning-box {
            background-color: #fff3cd;
            border-left: 4px solid #ff8800;
            padding: 20px;
            border-radius: 8px;
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
                <a href="manage_user.php" class="nav-link text-white p-3 rounded hover-effect">Manage Users</a>
                <a href="add_user.php" class="nav-link text-white p-3 rounded hover-effect">Add User</a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
                <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect">Profile</a>
                <a href="transfer_dean_role.php" class="nav-link text-white p-3 rounded active-nav-link">Transfer Role</a>
                <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
            </nav>
        </div>

        <div class="main-content flex-grow-1 p-5" style="margin-left: 260px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-orange font-serif fw-bold">Transfer Dean Role</h2>
                <a href="manage_user.php" class="btn btn-outline-secondary rounded-pill px-4">
                    <i class="bi bi-arrow-left me-2"></i> Back
                </a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="warning-box mb-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Important Notice</h5>
                <p class="mb-2">You are about to transfer your Dean role to another user. Please note:</p>
                <ul class="mb-0">
                    <li>This action cannot be undone</li>
                    <li>You will immediately lose all Dean privileges</li>
                    <li>The selected user will become the new Dean</li>
                    <li>You will be automatically logged out after the transfer</li>
                    <li>Your role will be changed to the role of the user you selected</li>
                    <li>The new Dean must log out and log back in if they are currently active</li>
                </ul>
            </div>

            <div class="card premium-card p-4 shadow-sm border-0 bg-white">
                <form method="POST" onsubmit="return confirm('Are you absolutely sure you want to transfer the Dean role? This action cannot be undone!');">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Select New Dean <span class="text-danger">*</span></label>
                        <select name="new_dean_id" class="form-select" required>
                            <option value="">Choose a user...</option>
                            <?php foreach ($eligible_users as $user): ?>
                                <option value="<?= (int) $user['id'] ?>">
                                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                    - <?= htmlspecialchars(ucfirst($user['role_name'])) ?>
                                    <?php if ($user['department_name']): ?>
                                        (<?= htmlspecialchars($user['department_name']) ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Select the user who will receive the Dean role</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Confirmation <span class="text-danger">*</span></label>
                        <input type="text" name="confirmation" class="form-control" placeholder="Type 'TRANSFER' to confirm" required>
                        <small class="text-muted">Type <strong>TRANSFER</strong> (in capital letters) to confirm this action</small>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-danger rounded-pill px-4">
                            <i class="bi bi-arrow-left-right me-2"></i>Transfer Dean Role
                        </button>
                        <a href="manage_user.php" class="btn btn-outline-secondary rounded-pill px-4 ms-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>