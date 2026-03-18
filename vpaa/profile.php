<?php
/**
 * vpaa/profile.php
 * VPAA profile — data from DB, uses process_profile.php handler.
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
$username     = $_SESSION['username'] ?? 'VPAA';
$role_display = 'VPAA Institutional Hub';

if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: profile.php');
    exit();
}

$user = get_user_by_id($user_id);
if (!$user) {
    session_destroy();
    header('Location: ../login.php');
    exit();
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message   = $_SESSION['error_message']   ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$edit_mode     = isset($_GET['edit']) && $_GET['edit'] === 'true';
$vpaa_pending_count = (int) get_db()->query("
    SELECT COUNT(DISTINCT sw.syllabus_id) FROM syllabus_workflow sw
    JOIN roles r ON sw.role_id = r.id WHERE r.role_name='vpaa' AND sw.action='Pending'
")->fetchColumn();

$unread_count  = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - VPAA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
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
            <a href="vpaa_dashboard.php"     class="nav-link text-white p-3 rounded hover-effect">Dashboard</a>
            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
            <a href="syllabus_review.php"    class="nav-link text-white p-3 rounded hover-effect">Syllabus Review
                <?php if ($vpaa_pending_count > 0): ?><span class="badge bg-danger ms-1"><?= $vpaa_pending_count ?></span><?php endif; ?>
            </a>
            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">ANALYTICS</div>
            <a href="compliance_reports.php" class="nav-link text-white p-3 rounded hover-effect">Compliance Reports</a>
            <a href="syllabus_vault.php"     class="nav-link text-white p-3 rounded hover-effect">Syllabus Vault</a>
            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
            <a href="profile.php"            class="nav-link text-white active-nav-link p-3 rounded">Profile</a>
            <a href="../logout.php"          class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 p-5" style="margin-left:260px;">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <h3 class="text-orange font-serif fw-bold mb-0">
                <?= $edit_mode ? 'Edit My Profile' : 'My Profile' ?>
            </h3>
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

        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4">
                <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card premium-card shadow-sm p-5 bg-white mx-auto" style="max-width:800px;">
            <p class="text-center text-muted small mb-4">
                <?= $edit_mode ? 'Update your personal information below.' : 'Your profile information.' ?>
            </p>
            <!-- Posts to the same process_profile.php used by dept_head -->
            <form action="process_profile.php" method="POST">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="first_name"
                               value="<?= htmlspecialchars($user['first_name']) ?>"
                               <?= !$edit_mode ? 'readonly' : 'required' ?>>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Middle Name</label>
                        <input type="text" class="form-control" name="middle_name"
                               value="<?= htmlspecialchars($user['middle_name'] ?? '') ?>"
                               <?= !$edit_mode ? 'readonly' : '' ?>>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Last Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="last_name"
                               value="<?= htmlspecialchars($user['last_name']) ?>"
                               <?= !$edit_mode ? 'readonly' : 'required' ?>>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Birthdate <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="birthdate"
                               value="<?= htmlspecialchars($user['birthdate'] ?? '') ?>"
                               <?= !$edit_mode ? 'readonly' : 'required' ?>>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Sex <span class="text-danger">*</span></label>
                        <select class="form-select" name="sex" <?= !$edit_mode ? 'disabled' : 'required' ?>>
                            <option value="Male"   <?= ($user['sex'] ?? '') === 'Male'   ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($user['sex'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                        </select>
                        <?php if (!$edit_mode): ?>
                            <input type="hidden" name="sex" value="<?= htmlspecialchars($user['sex'] ?? '') ?>">
                        <?php endif; ?>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">College</label>
                        <input type="text" class="form-control" readonly
                               value="<?= htmlspecialchars($user['college_name'] ?? '—') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Office / Department</label>
                        <input type="text" class="form-control" readonly
                               value="<?= htmlspecialchars($user['department_name'] ?? 'Office of the VPAA') ?>">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold small">Email</label>
                    <input type="email" class="form-control" readonly value="<?= htmlspecialchars($user['email']) ?>">
                </div>
                <div class="d-grid gap-2">
                    <?php if ($edit_mode): ?>
                        <button type="submit" class="btn btn-login btn-lg fw-bold">
                            <i class="bi bi-save me-2"></i>Save Changes
                        </button>
                        <a href="profile.php" class="btn btn-outline-secondary btn-lg">Cancel</a>
                    <?php else: ?>
                        <a href="profile.php?edit=true" class="btn btn-login btn-lg fw-bold">
                            <i class="bi bi-pencil me-2"></i>Edit My Profile
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>