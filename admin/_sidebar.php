<?php
/**
 * Admin/Dean sidebar include.
 * Usage: Set $active_page before including.
 * Required vars: $username, $pending_review_count, $reg_count
 */
$_username = $username ?? $_SESSION['username'] ?? 'Dean';
$_pending = $pending_review_count ?? 0;
$_reg = $reg_count ?? 0;
?>
<script src="../js/theme.js"></script>
<button class="sidebar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<nav class="scc-sidebar" id="sidebar">
        <div class="sidebar-brand">
                <img src="../css/logo.png" alt="CCS Logo" class="mb-2">
                <h6 class="fw-bold mb-0" style="color:var(--primary);font-family:var(--font-serif)">Dean's Panel</h6>
                <small style="color:rgba(255,255,255,0.5);font-size:0.72rem"><?= htmlspecialchars($_username) ?></small>
        </div>
        <div class="sidebar-section">OVERVIEW</div>
        <a href="admin_dashboard.php" class="nav-item <?= ($active_page ?? '') === 'dashboard' ? 'active' : '' ?>"><i
                        class="bi bi-grid-1x2 me-2"></i> Dashboard</a>

        <div class="sidebar-section">SYLLABUS MANAGEMENT</div>
        <a href="syllabus_review.php" class="nav-item <?= ($active_page ?? '') === 'review' ? 'active' : '' ?>"><i
                        class="bi bi-clipboard-check me-2"></i> Syllabus Review
                <?php if ($_pending > 0): ?><span class="badge bg-danger ms-auto"><?= $_pending ?></span><?php endif; ?>
        </a>
        <a href="upload_syllabus.php" class="nav-item <?= ($active_page ?? '') === 'upload' ? 'active' : '' ?>"><i
                        class="bi bi-cloud-upload me-2"></i> Upload Syllabus</a>
        <a href="manage_courses.php" class="nav-item <?= ($active_page ?? '') === 'courses' ? 'active' : '' ?>"><i
                        class="bi bi-book me-2"></i> Manage Courses</a>
        <a href="my_submissions.php" class="nav-item <?= ($active_page ?? '') === 'submissions' ? 'active' : '' ?>"><i
                        class="bi bi-folder2-open me-2"></i> My Submissions</a>
        <a href="shared_syllabus.php" class="nav-item <?= ($active_page ?? '') === 'shared' ? 'active' : '' ?>"><i
                        class="bi bi-share me-2"></i> Shared Syllabus</a>

        <div class="sidebar-section">USER MANAGEMENT</div>
        <a href="registration_requests.php"
                class="nav-item <?= ($active_page ?? '') === 'registration' ? 'active' : '' ?>"><i
                        class="bi bi-person-check me-2"></i> Registration Requests
                <?php if ($_reg > 0): ?><span class="badge bg-danger ms-auto"><?= $_reg ?></span><?php endif; ?>
        </a>
        <a href="manage_user.php" class="nav-item <?= ($active_page ?? '') === 'users' ? 'active' : '' ?>"><i
                        class="bi bi-people me-2"></i> Manage Users</a>
        <a href="add_user.php" class="nav-item <?= ($active_page ?? '') === 'add_user' ? 'active' : '' ?>"><i
                        class="bi bi-person-plus me-2"></i> Add User</a>

        <div class="sidebar-section">SYSTEM</div>
        <a href="profile.php" class="nav-item <?= ($active_page ?? '') === 'profile' ? 'active' : '' ?>"><i
                        class="bi bi-person me-2"></i> Profile</a>
        <a href="notifications.php" class="nav-item <?= ($active_page ?? '') === 'notifications' ? 'active' : '' ?>"><i
                        class="bi bi-bell me-2"></i> Notifications</a>
        <a href="../logout.php" class="nav-item logout-link"><i class="bi bi-box-arrow-left me-2"></i> Logout</a>
</nav>