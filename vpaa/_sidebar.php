<?php
/**
 * VPAA sidebar include.
 * Usage: Set $active_page before including.
 */
$_username = $username ?? $_SESSION['username'] ?? 'VPAA';
// VPAA pending review badge count removed as VPAA approval is no longer required
?>
<script src="../js/theme.js"></script>
<button class="sidebar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<nav class="scc-sidebar" id="sidebar">
        <div class="sidebar-brand">
                <img src="../css/logo.png" alt="CCS Logo" class="mb-2">
                <h6 class="fw-bold mb-0" style="color:var(--primary);font-family:var(--font-serif)">VPAA Institutional
                        Hub</h6>
                <small style="color:rgba(255,255,255,0.5);font-size:0.72rem"><?= htmlspecialchars($_username) ?></small>
        </div>
        <div class="sidebar-section">OVERVIEW</div>
        <a href="vpaa_dashboard.php" class="nav-item <?= ($active_page ?? '') === 'dashboard' ? 'active' : '' ?>"><i
                        class="bi bi-grid-1x2 me-2"></i> Dashboard</a>

        <div class="sidebar-section">SYLLABUS MANAGEMENT</div>
        <a href="syllabus_review.php" class="nav-item <?= ($active_page ?? '') === 'review' ? 'active' : '' ?>"><i
                        class="bi bi-clipboard-check me-2"></i> Approved Syllabi</a>

        <div class="sidebar-section">ANALYTICS</div>
        <a href="compliance_reports.php"
                class="nav-item <?= ($active_page ?? '') === 'compliance' ? 'active' : '' ?>"><i
                        class="bi bi-graph-up me-2"></i> Compliance Reports</a>
        <a href="syllabus_vault.php" class="nav-item <?= ($active_page ?? '') === 'vault' ? 'active' : '' ?>"><i
                        class="bi bi-safe2 me-2"></i> Syllabus Vault</a>

        <div class="sidebar-section">SYSTEM</div>
        <a href="profile.php" class="nav-item <?= ($active_page ?? '') === 'profile' ? 'active' : '' ?>"><i
                        class="bi bi-person me-2"></i> Profile</a>
        <a href="notifications.php" class="nav-item <?= ($active_page ?? '') === 'notifications' ? 'active' : '' ?>"><i
                        class="bi bi-bell me-2"></i> Notifications</a>
        <a href="../logout.php" class="nav-item logout-link"><i class="bi bi-box-arrow-left me-2"></i> Logout</a>
</nav>