<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

ensure_role_in_session();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Dean / Admin';

// Handle mark-all-read
if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: notifications.php');
    exit();
}

// Handle single notification click (mark read + redirect)
if (isset($_GET['notif_id'])) {
    $notif_id = (int) $_GET['notif_id'];
    $user_id = $_SESSION['user_id'];
    
    // Mark as read
    mark_single_notification_read($notif_id, $user_id);
    
    // Determine redirect based on message content
    $conn = get_db();
    $stmt = $conn->prepare("SELECT message FROM notifications WHERE id = ?");
    $stmt->execute([$notif_id]);
    $notif = $stmt->fetch();

    if ($notif && strpos($notif['message'], 'registration') !== false) {
        header('Location: registration_requests.php');
    } else {
        header('Location: syllabus_review.php');
    }
    exit();
}

// Fetch ALL notifications (no limit)
$conn = get_db();
$stmt = $conn->prepare("
    SELECT n.*, s.file_path
    FROM notifications n
    LEFT JOIN syllabus s ON n.syllabus_id = s.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
");
$stmt->execute([$user_id]);
$all_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unread_count = count_unread_notifications($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications — SCC Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-light">
<div class="d-flex">

    <!-- Sidebar (same as dashboard) -->
    <div class="sidebar sidebar-premium text-white p-2 min-vh-100 d-flex flex-column"
         style="width:260px; position:fixed; z-index:1100;">
        <div class="text-center mb-3 mt-2">
            <img src="../css/logo.png" alt="CCS Logo" class="rounded-circle mb-2"
                 style="width:80px;height:80px;border:2px solid rgba(255,136,0,.5);padding:3px;">
            <h5 class="font-serif fw-bold text-orange mb-0">Dean's Panel</h5>
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
            <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 p-5" style="margin-left:260px;">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color:var(--text)">All <span style="color:var(--primary)">Notifications</span></h4>
                <p style="font-size:0.85rem;color:var(--text-secondary);margin:0"><?= count($all_notifications) ?> total &middot; <?= $unread_count ?> unread</p>
            </div>
            <div class="d-flex align-items-center gap-3" id="navbarActions">
                <?php if ($unread_count > 0): ?>
                    <a href="?mark_read=1" class="btn btn-primary btn-sm px-3 rounded-pill fw-bold shadow-sm">
                        <i class="bi bi-check2-all me-1"></i> Mark All as Read
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="scc-card p-0 overflow-hidden animate-in shadow-sm">
            <?php if (empty($all_notifications)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-bell-slash fs-1 d-block mb-3 opacity-25"></i>
                    No notifications yet
                </div>
            <?php else: ?>
                <?php foreach ($all_notifications as $n):
                    $color = get_notification_color($n['message']);
                ?>
                    <a href="?notif_id=<?= $n['id'] ?>" class="text-decoration-none d-block border-bottom notification-item <?= !$n['is_read'] ? 'unread' : '' ?>" style="transition: all 0.2s ease;">
                        <div class="px-4 py-3" style="<?= !$n['is_read'] ? 'background:var(--primary-light)' : '' ?>">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle <?= $color['text'] ?> shadow-sm d-flex align-items-center justify-content-center flex-shrink-0" style="width:50px;height:50px;background-color:var(--bg-card);">
                                    <span class="fs-4"><?= $color['icon'] ?></span>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-1 small fw-bold" style="color:var(--text)">
                                        <?= htmlspecialchars($n['message']) ?>
                                    </p>
                                    <span style="font-size:.72rem;color:var(--text-secondary)">
                                        <i class="bi bi-clock me-1"></i>
                                        <?= date('M d, Y h:i A', strtotime($n['created_at'])) ?>
                                    </span>
                                </div>
                                <?php if (!$n['is_read']): ?>
                                    <span class="badge bg-primary rounded-pill px-3 py-1" style="font-size:.6rem; letter-spacing:0.5px">NEW</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="mt-4">
            <a href="admin_dashboard.php" class="btn btn-light border fw-bold text-secondary rounded-pill px-4">
                <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
            </a>
        </div>
    </main>

    </div>
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/common.js"></script>
    <script>
        function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('active');}
    </script>
</body>
</html>