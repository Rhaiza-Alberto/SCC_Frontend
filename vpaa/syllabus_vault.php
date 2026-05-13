<?php
/**
 * vpaa/syllabus_vault.php
 * Accreditation vault — all fully approved syllabi, DB-driven.
 */
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

ensure_role_in_session();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'VPAA';
$role_display = 'VPAA Institutional Hub';

if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: syllabus_vault.php');
    exit();
}

$search = trim($_GET['search'] ?? '');
$college_filter = isset($_GET['college_filter']) ? (int) $_GET['college_filter'] : null;

$conn = get_db();
$params = [];
$sql = "
    SELECT s.*,
           COALESCE(NULLIF(s.course_code,''),  c.course_code)  AS course_code,
           COALESCE(NULLIF(s.course_title,''), c.course_title) AS course_title,
           u.first_name, u.last_name, u.email AS uploader_email,
           r.role_name AS uploader_role,
           col.college_name
    FROM syllabus s
    LEFT JOIN courses c  ON s.course_id   = c.id
    LEFT JOIN users u    ON s.uploaded_by = u.id
    LEFT JOIN roles r    ON u.role_id     = r.id
    LEFT JOIN colleges col ON u.college_id = col.id
    WHERE s.status = 'Approved'
";
if ($college_filter) {
    $sql .= " AND u.college_id = ?";
    $params[] = $college_filter;
}
if ($search !== '') {
    $sql .= " AND (COALESCE(NULLIF(s.course_code,''), c.course_code) LIKE ?
              OR COALESCE(NULLIF(s.course_title,''), c.course_title) LIKE ?
              OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $like = '%' . $search . '%';
    $params = array_merge($params, [$like, $like, $like, $like]);
}
$sql .= " ORDER BY s.submitted_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$vault_syllabi = $stmt->fetchAll(PDO::FETCH_ASSOC);
$colleges = get_colleges();

$vpaa_pending_count = (int) $conn->query("
    SELECT COUNT(DISTINCT sw.syllabus_id) FROM syllabus_workflow sw
    JOIN roles r ON sw.role_id = r.id WHERE r.role_name='vpaa' AND sw.action='Pending'
")->fetchColumn();

$unread_count = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Syllabus Vault — SCC Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php $active_page = 'vault'; include '_sidebar.php'; ?>

    <main class="scc-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color:var(--text)">Accreditation <span style="color:var(--primary)">Vault</span></h4>
                <p style="font-size:0.85rem;color:var(--text-secondary);margin:0"><?= count($vault_syllabi) ?> validated syllabus records available</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <div class="position-relative" style="cursor:pointer" data-bs-toggle="dropdown">
                        <i class="bi bi-bell fs-5" style="color:var(--text)"></i>
                        <?php if ($unread_count > 0): ?><span class="notif-badge"><?= $unread_count > 9 ? '9+' : $unread_count ?></span><?php endif; ?>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="width:340px;max-height:420px;overflow-y:auto;border-radius:var(--radius-md);background:var(--bg-card)">
                        <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom sticky-top" style="background:var(--bg-card)">
                            <strong style="font-size:0.9rem;color:var(--text)">Notifications</strong>
                            <?php if ($unread_count > 0): ?><a href="?mark_read=1" class="text-decoration-none small" style="color:var(--primary)">Mark all read</a><?php endif; ?>
                        </li>
                        <?php if (empty($notifications)): ?>
                            <li class="px-3 py-4 text-center" style="color:var(--text-muted)"><i class="bi bi-bell-slash fs-4 d-block mb-2 opacity-50"></i><span class="small">No notifications</span></li>
                        <?php else: foreach ($notifications as $n): $color = get_notification_color($n['message']); ?>
                            <li class="border-bottom" style="<?= !$n['is_read'] ? 'background:var(--primary-light)' : '' ?>">
                                <a href="notifications.php?notif_id=<?= $n['id'] ?>" class="text-decoration-none d-block px-3 py-2">
                                    <p class="mb-0 small" style="color:var(--text)"><span class="<?= $color['text'] ?> fw-bold me-1"><?= $color['icon'] ?></span><?= htmlspecialchars($n['message']) ?></p>
                                    <span style="font-size:.7rem;color:var(--text-muted)"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></span>
                                </a>
                            </li>
                        <?php endforeach; endif; ?>
                        <li style="background:var(--bg-card);border-top:1px solid var(--border)"><a href="notifications.php" class="d-block text-center text-decoration-none small fw-bold py-2" style="color:var(--primary)">View all notifications</a></li>
                    </ul>
                </div>
            </div>
        </div>

            <!-- Search / Filter -->
            <div class="scc-card p-4 mb-4 animate-in">
                <h6 class="fw-bold mb-3" style="color:var(--text)">Search <span style="color:var(--primary)">Repository</span></h6>
                <form method="GET" action="syllabus_vault.php">
                    <div class="row g-3">
                        <div class="col-md-9">
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0" style="color:var(--text-muted)"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search by course code, title, or instructor name..." value="<?= htmlspecialchars($search) ?>" style="padding:0.75rem;">
                            </div>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100 fw-bold">Search Vault</button>
                            <?php if ($search): ?>
                                <a href="syllabus_vault.php" class="btn btn-outline-secondary d-flex align-items-center justify-content-center" title="Clear Search" style="width:50px;">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Vault Table -->
            <div class="scc-card animate-in">
                <div class="card-header border-0 bg-transparent p-4 pb-0">
                    <h6 class="fw-bold mb-0" style="color:var(--text)">Validated <span style="color:var(--primary)">Syllabi Repository</span></h6>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="scc-table">
                            <thead>
                                <tr>
                                    <th class="text-secondary small">COURSE</th>
                                    <th class="text-secondary small">INSTRUCTOR</th>
                                    <th class="text-secondary small">COLLEGE</th>
                                    <th class="text-secondary small">TYPE</th>
                                    <th class="text-secondary small">DATE</th>
                                    <th class="text-secondary small text-center">FILE</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($vault_syllabi)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bi bi-safe2 fs-2 opacity-25 d-block mb-2"></i>
                                            No approved syllabi in the vault<?= $search ? ' for "' . htmlspecialchars($search) . '"' : '' ?>
                                        </td>
                                    </tr>
                                <?php else:
                                    foreach ($vault_syllabi as $i => $s): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold small" style="color:var(--text)"><?= htmlspecialchars($s['course_code']) ?></div>
                                                <div style="font-size:.7rem;color:var(--text-secondary)"><?= htmlspecialchars($s['course_title']) ?></div>
                                            </td>
                                            <td>
                                                <div class="fw-bold small" style="color:var(--text)"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></div>
                                                <div style="font-size:.7rem;color:var(--text-secondary)"><?= htmlspecialchars($s['uploader_email']) ?></div>
                                            </td>
                                            <td class="small" style="color:var(--text-secondary)"><?= htmlspecialchars($s['college_name'] ?? '—') ?></td>
                                            <td class="small" style="color:var(--text-secondary)"><?= htmlspecialchars($s['subject_type'] ?? '—') ?></td>
                                            <td class="small" style="color:var(--text-secondary)"><?= date('M d, Y', strtotime($s['submitted_at'])) ?></td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($s['file_path'])) ?>" target="_blank" class="btn btn-sm btn-light border" title="Preview"><i class="bi bi-eye"></i></a>
                                                    <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($s['file_path'])) ?>&download=1" class="btn btn-sm btn-light border text-primary" title="Download"><i class="bi bi-download"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/common.js"></script>
    <script>
    function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('active');}
    </script>
</body>

</html>