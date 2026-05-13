<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

restrict_to_role('dean');

$username = $_SESSION['username'] ?? 'Dean';
$email = $_SESSION['email'] ?? '';
$role_display = "Dean's Panel";

// Fetch user profile from database
$user_id = $_SESSION['user_id'];

$conn = get_db();
$stmt = $conn->prepare("
    SELECT 
        u.first_name,
        u.middle_name,
        u.last_name,
        u.birthdate,
        u.sex,
        u.email,
        c.college_name
    FROM users u
    LEFT JOIN colleges c ON u.college_id = c.id
    WHERE u.id = ? AND u.is_deleted = 0
");
$stmt->execute([$user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$profile = [
    'first_name' => $row['first_name'] ?? '',
    'middle_name' => $row['middle_name'] ?? '',
    'last_name' => $row['last_name'] ?? '',
    'birthdate' => $row['birthdate'] ?? '',
    'sex' => $row['sex'] ?? '',
    'college' => $row['college_name'] ?? '',
    'email' => $row['email'] ?? $email,
];

$edit_mode = isset($_GET['edit']) && $_GET['edit'] == 'true';

$user_id = $_SESSION['user_id'];
$notifications = get_notifications($user_id, 10);
$unread_count = count_unread_notifications($user_id);

// Handle "mark all read" action
if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: profile.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — SCC Syllabus Portal</title>
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
                <p class="text-white-50 small fw-bold mb-0" style="font-size:.75rem;"><?= htmlspecialchars($username) ?></p>
            </div>
            <nav class="nav flex-column gap-2 mb-auto">
                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
                <a href="admin_dashboard.php" class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active-nav-link' : 'hover-effect' ?>">Dashboard</a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
                <a href="syllabus_review.php" class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'syllabus_review.php' ? 'active-nav-link' : 'hover-effect' ?>">
            Syllabus Review
                    <?php if (isset($pending_review_count) && $pending_review_count > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $pending_review_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="upload_syllabus.php" class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'upload_syllabus.php' ? 'active-nav-link' : 'hover-effect' ?>">Upload Syllabus</a>
                <a href="my_submissions.php" class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'my_submissions.php' ? 'active-nav-link' : 'hover-effect' ?>">My Submissions</a>
                <a href="shared_syllabus.php" class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'shared_syllabus.php' ? 'active-nav-link' : 'hover-effect' ?>">Shared Syllabus</a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">USER MANAGEMENT</div>
                <a href="registration_requests.php" class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'registration_requests.php' ? 'active-nav-link' : 'hover-effect' ?>">
            Registration Requests
                    <?php if (isset($reg_count) && $reg_count > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $reg_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="manage_user.php" class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'manage_user.php' ? 'active-nav-link' : 'hover-effect' ?>">Manage Users</a>
                <a href="add_user.php" class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'add_user.php' ? 'active-nav-link' : 'hover-effect' ?>">Add User</a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
                <a href="profile.php" class="nav-link text-white p-3 rounded <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active-nav-link' : 'hover-effect' ?>">Profile</a>
                <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5 logout-link">Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-grow-1 p-5" style="margin-left: 260px;">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <h3 class="text-orange font-serif fw-bold mb-0">
                    <?php echo $edit_mode ? 'Edit my Profile' : 'My Profile'; ?>
                </h3>
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

        <div class="scc-card animate-in" style="max-width: 800px; margin: 0 auto;">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-5">
                    <div class="profile-avatar-large mx-auto mb-3"
                        style="width:100px;height:100px;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;border-radius:50%;font-size:2.5rem;font-weight:bold;border:4px solid var(--bg-card);box-shadow:var(--shadow-sm)">
                        <?= strtoupper(substr($profile['first_name'], 0, 1) . substr($profile['last_name'], 0, 1)) ?>
                    </div>
                    <h5 class="fw-bold mb-1" style="color:var(--text)">
                        <?= htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']) ?></h5>
                    <p class="small text-muted mb-0">Administrative Access — Dean</p>
                </div>

                <form action="process_profile.php" method="POST" id="profileForm" novalidate data-scc-validate>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small" style="color:var(--text)">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="first_name"
                                value="<?= htmlspecialchars($profile['first_name']) ?>" <?= !$edit_mode ? 'readonly' : 'required' ?>
                                style="border-radius:var(--radius-sm); border:1px solid var(--border); padding:0.75rem;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small" style="color:var(--text)">Middle Name</label>
                            <input type="text" class="form-control" name="middle_name"
                                value="<?= htmlspecialchars($profile['middle_name']) ?>" <?= !$edit_mode ? 'readonly' : '' ?>
                                style="border-radius:var(--radius-sm); border:1px solid var(--border); padding:0.75rem;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small" style="color:var(--text)">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="last_name"
                                value="<?= htmlspecialchars($profile['last_name']) ?>" <?= !$edit_mode ? 'readonly' : 'required' ?>
                                style="border-radius:var(--radius-sm); border:1px solid var(--border); padding:0.75rem;">
                        </div>

                        <div class="col-md-6 mt-4">
                            <label class="form-label fw-bold small" style="color:var(--text)">Birthdate <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="birthdate"
                                value="<?= htmlspecialchars($profile['birthdate']) ?>" <?= !$edit_mode ? 'readonly' : 'required' ?>
                                max="<?= date('Y-m-d') ?>"
                                style="border-radius:var(--radius-sm); border:1px solid var(--border); padding:0.75rem;">
                        </div>
                        <div class="col-md-6 mt-4">
                            <label class="form-label fw-bold small" style="color:var(--text)">Sex <span class="text-danger">*</span></label>
                            <select class="form-select" name="sex" <?= !$edit_mode ? 'disabled' : 'required' ?>
                                style="border-radius:var(--radius-sm); border:1px solid var(--border); padding:0.75rem;">
                                <option value="Male" <?= $profile['sex'] == 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $profile['sex'] == 'Female' ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>

                        <div class="col-12 mt-4">
                            <label class="form-label fw-bold small" style="color:var(--text)">College / Institution</label>
                            <input type="text" class="form-control" readonly
                                value="<?= htmlspecialchars($profile['college']) ?>" 
                                style="border-radius:var(--radius-sm); border:1px solid var(--border); padding:0.75rem; background:var(--bg-subtle)">
                        </div>

                        <div class="col-12 mt-3">
                            <label class="form-label fw-bold small" style="color:var(--text)">Email Address</label>
                            <input type="email" class="form-control" readonly
                                value="<?= htmlspecialchars($profile['email']) ?>" 
                                style="border-radius:var(--radius-sm); border:1px solid var(--border); padding:0.75rem; background:var(--bg-subtle)">
                            <p class="small text-muted mt-2 mb-0"><i class="bi bi-info-circle me-1"></i>Contact IT to
                                change your official institutional email.</p>
                        </div>

                        <div class="col-12 mt-5">
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
                        </div>
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