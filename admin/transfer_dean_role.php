 <?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$db   = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("SELECT users.*, roles.role_name FROM users
                        LEFT JOIN roles ON users.role_id = roles.id
                        WHERE users.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();

if ($current_user['role_name'] !== 'dean') {
    header('Location: admin_dashboard.php');
    exit();
}

$username     = $_SESSION['username'] ?? 'Dean / Admin';
$role_display = "Dean's Panel";

$stmt = $conn->prepare("SELECT users.*, roles.role_name, colleges.college_name
                        FROM users
                        LEFT JOIN roles       ON users.role_id       = roles.id
                        LEFT JOIN colleges    ON users.college_id    = colleges.id
                        WHERE users.is_deleted = 0
                        AND users.id != ?
                        AND roles.role_name != 'dean'
                        ORDER BY users.first_name, users.last_name");
$stmt->execute([$_SESSION['user_id']]);
$eligible_users = $stmt->fetchAll();

$stmt = $conn->prepare("SELECT id FROM roles WHERE role_name = 'dean'");
$stmt->execute();
$dean_role    = $stmt->fetch();
$dean_role_id = $dean_role['id'];

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_dean_id  = (int) ($_POST['new_dean_id'] ?? 0);
    $confirmation = $_POST['confirmation'] ?? '';

    if ($new_dean_id <= 0) {
        $error = 'Please select a user to transfer the dean role to.';
    } elseif ($confirmation !== 'TRANSFER') {
        $error = 'Please type "TRANSFER" to confirm the role transfer.';
    } else {
        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("SELECT users.role_id, roles.role_name FROM users
                                    LEFT JOIN roles ON users.role_id = roles.id
                                    WHERE users.id = ? AND users.is_deleted = 0
                                    AND roles.role_name != 'dean'");
            $stmt->execute([$new_dean_id]);
            $new_dean_old = $stmt->fetch();

            if (!$new_dean_old) {
                $conn->rollBack();
                $error = 'Invalid user selected. Please try again.';
            } else {
                $stmt = $conn->prepare("UPDATE users SET role_id = ? WHERE id = ?");
                $stmt->execute([$dean_role_id, $new_dean_id]);

                $stmt = $conn->prepare("UPDATE users SET role_id = ? WHERE id = ?");
                $stmt->execute([$new_dean_old['role_id'], $_SESSION['user_id']]);

                $conn->commit();

                session_destroy();
                header('Location: ../login.php?msg=role_transferred');
                exit();
            }
        } catch (Exception $e) {
            $conn->rollBack();
            $error = 'An error occurred during the transfer. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Dean Role — SCC Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php $active_page = 'users'; include '_sidebar.php'; ?>

    <main class="scc-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color:var(--text)">Transfer <span style="color:var(--primary)">Dean Role</span></h4>
                <p style="font-size:0.85rem;color:var(--text-secondary);margin:0">Succession management and administrative role handover</p>
            </div>
            <div>
                <a href="manage_user.php" class="btn btn-light border fw-bold text-secondary rounded-pill px-4">
                    <i class="bi bi-arrow-left me-2"></i> Back to Users
                </a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger border-0 shadow-sm mb-4 d-flex align-items-center animate-in" style="border-radius:var(--radius-md)">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="alert alert-warning border-0 shadow-sm mb-4 p-4 animate-in" style="background:var(--warning-light); color:var(--warning); border-radius:var(--radius-lg)">
            <h6 class="fw-bold mb-3 d-flex align-items-center"><i class="bi bi-shield-lock-fill fs-4 me-2"></i> Critical Succession Notice</h6>
            <p class="small mb-2 opacity-75">You are initiating a permanent transfer of administrative authority. Please review the following implications:</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <ul class="mb-0 small">
                        <li class="mb-1">This action is <strong>irreversible</strong> once confirmed.</li>
                        <li class="mb-1">Immediate loss of all Dean-level privileges.</li>
                        <li class="mb-1">The selected user will gain full system control.</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul class="mb-0 small">
                        <li class="mb-1">You will be automatically logged out.</li>
                        <li class="mb-1">Your role will swap with the selected user's role.</li>
                        <li class="mb-1">Institutional records will reflect this handover.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="scc-card animate-in" style="max-width: 700px; margin: 0 auto;">
            <div class="card-header border-0 bg-transparent p-4 pb-0 text-center">
                <h6 class="fw-bold mb-0" style="color:var(--text)">Succession <span style="color:var(--primary)">Authorization</span></h6>
                <p class="small text-muted mb-0">Authorized handover of administrative credentials</p>
            </div>
            <div class="card-body p-4 p-md-5">
                <form method="POST" id="transferForm">
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-secondary">Designate New Dean <span class="text-danger">*</span></label>
                        <select name="new_dean_id" class="form-select" required>
                            <option value="">Choose a successor...</option>
                            <?php foreach ($eligible_users as $user): ?>
                                <option value="<?= (int) $user['id'] ?>">
                                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                    — <?= htmlspecialchars(ucfirst($user['role_name'])) ?>
                                    <?php if ($user['college_name']): ?>(<?= htmlspecialchars($user['college_name']) ?>)<?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text small opacity-75">The user who will receive the Dean credentials</div>
                    </div>

                    <div class="mb-5 p-4 bg-light rounded-4 border">
                        <label class="form-label fw-bold small text-dark">Succession Confirmation <span class="text-danger">*</span></label>
                        <input type="text" name="confirmation" class="form-control mb-2" placeholder="Type 'TRANSFER' to authorize" required style="letter-spacing: 2px; text-transform: uppercase;">
                        <p class="small text-muted mb-0">Type <strong>TRANSFER</strong> in all caps to finalize the handover authorization.</p>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-8">
                            <button type="button" onclick="confirmTransfer()" class="btn btn-danger btn-lg w-100 fw-bold rounded-pill shadow-sm">
                                <i class="bi bi-arrow-left-right me-2"></i> Execute Role Transfer
                            </button>
                        </div>
                        <div class="col-md-4">
                            <a href="manage_user.php" class="btn btn-light btn-lg w-100 fw-bold rounded-pill border">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/common.js"></script>
<script>
    function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('active');}
    function confirmTransfer() {
        const confirmVal = document.querySelector('input[name="confirmation"]').value;
        const selectedUser = document.querySelector('select[name="new_dean_id"]');
        
        if (!selectedUser.value) {
            Swal.fire('Error', 'Please select a successor.', 'error');
            return;
        }
        
        if (confirmVal !== 'TRANSFER') {
            Swal.fire('Confirmation Required', 'Please type TRANSFER to confirm.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Critical Handover',
            text: "Are you absolutely sure? You will be logged out and lose all administrative access immediately.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--danger)',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Finalize Transfer'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('transferForm').submit();
            }
        });
    }
</script>
</body>
</html>