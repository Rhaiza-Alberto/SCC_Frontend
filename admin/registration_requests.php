<?php
/**
 * admin/registration_requests.php
 * Dean approves/rejects faculty registration requests (formerly dept_head's role).
 */
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

restrict_to_role('dean');

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Dean / Admin';
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
    $action = $_POST['action'];

    if ($action === 'approve') {
        $conn->prepare("UPDATE users SET is_approved = 1 WHERE id = ?")
            ->execute([$target_id]);
        notify_user(
            $target_id,
            "Your registration has been approved by the Dean. You may now log in.",
            null
        );
    } elseif ($action === 'reject') {
        $conn->prepare("DELETE FROM users WHERE id = ?")
            ->execute([$target_id]);
    }
    header('Location: registration_requests.php');
    exit();
}

// ── Fetch pending faculty registrations ──────────────────────────────────────
$stmt = $conn->prepare("
    SELECT u.id, u.first_name, u.middle_name, u.last_name,
           u.email, u.created_at, u.email_verified, col.college_name
    FROM users u
    LEFT JOIN colleges col ON u.college_id = col.id
    JOIN roles r       ON u.role_id        = r.id
    WHERE LOWER(r.role_name) = 'faculty'
      AND (u.is_approved = 0 OR u.is_approved IS NULL)
    ORDER BY u.created_at DESC
");
$stmt->execute();
$pending_registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$reg_count = count($pending_registrations);
$unread_count = count_unread_notifications($user_id);
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
    <title>Registration Requests — Dean's Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php $active_page = 'registration'; include '_sidebar.php'; ?>

    <main class="scc-main">
        <div class="scc-page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color:var(--text)">Registration <span style="color:var(--primary)">Requests</span></h4>
                <p style="font-size:0.85rem;color:var(--text-secondary);margin:0">Approve or reject new faculty access requests</p>
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

        <?php if ($reg_count === 0): ?>
            <div class="alert border-0 shadow-sm mb-4 d-flex align-items-center p-3 animate-in" style="background:var(--primary-light);border-radius:var(--radius-md)">
                <div class="p-3 rounded-circle bg-white text-primary me-3 shadow-sm"><i class="bi bi-check2-circle fs-4"></i></div>
                <div>
                    <h6 class="fw-bold mb-0" style="color:var(--text)">All Clear</h6>
                    <p class="mb-0 small" style="color:var(--text-secondary)">No pending faculty registration requests at this time.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="alert border-0 shadow-sm mb-4 d-flex align-items-center p-3 animate-in" style="background:var(--warning-light);border-radius:var(--radius-md)">
                <div class="p-3 rounded-circle bg-white text-warning me-3 shadow-sm"><i class="bi bi-person-plus fs-4"></i></div>
                <div>
                    <h6 class="fw-bold mb-0" style="color:var(--text)">Action Required</h6>
                    <p class="mb-0 small" style="color:var(--text-secondary)"><?= $reg_count ?> faculty registration(s) awaiting your validation.</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="scc-card animate-in">
            <div class="card-header border-0 bg-transparent p-4 pb-0">
                <h6 class="fw-bold mb-0" style="color:var(--text)">Pending <span style="color:var(--primary)">Applications</span></h6>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="scc-table">
                        <thead>
                            <tr>
                                <th style="width: 50px">#</th>
                                <th>FULL NAME</th>
                                <th>EMAIL</th>
                                <th>COLLEGE</th>
                                <th>REGISTERED</th>
                                <th class="text-center">EMAIL STATUS</th>
                                <th class="text-center">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pending_registrations)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="bi bi-person-check fs-2 opacity-25 d-block mb-2"></i>
                                        No pending registration requests
                                    </td>
                                </tr>
                            <?php else:
                                foreach ($pending_registrations as $i => $reg): ?>
                                    <tr>
                                        <td class="small text-muted"><?= $i + 1 ?></td>
                                        <td>
                                            <div class="fw-bold small" style="color:var(--text)">
                                                <?= htmlspecialchars(trim($reg['first_name'] . ' ' . ($reg['middle_name'] ? $reg['middle_name'] . ' ' : '') . $reg['last_name'])) ?>
                                            </div>
                                        </td>
                                        <td class="small" style="color:var(--text-secondary)"><?= htmlspecialchars($reg['email']) ?></td>
                                        <td>
                                            <span class="badge rounded-pill border px-3 py-1" style="font-size:0.7rem;background:var(--primary-light);color:var(--primary);border-color:var(--primary-light) !important">
                                                <?= htmlspecialchars($reg['college_name'] ?? '—') ?>
                                            </span>
                                        </td>
                                        <td class="small" style="color:var(--text-secondary)"><?= date('M d, Y', strtotime($reg['created_at'])) ?></td>
                                        <td class="text-center">
                                            <?php if (!empty($reg['email_verified'])): ?>
                                                <span class="badge bg-success-light text-success border-success-light rounded-pill px-3" style="font-size:0.65rem">Verified</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-light text-warning border-warning-light rounded-pill px-3" style="font-size:0.65rem">Unverified</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-2 justify-content-center">
                                                <button onclick="handleAction('approve', <?= $reg['id'] ?>, '<?= htmlspecialchars($reg['first_name'] . ' ' . $reg['last_name']) ?>')" class="btn btn-sm btn-light border text-success fw-bold px-3"><i class="bi bi-check2 me-1"></i> Approve</button>
                                                <button onclick="handleAction('reject', <?= $reg['id'] ?>, '<?= htmlspecialchars($reg['first_name'] . ' ' . $reg['last_name']) ?>')" class="btn btn-sm btn-light border text-danger fw-bold px-3"><i class="bi bi-x-lg me-1"></i> Reject</button>
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

    <form id="actionForm" method="POST" action="registration_requests.php">
        <input type="hidden" name="action" id="formAction">
        <input type="hidden" name="user_id" id="formUserId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/common.js"></script>
    <script>
        function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('active');}
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