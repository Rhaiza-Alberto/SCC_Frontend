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
    <title>Syllabus Vault - VPAA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .text-orange {
            color: #ff8800 !important;
        }

        .btn-orange {
            background-color: #ff8800 !important;
            color: #fff !important;
            border: none;
        }

        .btn-orange:hover {
            background-color: #e67a00 !important;
        }

        .notif-dot {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 10px;
            height: 10px;
            background: #dc3545;
            border-radius: 50%;
            border: 2px solid #fff;
        }
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
                <p class="text-white-50 small fw-bold mb-0" style="font-size:.75rem;"><?= htmlspecialchars($username) ?>
                </p>
            </div>
            <nav class="nav flex-column gap-2 mb-auto">
                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
                <a href="vpaa_dashboard.php" class="nav-link text-white p-3 rounded hover-effect">Dashboard</a>
                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
                <a href="syllabus_review.php" class="nav-link text-white p-3 rounded hover-effect">Syllabus Review
                    <?php if ($vpaa_pending_count > 0): ?><span
                            class="badge bg-danger ms-1"><?= $vpaa_pending_count ?></span><?php endif; ?>
                </a>
                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">ANALYTICS</div>
                <a href="compliance_reports.php" class="nav-link text-white p-3 rounded hover-effect">Compliance
                    Reports</a>
                <a href="syllabus_vault.php" class="nav-link text-white active-nav-link p-3 rounded">Syllabus Vault</a>
                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
                <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect">Profile</a>
                <a href="javascript:void(0)" class="nav-link text-white p-3 rounded hover-effect mt-5" onclick="confirmLogout()">Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-grow-1 p-5" style="margin-left:260px;">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="text-orange font-serif fw-bold mb-0">Accreditation Vault</h2>
                    <p class="text-muted small mb-0"><?= count($vault_syllabi) ?> fully approved syllabus file(s)
                        available</p>
                </div>
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
                                            <span class="<?= $color['text'] ?>"><?= htmlspecialchars($n['message']) ?></span>
                                        </p>
                                        <span class="text-muted"
                                            style="font-size:.7rem;"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></span>
                                    </a>
                                </li>
                            <?php endforeach; endif; ?>
                        <li class="dropdown-menu-sticky-footer">
                            <a href="notifications.php"
                                class="d-block text-center text-orange text-decoration-none small fw-bold py-2">View all
                                notifications</a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Search / Filter -->
            <div class="card premium-card p-4 mb-4 shadow-sm border-0">
                <h5 class="font-serif fw-bold mb-3 text-orange">Search Vault</h5>
                <form method="GET" action="syllabus_vault.php">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <input type="text" name="search" class="form-control"
                                placeholder="Course code, title, or instructor"
                                value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-4">
                            <select name="college_filter" class="form-select">
                                <option value="">All Colleges</option>
                                <?php foreach ($colleges as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= $college_filter === (int) $c['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['college_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-orange w-100">
                                <i class="bi bi-search me-1"></i>Search
                            </button>
                            <?php if ($search || $college_filter): ?>
                                <a href="syllabus_vault.php" class="btn btn-outline-secondary" title="Clear">
                                    <i class="bi bi-x"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Vault Table -->
            <div class="card premium-card p-4 border-0 shadow-sm">
                <h5 class="font-serif fw-bold mb-4 text-orange">Validated Syllabi Repository</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="text-secondary small">ID</th>
                                <th class="text-secondary small">COURSE CODE</th>
                                <th class="text-secondary small">COURSE TITLE</th>
                                <th class="text-secondary small">INSTRUCTOR</th>
                                <th class="text-secondary small">COLLEGE</th>
                                <th class="text-secondary small">TYPE</th>
                                <th class="text-secondary small">APPROVED ON</th>
                                <th class="text-secondary small text-center">FILE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($vault_syllabi)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-safe2 fs-2 opacity-25 d-block mb-2"></i>
                                        No approved syllabi in the
                                        vault<?= $search ? ' for "' . htmlspecialchars($search) . '"' : '' ?>
                                    </td>
                                </tr>
                            <?php else:
                                foreach ($vault_syllabi as $i => $s): ?>
                                    <tr>
                                        <td class="small fw-bold text-muted"><?= $s['id'] ?></td>
                                        <td><strong class="small"><?= htmlspecialchars($s['course_code']) ?></strong></td>
                                        <td class="small"><?= htmlspecialchars($s['course_title']) ?></td>
                                        <td class="small">
                                            <div class="fw-bold"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></div>
                                            <div class="text-muted" style="font-size:.7rem;">
                                                <?= htmlspecialchars($s['uploader_email']) ?></div>
                                            <div class="text-muted" style="font-size:.65rem;">
                                                (<?= ucfirst(htmlspecialchars($s['uploader_role'])) ?>)</div>
                                        </td>
                                        <td class="small"><?= htmlspecialchars($s['college_name'] ?? '—') ?></td>
                                        <td class="small"><?= htmlspecialchars($s['subject_type'] ?? '—') ?></td>
                                        <td class="small"><?= date('M d, Y', strtotime($s['submitted_at'])) ?></td>
                                        <td class="text-center">
                                            <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($s['file_path'])) ?>"
                                                target="_blank" class="btn btn-sm btn-outline-danger rounded-pill me-1 px-2">
                                                <i class="bi bi-eye me-1"></i>Preview
                                            </a>
                                            <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($s['file_path'])) ?>&download=1"
                                                class="btn btn-sm btn-outline-secondary rounded-pill px-2">
                                                <i class="bi bi-download me-1"></i>Download
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmLogout() {
            Swal.fire({
                title: 'Sign Out?',
                text: "Are you sure you want to end your current session?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff8800',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Logout',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../logout.php';
                }
            });
        }
    </script>
</body>

</html>