<?php
/**
 * dept_head/registration_requests.php
 * Approve or reject pending faculty registrations.
 * Fixed: Bootstrap Icons CDN added, notification bell working, DB-driven counts.
 */
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

restrict_to_role('department_head');

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$role_display = 'Dept Head Panel';
$college_id = $_SESSION['college_id'] ?? null;

// Handle mark-all-read
if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: registration_requests.php');
    exit();
}

$conn = get_db();

// ── Handle Approve / Reject POST ─────────────────────────────────────────────
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

// ── Fetch pending faculty registrations for this department ──────────────────
$pending_registrations = [];
$db_error = null;
try {
    if ($college_id) {
        $stmt = $conn->prepare("
            SELECT u.id, u.first_name, u.middle_name, u.last_name,
                   u.email, u.created_at, col.college_name
            FROM users u
            JOIN colleges col  ON u.college_id   = col.id
            JOIN roles r       ON u.role_id      = r.id
            WHERE r.role_name    = 'faculty'
              AND u.is_approved  = 0
              AND u.college_id   = ?
            ORDER BY u.created_at DESC
        ");
        $stmt->execute([$college_id]);
    } else {
        $stmt = $conn->prepare("
            SELECT u.id, u.first_name, u.middle_name, u.last_name,
                   u.email, u.created_at, col.college_name
            FROM users u
            JOIN colleges col  ON u.college_id   = col.id
            JOIN roles r       ON u.role_id      = r.id
            WHERE r.role_name   = 'faculty'
              AND u.is_approved = 0
            ORDER BY u.created_at DESC
        ");
        $stmt->execute();
    }
    $pending_registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Registration requests error: " . $e->getMessage());
    $db_error = "Database error: " . $e->getMessage();
}

$reg_count = count($pending_registrations);
$unread_count = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Requests - SCC-CCS Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap"
        rel="stylesheet">
    <!-- Bootstrap Icons — was MISSING in original file -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/common.js"></script>
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .text-orange {
            color: #ff8800 !important;
        }

        .notif-dot {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 10px;
            height: 10px;
         <?php $active_page = 'registration'; include '_sidebar.php'; ?>

    <main class="scc-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color:var(--text)">Registration <span style="color:var(--primary)">Requests</span></h4>
                <p style="font-size:0.85rem;color:var(--text-secondary);margin:0">Approve or reject new faculty access requests</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                 <span class="badge <?= $reg_count > 0 ? 'bg-warning-light text-warning border-warning-border' : 'bg-light text-muted border' ?> rounded-pill px-3 py-1" style="font-size:0.75rem">
                    <i class="bi bi-person-plus me-1"></i> <?= $reg_count ?> New
                </span>
            </div>
        </div>

        <?php if ($reg_count === 0): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-4 py-3 mb-4 d-flex align-items-center" role="alert" style="background:var(--success-light); color:var(--success)">
                <i class="bi bi-check-circle-fill me-3 fs-5"></i>
                <div>All faculty registration requests have been cleared.</div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning border-0 shadow-sm rounded-4 py-3 mb-4 d-flex align-items-center" role="alert" style="background:var(--warning-light); color:var(--warning)">
                <i class="bi bi-exclamation-circle-fill me-3 fs-5"></i>
                <div>You have <?= $reg_count ?> faculty registration request(s) awaiting your validation.</div>
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
                                <th class="ps-4">FULL NAME</th>
                                <th>EMAIL</th>
                                <th>COLLEGE</th>
                                <th>REGISTERED</th>
                                <th class="text-center pe-4">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pending_registrations)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="bi bi-person-check fs-1 opacity-25 d-block mb-2"></i>
                                        No pending registration requests
                                    </td>
                                </tr>
                            <?php else:
                                foreach ($pending_registrations as $reg): ?>
                                    <tr>
                                        <td class="ps-4">
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
                                        <td class="text-center pe-4">
                                            <div class="d-flex gap-2 justify-content-center">
                                                <button onclick="handleAction('approve', <?= $reg['id'] ?>, '<?= htmlspecialchars($reg['first_name'] . ' ' . $reg['last_name']) ?>')" class="btn btn-sm btn-success rounded-pill px-3">Approve</button>
                                                <button onclick="handleAction('reject', <?= $reg['id'] ?>, '<?= htmlspecialchars($reg['first_name'] . ' ' . $reg['last_name']) ?>')" class="btn btn-sm btn-danger rounded-pill px-3">Reject</button>
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

            <!-- Hidden POST form for SweetAlert confirm -->
            <form id="actionForm" method="POST" action="registration_requests.php">
                <input type="hidden" name="action" id="formAction">
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