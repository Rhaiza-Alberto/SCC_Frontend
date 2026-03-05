<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Initialize session-based "users" if empty
if (!isset($_SESSION['users'])) {
    $_SESSION['users'] = [
        ['id' => 1, 'username' => 'Achy', 'email' => 'faculty@gmail.com', 'role' => 'faculty', 'dept' => 'CS'],
        ['id' => 2, 'username' => 'Dr. Jane Smith', 'email' => 'dept@gmail.com', 'role' => 'dept_head', 'dept' => 'CS'],
        ['id' => 3, 'username' => 'VPAA', 'email' => 'vpaa@gmail.com', 'role' => 'vpaa', 'dept' => 'Institutional'],
        ['id' => 4, 'username' => 'Admin User', 'email' => 'admin@gmail.com', 'role' => 'admin', 'dept' => 'CCS'],
    ];
}

$username = $_SESSION['username'] ?? 'Dean / Admin';
$role_display = "Dean's Panel";
$users = $_SESSION['users'];

// Handle Deletion (Simulated)
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $_SESSION['users'] = array_filter($_SESSION['users'], fn($u) => $u['id'] !== $id);
    header('Location: manage_user.php');
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - SCC-CCS</title>
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
                <a href="admin_dashboard.php" class="nav-link text-white p-3 rounded hover-effect">
                    Dashboard
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
                <a href="upload_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">
                    Upload Syllabus
                </a>
                <a href="my_submissions.php" class="nav-link text-white p-3 rounded hover-effect">
                    My Submissions
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">USER MANAGEMENT</div>
                <a href="manage_user.php" class="nav-link text-white p-3 rounded active-nav-link">
                    Manage User
                </a>
                <a href="add_user.php" class="nav-link text-white p-3 rounded hover-effect">
                    Add User
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
                <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect text-decoration-none">
                    Profile
                </a>
                <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">
                    Logout
                </a>
            </nav>
        </div>

        <div class="main-content flex-grow-1 p-5" style="margin-left: 260px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-orange font-serif fw-bold">Manage Users</h2>
                <a href="add_user.php" class="btn btn-orange rounded-pill px-4 shadow-sm"><i
                        class="bi bi-person-plus me-2"></i> Add New User</a>
            </div>

            <div class="card premium-card p-4 shadow-sm border-0 bg-white">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr class="small text-muted text-uppercase">
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Department</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?php echo $u['id']; ?></td>
                                    <td><span class="fw-bold"><?php echo htmlspecialchars($u['username']); ?></span></td>
                                    <td class="small"><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td>
                                        <span
                                            class="badge rounded-pill px-3 py-1 bg-opacity-10 
                                        <?php echo $u['role'] == 'admin' ? 'bg-danger text-danger' :
                                            ($u['role'] == 'dept_head' ? 'bg-warning text-warning' : 'bg-primary text-primary'); ?>">
                                            <?php echo strtoupper($u['role']); ?>
                                        </span>
                                    </td>
                                    <td class="small text-muted"><?php echo htmlspecialchars($u['dept']); ?></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary border-0"><i
                                                    class="bi bi-pencil"></i></button>
                                            <a href="?delete=<?php echo $u['id']; ?>"
                                                class="btn btn-outline-danger border-0"
                                                onclick="return confirm('Delete this user?')"><i
                                                    class="bi bi-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>

</html>