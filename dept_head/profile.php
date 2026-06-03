 <?php
/**
 * dept_head/profile.php
 * Department Head profile — all data from DB.
 */
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

restrict_to_role('department_head');

$user_id      = $_SESSION['user_id'];
$username     = $_SESSION['username'] ?? 'User';
$role_display = 'Dept Head Panel';

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
$unread_count  = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - SCC-CCS Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/common.js"></script>
    <style>
        .text-orange { color: #ff8800 !important; }
        .notif-dot { position:absolute;top:2px;right:2px;width:10px;height:10px;
                     background:#dc3545;border-radius:50%;border:2px solid #fff; }
    </style>
</head>
<body class="bg-light" data-toast-error="<?= $_SESSION['error_message'] ?? '' ?>" data-toast-success="<?= $_SESSION['success_message'] ?? '' ?>">
    <?php unset($_SESSION['success_message'], $_SESSION['error_message']); ?>
    <?php $active_page = 'profile';
    include '_sidebar.php'; ?>

    <main class="scc-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color:var(--text)"><?= $edit_mode ? 'Edit' : 'My' ?> <span
                        style="color:var(--primary)">Profile</span></h4>
                <p style="font-size:0.85rem;color:var(--text-secondary);margin:0">Manage your account information and preferences</p>
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
            </div>
        </div>

        <div class="scc-card animate-in" style="max-width:800px; margin: 0 auto;">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-5">
                    <div class="profile-avatar-large mx-auto mb-3"
                        style="width:100px;height:100px;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;border-radius:50%;font-size:2.5rem;font-weight:bold;border:4px solid var(--bg-card);box-shadow:var(--shadow-sm)">
                        <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                    </div>
                    <h5 class="fw-bold mb-0" style="color:var(--text)">
                        <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h5>
                    <p class="text-muted small"><?= htmlspecialchars($user['email']) ?></p>
                </div>

                <form action="process_profile.php" method="POST" id="profileForm" novalidate data-scc-validate>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small" style="color:var(--text)">First Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="first_name"
                                value="<?= htmlspecialchars($user['first_name']) ?>" <?= !$edit_mode ? 'readonly' : 'required' ?>
                                style="border-radius:var(--radius-sm); border:1px solid var(--border); padding:0.75rem;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small" style="color:var(--text)">Middle Name</label>
                            <input type="text" class="form-control" name="middle_name"
                                value="<?= htmlspecialchars($user['middle_name'] ?? '') ?>" <?= !$edit_mode ? 'readonly' : '' ?>
                                style="border-radius:var(--radius-sm); border:1px solid var(--border); padding:0.75rem;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small" style="color:var(--text)">Last Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="last_name"
                                value="<?= htmlspecialchars($user['last_name']) ?>" <?= !$edit_mode ? 'readonly' : 'required' ?>
                                style="border-radius:var(--radius-sm); border:1px solid var(--border); padding:0.75rem;">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small" style="color:var(--text)">Birthdate <span
                                    class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="birthdate"
                                value="<?= htmlspecialchars($user['birthdate'] ?? '') ?>" <?= !$edit_mode ? 'readonly' : 'required' ?>
                                max="<?= date('Y-m-d') ?>"
                                style="border-radius:var(--radius-sm); border:1px solid var(--border); padding:0.75rem;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small" style="color:var(--text)">Sex <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" name="sex" <?= !$edit_mode ? 'disabled' : 'required' ?>
                                style="border-radius:var(--radius-sm); border:1px solid var(--border); padding:0.75rem;">
                                <option value="Male" <?= ($user['sex'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= ($user['sex'] ?? '') === 'Female' ? 'selected' : '' ?>>Female
                                </option>
                            </select>
                            <?php if (!$edit_mode): ?><input type="hidden" name="sex"
                                    value="<?= htmlspecialchars($user['sex'] ?? '') ?>"><?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small" style="color:var(--text)">College</label>
                        <input type="text" class="form-control" readonly
                            value="<?= htmlspecialchars($user['college_name'] ?? '—') ?>"
                            style="border-radius:var(--radius-sm); border:1px solid var(--border); padding:0.75rem; background:var(--bg-subtle)">
                    </div>

                    <div class="mb-5">
                        <label class="form-label fw-bold small" style="color:var(--text)">Email Address</label>
                        <input type="email" class="form-control" readonly
                            value="<?= htmlspecialchars($user['email']) ?>"
                            style="border-radius:var(--radius-sm); border:1px solid var(--border); padding:0.75rem; background:var(--bg-subtle)">
                    </div>

                    <div class="d-grid gap-3">
                        <?php if ($edit_mode): ?>
                            <button type="button" onclick="confirmProfileUpdate()" class="btn btn-primary-scc py-3 fw-bold shadow-sm"
                                style="border-radius:var(--radius-sm)">
                                <i class="bi bi-save me-2"></i> Save Changes
                            </button>
                            <a href="profile.php" class="btn btn-outline-scc py-3"
                                style="border-radius:var(--radius-sm)">Cancel</a>
                        <?php else: ?>
                            <a href="profile.php?edit=true" class="btn btn-primary-scc py-3 fw-bold shadow-sm"
                                style="border-radius:var(--radius-sm)">
                                <i class="bi bi-pencil me-2"></i> Edit Profile Information
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/validate.js"></script>
    <script src="../js/common.js"></script>
    <script>
        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('open'); document.getElementById('sidebarOverlay').classList.toggle('active'); }
        
        function confirmProfileUpdate() {
            Swal.fire({
                title: 'Confirm Changes',
                text: "Are you sure you want to save these changes to your profile?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: 'var(--primary)',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Save Changes'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('profileForm').submit();
                }
            });
        }
    </script>
</body>
</html>