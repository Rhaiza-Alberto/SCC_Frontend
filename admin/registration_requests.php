<?php
/**
 * admin/registration_requests.php
 * Dean approves/rejects faculty registration requests (formerly dept_head's role).
 */
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

ensure_role_in_session();

$user_id      = $_SESSION['user_id'];
$username     = $_SESSION['username'] ?? 'Dean / Admin';
$role_display = "Dean's Panel";

if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: registration_requests.php');
    exit();
}

$conn = get_db();

// ── Handle Approve / Reject ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'])) {
    $target_id = (int) $_POST['user_id'];
    $action    = $_POST['action'];

    if ($action === 'approve') {
        $conn->prepare("UPDATE users SET is_approved = 1 WHERE id = ? AND is_deleted = 0")
             ->execute([$target_id]);
        notify_user($target_id,
            "Your registration has been approved by the Dean. You may now log in.", null);
    } elseif ($action === 'reject') {
        $conn->prepare("UPDATE users SET is_deleted = 1 WHERE id = ?")
             ->execute([$target_id]);
    }
    header('Location: registration_requests.php');
    exit();
}

// ── Fetch pending faculty registrations ──────────────────────────────────────
$stmt = $conn->prepare("
    SELECT u.id, u.first_name, u.middle_name, u.last_name,
           u.email, u.created_at, d.department_name
    FROM users u
    JOIN departments d ON u.department_id = d.id
    JOIN roles r       ON u.role_id        = r.id
    WHERE r.role_name   = 'faculty'
      AND u.is_approved = 0
      AND u.is_deleted  = 0
    ORDER BY u.created_at DESC
");
$stmt->execute();
$pending_registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$reg_count     = count($pending_registrations);
$unread_count  = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);

