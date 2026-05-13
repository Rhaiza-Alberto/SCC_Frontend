<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

restrict_to_role('dean');

$username = $_SESSION['username'] ?? 'Dean / Admin';
$role_display = "Dean's Panel";

// CONNECT (PDO)
$db = new Database();
$conn = $db->connect();

// Check if current user is dean
$stmt = $conn->prepare("SELECT users.*, roles.role_name FROM users
                        LEFT JOIN roles ON users.role_id = roles.id
                        WHERE users.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();
$is_dean = ($current_user['role_name'] === 'dean');

// ── Initialise badge counters so isset() checks are never needed ──────────────
$pending_review_count = 0;
$reg_count = 0;

// DELETE USER (HARD DELETE) — must run BEFORE fetching users
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    try {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success_message'] = "User account has been permanently deleted.";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Cannot delete user. They may have active syllabus submissions or workflow history.";
    }

    header('Location: manage_user.php');
    exit();
}

// FETCH USERS (after possible delete so the list is current)
$query = "SELECT users.*, roles.role_name, colleges.college_name
          FROM users
          LEFT JOIN roles       ON users.role_id       = roles.id
          LEFT JOIN colleges    ON users.college_id    = colleges.id
          WHERE users.is_deleted = 0";

$stmt = $conn->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll();
$unread_count = count_unread_notifications($_SESSION['user_id']);
$notifications = get_notifications($_SESSION['user_id'], 5);

if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($_SESSION['user_id']);
    header('Location: manage_user.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users — SCC Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                <a href="admin_dashboard.php"
                    class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active-nav-link' : 'hover-effect' ?>">Dashboard</a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
                <a href="syllabus_review.php"
                    class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'syllabus_review.php' ? 'active-nav-link' : 'hover-effect' ?>">
                    Syllabus Review
                    <?php if ($pending_review_count > 0): ?>
                        <span class="badge bg-danger ms-1"><?= (int) $pending_review_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="upload_syllabus.php"
                    class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'upload_syllabus.php' ? 'active-nav-link' : 'hover-effect' ?>">Upload
                    Syllabus</a>
                <a href="manage_courses.php"
                    class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'manage_courses.php' ? 'active-nav-link' : 'hover-effect' ?>">Manage Courses</a>
                <a href="my_submissions.php"
                    class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'my_submissions.php' ? 'active-nav-link' : 'hover-effect' ?>">My
                    Submissions</a>
                <a href="shared_syllabus.php"
                    class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'shared_syllabus.php' ? 'active-nav-link' : 'hover-effect' ?>">Shared
                    Syllabus</a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">USER MANAGEMENT</div>
                <a href="registration_requests.php"
                    class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'registration_requests.php' ? 'active-nav-link' : 'hover-effect' ?>">
                    Registration Requests
                    <?php if ($reg_count > 0): ?>
                        <span class="badge bg-danger ms-1"><?= (int) $reg_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="manage_user.php"
                    class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'manage_user.php' ? 'active-nav-link' : 'hover-effect' ?>">Manage
                    Users</a>
                <a href="add_user.php"
                    class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'add_user.php' ? 'active-nav-link' : 'hover-effect' ?>">Add
                    User</a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
                <a href="profile.php"
                    class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active-nav-link' : 'hover-effect' ?>">Profile</a>
                <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5 logout-link">Logout</a>
            </nav>
        </div>

        <div class="main-content flex-grow-1 p-5" style="margin-left: 260px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-orange font-serif fw-bold">Manage Users</h2>
                <div class="d-flex align-items-center gap-3">
                    <?php if ($is_dean): ?>
                        <a href="transfer_dean_role.php" class="btn btn-outline-warning rounded-pill px-4 me-2 shadow-sm">
                            <i class="bi bi-arrow-left-right me-2"></i> Transfer Dean Role
                        </a>
                    <?php endif; ?>
                    <a href="add_user.php" class="btn btn-orange rounded-pill px-4 shadow-sm">
                        <i class="bi bi-person-plus me-2"></i> Add New User
                    </a>

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
                                                <span
                                                    class="<?= $color['text'] ?>"><?= htmlspecialchars($n['message']) ?></span>
                                            </p>
                                            <span class="text-muted"
                                                style="font-size:.7rem;"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; endif; ?>
                            <li class="dropdown-menu-sticky-footer">
                                <a href="notifications.php"
                                    class="d-block text-center text-orange text-decoration-none small fw-bold py-2">View
                                    all notifications</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        <div class="scc-card animate-in">
            <div class="card-header border-0 bg-transparent p-4 pb-0">
                <h6 class="fw-bold mb-0" style="color:var(--text)">User <span
                        style="color:var(--primary)">Directory</span></h6>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="scc-table">
                        <thead>
                            <tr>
                                <th style="width: 50px">#</th>
                                <th>NAME</th>
                                <th>EMAIL</th>
                                <th>ROLE</th>
                                <th>STATUS</th>
                                <th>COLLEGE</th>
                                <th class="text-center">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td class="small text-muted"><?= (int) $u['id'] ?></td>
                                    <td>
                                        <div class="fw-bold small" style="color:var(--text)">
                                            <?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>
                                        </div>
                                    </td>
                                    <td class="small" style="color:var(--text-secondary)">
                                        <?= htmlspecialchars($u['email']) ?></td>
                                    <td>
                                        <span class="badge rounded-pill px-3 py-1"
                                            style="font-size:0.7rem; background: <?= $u['role_name'] == 'dean' ? 'var(--primary-light)' : ($u['role_name'] == 'vpaa' ? 'var(--success-light)' : 'var(--bg-card)') ?>; color: <?= $u['role_name'] == 'dean' ? 'var(--primary)' : ($u['role_name'] == 'vpaa' ? 'var(--success)' : 'var(--text-secondary)') ?>; border: 1px solid <?= $u['role_name'] == 'dean' ? 'var(--primary-light)' : ($u['role_name'] == 'vpaa' ? 'var(--success-light)' : 'var(--border)') ?> !important">
                                            <?= htmlspecialchars(strtoupper($u['role_name'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($u['email_verified']): ?>
                                            <span class="badge bg-success-light text-success rounded-pill px-2 py-1" style="font-size:0.65rem">
                                                <i class="bi bi-check-circle-fill me-1"></i> Verified
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-light text-warning rounded-pill px-2 py-1" style="font-size:0.65rem">
                                                <i class="bi bi-exclamation-circle-fill me-1"></i> Unverified
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small" style="color:var(--text-secondary)">
                                        <?= htmlspecialchars($u['college_name'] ?? 'Unassigned') ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <a href="edit_user.php?id=<?= (int) $u['id'] ?>"
                                                class="btn btn-sm btn-light border" title="Edit User"><i
                                                    class="bi bi-pencil"></i></a>
                                            <button class="btn btn-sm btn-light border text-danger" title="Delete User"
                                                onclick="confirmDelete(<?= (int) $u['id'] ?>, '<?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>')"><i
                                                    class="bi bi-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/common.js"></script>
    <script>
        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('open'); document.getElementById('sidebarOverlay').classList.toggle('active'); }
        function confirmDelete(userId, userName) {
            Swal.fire({
                title: 'Delete User?',
                html: `Are you sure you want to remove <strong>${userName}</strong>? This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `?delete=${userId}`;
                }
            });
        }
    </script>
</body>

</html>