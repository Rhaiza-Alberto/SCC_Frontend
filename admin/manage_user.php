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

<body>
    <?php $active_page = 'users';
    include '_sidebar.php'; ?>

    <main class="scc-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color:var(--text)">Manage <span
                        style="color:var(--primary)">Users</span></h4>
                <p style="font-size:0.85rem;color:var(--text-secondary);margin:0">Administrative access and account
                    management</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <div class="position-relative" style="cursor:pointer" data-bs-toggle="dropdown">
                        <i class="bi bi-bell fs-5" style="color:var(--text)"></i>
                        <?php if ($unread_count > 0): ?><span
                                class="notif-badge"><?= $unread_count > 9 ? '9+' : $unread_count ?></span><?php endif; ?>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0"
                        style="width:340px;max-height:420px;overflow-y:auto;border-radius:var(--radius-md);background:var(--bg-card)">
                        <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom sticky-top"
                            style="background:var(--bg-card)">
                            <strong style="font-size:0.9rem;color:var(--text)">Notifications</strong>
                            <?php if ($unread_count > 0): ?><a href="?mark_read=1" class="text-decoration-none small"
                                    style="color:var(--primary)">Mark all read</a><?php endif; ?>
                        </li>
                        <?php if (empty($notifications)): ?>
                            <li class="px-3 py-4 text-center" style="color:var(--text-muted)"><i
                                    class="bi bi-bell-slash fs-4 d-block mb-2 opacity-50"></i><span class="small">No
                                    notifications</span></li>
                        <?php else:
                            foreach ($notifications as $n):
                                $color = get_notification_color($n['message']); ?>
                                <li class="border-bottom"
                                    style="<?= !$n['is_read'] ? 'background:var(--primary-light)' : '' ?>">
                                    <a href="notifications.php?notif_id=<?= $n['id'] ?>"
                                        class="text-decoration-none d-block px-3 py-2">
                                        <p class="mb-0 small" style="color:var(--text)"><span
                                                class="<?= $color['text'] ?> fw-bold me-1"><?= $color['icon'] ?></span><?= htmlspecialchars($n['message']) ?>
                                        </p>
                                        <span
                                            style="font-size:.7rem;color:var(--text-muted)"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></span>
                                    </a>
                                </li>
                            <?php endforeach; endif; ?>
                        <li style="background:var(--bg-card);border-top:1px solid var(--border)"><a
                                href="notifications.php"
                                class="d-block text-center text-decoration-none small fw-bold py-2"
                                style="color:var(--primary)">View all notifications</a></li>
                    </ul>
                </div>
                <?php if ($is_dean): ?>
                    <a href="transfer_dean_role.php" class="btn btn-light border rounded-pill px-4 fw-bold">
                        <i class="bi bi-arrow-left-right me-1"></i> Transfer Dean Role
                    </a>
                <?php endif; ?>
                <a href="add_user.php" class="btn btn-primary rounded-pill px-4 fw-bold">
                    <i class="bi bi-person-plus me-1"></i> Add User
                </a>
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