// Sidebar pending review count for badge
$pending_review_count = (int) $conn->query("
    SELECT COUNT(DISTINCT sw.syllabus_id) FROM syllabus_workflow sw
    JOIN roles r ON sw.role_id = r.id
    WHERE r.role_name = 'dean' AND sw.action = 'Pending'
")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Requests - Dean's Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .text-orange { color: #ff8800 !important; }
        .notif-dot   { position:absolute;top:2px;right:2px;width:10px;height:10px;
                       background:#dc3545;border-radius:50%;border:2px solid #fff; }
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
            <a href="admin_dashboard.php"       class="nav-link text-white p-3 rounded hover-effect">Dashboard</a>
            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
            <a href="syllabus_review.php"       class="nav-link text-white p-3 rounded hover-effect">Syllabus Review
                <?php if ($pending_review_count > 0): ?><span class="badge bg-danger ms-1"><?= $pending_review_count ?></span><?php endif; ?>
            </a>
            <a href="upload_syllabus.php"       class="nav-link text-white p-3 rounded hover-effect">Upload Syllabus</a>
            <a href="my_submissions.php"        class="nav-link text-white p-3 rounded hover-effect">My Submissions</a>
            <a href="shared_syllabus.php"       class="nav-link text-white p-3 rounded hover-effect">Shared Syllabus</a>
            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">USER MANAGEMENT</div>
            <a href="registration_requests.php" class="nav-link text-white active-nav-link p-3 rounded">Registration Requests</a>
            <a href="manage_user.php"           class="nav-link text-white p-3 rounded hover-effect">Manage Users</a>
            <a href="add_user.php"              class="nav-link text-white p-3 rounded hover-effect">Add User</a>
            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
            <a href="profile.php"               class="nav-link text-white p-3 rounded hover-effect">Profile</a>
            <a href="../logout.php"             class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 p-5" style="margin-left:260px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-orange font-serif fw-bold">Registration Requests</h2>
            <div class="d-flex align-items-center gap-3">
                <span class="badge rounded-pill px-3 py-1 shadow-sm <?= $reg_count > 0 ? 'bg-warning text-dark' : 'bg-secondary opacity-50' ?>">
                    <i class="bi bi-person-plus-fill me-1"></i><?= $reg_count ?> New
                </span>
                <div class="dropdown">
                    <div class="position-relative" style="cursor:pointer;" data-bs-toggle="dropdown">
                        <i class="bi bi-bell fs-4 text-secondary"></i>
                        <?php if ($unread_count > 0): ?><span class="notif-dot"></span><?php endif; ?>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow" style="width:320px;max-height:400px;overflow-y:auto;">
                        <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom">
                            <strong>Notifications</strong>
                            <?php if ($unread_count > 0): ?>
                                <a href="?mark_read=1" class="text-decoration-none small text-orange">Mark all read</a>
                            <?php endif; ?>
                        </li>
                        <?php if (empty($notifications)): ?>
                            <li class="px-3 py-3 text-center text-muted small">No notifications</li>
                        <?php else: foreach ($notifications as $n): ?>
                            <li class="px-3 py-2 border-bottom <?= !$n['is_read'] ? 'bg-light' : '' ?>">
                                <p class="mb-0 small"><?= htmlspecialchars($n['message']) ?></p>
                                <span class="text-muted" style="font-size:.7rem;"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></span>
                            </li>
                        <?php endforeach; endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <?php if ($reg_count === 0): ?>
            <div class="alert border-0 shadow-sm mb-4 d-flex align-items-center p-3 rounded-3" style="background:rgba(220,53,69,.08);">
                <div class="bg-danger text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width:45px;height:45px;">
                    <i class="bi bi-megaphone-fill fs-5"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1 text-muted opacity-75">All Caught Up</h6>
                    <p class="mb-0 text-muted small">No pending faculty registration requests at this time.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="alert border-0 shadow-sm mb-4 d-flex align-items-center p-3 rounded-3" style="background:rgba(255,193,7,.1);">
                <div class="bg-warning text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width:45px;height:45px;">
                    <i class="bi bi-megaphone-fill fs-5"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1 text-muted opacity-75">Action Required</h6>
                    <p class="mb-0 text-muted small"><?= $reg_count ?> faculty registration(s) awaiting your approval.</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="card premium-card p-4 mb-5 shadow-sm border-0">
            <h5 class="card-title font-serif fw-bold mb-3 text-orange">Pending Faculty Registrations</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-secondary small">#</th>
                            <th class="text-secondary small">FULL NAME</th>
                            <th class="text-secondary small">EMAIL</th>
                            <th class="text-secondary small">DEPARTMENT</th>
                            <th class="text-secondary small">REGISTERED</th>
                            <th class="text-secondary small text-center">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending_registrations)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="bi bi-person-check fs-2 opacity-25 d-block mb-2"></i>
                                    No pending registration requests
                                </td>
                            </tr>
                        <?php else: foreach ($pending_registrations as $i => $reg): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="fw-bold small">
                                    <?= htmlspecialchars(trim($reg['first_name'] . ' ' . ($reg['middle_name'] ? $reg['middle_name'] . ' ' : '') . $reg['last_name'])) ?>
                                </td>
                                <td class="small"><?= htmlspecialchars($reg['email']) ?></td>
                                <td class="small"><?= htmlspecialchars($reg['department_name']) ?></td>
                                <td class="small"><?= date('M d, Y', strtotime($reg['created_at'])) ?></td>
                                <td class="text-center">
                                    <button onclick="handleAction('approve', <?= $reg['id'] ?>, '<?= htmlspecialchars($reg['first_name'] . ' ' . $reg['last_name']) ?>')"
                                            class="btn btn-sm btn-outline-success me-1 px-3 rounded-pill">
                                        <i class="bi bi-check me-1"></i>Approve
                                    </button>
                                    <button onclick="handleAction('reject', <?= $reg['id'] ?>, '<?= htmlspecialchars($reg['first_name'] . ' ' . $reg['last_name']) ?>')"
                                            class="btn btn-sm btn-outline-danger px-3 rounded-pill">
                                        <i class="bi bi-x me-1"></i>Reject
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<form id="actionForm" method="POST" action="registration_requests.php">
    <input type="hidden" name="action"  id="formAction">
    <input type="hidden" name="user_id" id="formUserId">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function handleAction(action, userId, name) {
    Swal.fire({
        title: action === 'approve' ? 'Approve Registration?' : 'Reject Registration?',
        html: `${action === 'approve' ? 'Approve' : 'Reject'} account for <strong>${name}</strong>?`,
        icon: action === 'approve' ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonColor: action === 'approve' ? '#198754' : '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: action === 'approve' ? 'Yes, Approve' : 'Yes, Reject'
    }).then(result => {
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