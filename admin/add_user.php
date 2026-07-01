<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

restrict_to_role('dean');

$username = $_SESSION['username'] ?? 'Dean / Admin';
$role_display = "Dean's Panel";
$user_id_session = $_SESSION['user_id'];

if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id_session);
    header('Location: add_user.php');
    exit();
}

$unread_count = count_unread_notifications($user_id_session);
$notifications = get_notifications($user_id_session, 5);

$conn = get_db();

$stmt = $conn->prepare("SELECT * FROM roles WHERE role_name != 'department_head' ORDER BY role_name");
$stmt->execute();
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT * FROM colleges ORDER BY college_name");
$stmt->execute();
$colleges = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pending_review_count = (int) $conn->query("
    SELECT COUNT(DISTINCT sw.syllabus_id)
    FROM syllabus_workflow sw
    JOIN roles r ON sw.role_id = r.id
    WHERE r.role_name = 'dean' AND sw.action = 'Pending'
")->fetchColumn();

$reg_count = (int) $conn->query("
    SELECT COUNT(*) FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE r.role_name = 'faculty' AND u.is_approved = 0 AND u.is_deleted = 0
")->fetchColumn();

$success = "";
$error = "";
$errors = [];
$form_data = [
    'firstName' => '',
    'middleName' => '',
    'lastName' => '',
    'birthdate' => '',
    'sex' => 'male',
    'email' => '',
    'role_id' => '',
    'college_id' => '1'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data['firstName'] = trim($_POST['firstName'] ?? '');
    $form_data['middleName'] = trim($_POST['middleName'] ?? '');
    $form_data['lastName'] = trim($_POST['lastName'] ?? '');
    $form_data['birthdate'] = $_POST['birthdate'] ?? '';
    $form_data['sex'] = $_POST['sex'] ?? 'male';
    $form_data['email'] = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $form_data['role_id'] = $_POST['role_id'] ?? '';
    $form_data['college_id'] = $_POST['college_id'] ?? '1';

    if (empty($form_data['firstName'])) $errors['firstName'] = 'First name is required.';
    if (empty($form_data['lastName'])) $errors['lastName'] = 'Last name is required.';
    if (empty($form_data['birthdate'])) {
        $errors['birthdate'] = 'Birthdate is required.';
    } elseif (strtotime($form_data['birthdate']) > time()) {
        $errors['birthdate'] = 'Birthdate cannot be in the future.';
    }
    
    if (empty($form_data['email'])) {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } elseif (!preg_match("/@gmail\.com$/i", $form_data['email'])) {
        $errors['email'] = 'Only @gmail.com addresses are accepted.';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors['confirmPassword'] = 'Passwords do not match.';
    }

    if (empty($form_data['role_id'])) $errors['role_id'] = 'Role is required.';

    if (empty($errors)) {
        try {
            $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND is_deleted = 0");
            $check->execute([$form_data['email']]);
            if ($check->fetch()) {
                $error = "This email is already registered.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $sex_normalized = ucfirst(strtolower($form_data['sex']));
                $verification_token = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

                $stmt = $conn->prepare("
                    INSERT INTO users
                        (first_name, middle_name, last_name, birthdate, sex,
                         email, password, role_id, college_id,
                         created_at, is_deleted, is_approved, reset_requested,
                         verification_token, email_verified)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 0, 1, 0, ?, 0)
                ");
                $stmt->execute([
                    $form_data['firstName'],
                    !empty($form_data['middleName']) ? $form_data['middleName'] : null,
                    $form_data['lastName'],
                    $form_data['birthdate'],
                    $sex_normalized,
                    $form_data['email'],
                    $hashed_password,
                    $form_data['role_id'],
                    $form_data['college_id'],
                    $verification_token
                ]);

                // Email Logic
                $verify_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'], 2) . "/verify_user.php?email=" . urlencode($form_data['email']);
                $subject = "Institutional Account Created - SCC Portal";
                $body = "Hello {$form_data['firstName']}, an account has been created for you. Use code: {$verification_token} to activate. Link: {$verify_link}";
                send_system_email($form_data['email'], $subject, $body);

                $success = "User account created! A verification code has been sent to " . htmlspecialchars($form_data['email']);
                
                header('Location: verify_onboarding.php?email=' . urlencode($form_data['email']));
                exit();
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User — SCC Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .form-section-label { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 800; color: var(--primary); display: flex; align-items: center; gap: 10px; margin-bottom: 1.5rem; }
        .form-section-label::after { content: ''; flex-grow: 1; height: 1px; background: var(--border); opacity: 0.5; }
        .custom-label { font-size: 0.75rem; font-weight: 700; color: var(--text-dark); margin-bottom: 0.4rem; display: block; }
        .error-msg { color: #ef4444; font-size: 0.7rem; margin-top: 4px; font-weight: 500; }
        .position-relative.mb-4 { z-index: 1070 !important; }
        .dropdown-menu { z-index: 1080 !important; }
    </style>
</head>
<body>
    <?php $active_page = 'add_user'; include '_sidebar.php'; ?>

    <main class="scc-main">
        <div class="mb-4 position-relative" style="z-index: 1070;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h4 class="fw-bold mb-1" style="color:var(--text)">Add <span style="color:var(--primary)">User Account</span></h4>
                    <p style="font-size:0.85rem;color:var(--text-secondary);margin:0">Register new staff using the standard institutional format</p>
                </div>
                
                <div class="d-flex align-items-center gap-3" style="position: relative; z-index: 1075;">
                    <div class="dropdown">
                        <div class="position-relative" style="cursor:pointer" data-bs-toggle="dropdown">
                            <i class="bi bi-bell fs-5" style="color:var(--text)"></i>
                            <?php if ($unread_count > 0): ?><span class="notif-badge"><?= $unread_count > 9 ? '9+' : $unread_count ?></span><?php endif; ?>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="width:340px;max-height:420px;overflow-y:auto;border-radius:var(--radius-md);background:var(--bg-card); z-index: 1080 !important;">
                            <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom sticky-top" style="background:var(--bg-card); z-index: 12;">
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
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success border-0 shadow-sm mb-4"><?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger border-0 shadow-sm mb-4"><?= $error ?></div>
        <?php endif; ?>

        <div class="scc-card animate-in" style="max-width: 800px; margin: 0 auto; position: relative; z-index: 1;">
            <div class="card-body p-4 p-md-5">
                <form method="POST">
                    <div class="form-section-label">Personal Information</div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-5">
                            <label class="custom-label">First Name</label>
                            <input type="text" name="firstName" class="form-control <?= isset($errors['firstName']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($form_data['firstName'] ?? '') ?>" placeholder="First name" required>
                            <?php if (isset($errors['firstName'])): ?><div class="error-msg"><?= $errors['firstName'] ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-2">
                            <label class="custom-label">M.I.</label>
                            <input type="text" name="middleName" class="form-control" value="<?= htmlspecialchars($form_data['middleName'] ?? '') ?>" placeholder="—">
                        </div>
                        <div class="col-md-5">
                            <label class="custom-label">Last Name</label>
                            <input type="text" name="lastName" class="form-control <?= isset($errors['lastName']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($form_data['lastName'] ?? '') ?>" placeholder="Last name" required>
                            <?php if (isset($errors['lastName'])): ?><div class="error-msg"><?= $errors['lastName'] ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="custom-label">Birthdate</label>
                            <input type="date" name="birthdate" class="form-control <?= isset($errors['birthdate']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($form_data['birthdate'] ?? '') ?>" required max="<?= date('Y-m-d') ?>">
                            <?php if (isset($errors['birthdate'])): ?><div class="error-msg"><?= $errors['birthdate'] ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="custom-label">Sex</label>
                            <div class="d-flex gap-2">
                                <input type="radio" class="btn-check" name="sex" id="sexMale" value="male" <?= ($form_data['sex'] ?? 'male') === 'male' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary w-100 border-0" style="background:var(--bg-secondary); font-size:0.8rem; border-radius:8px" for="sexMale">Male</label>
                                <input type="radio" class="btn-check" name="sex" id="sexFemale" value="female" <?= ($form_data['sex'] ?? '') === 'female' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary w-100 border-0" style="background:var(--bg-secondary); font-size:0.8rem; border-radius:8px" for="sexFemale">Female</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-section-label">Account Credentials</div>
                    <div class="mb-4">
                        <label class="custom-label">Email Address (@gmail.com)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($form_data['email'] ?? '') ?>" placeholder="user@gmail.com" required>
                        </div>
                        <?php if (isset($errors['email'])): ?><div class="error-msg"><?= $errors['email'] ?></div><?php endif; ?>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="custom-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-shield-lock"></i></span>
                                <input type="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" placeholder="••••••••" required>
                            </div>
                            <?php if (isset($errors['password'])): ?><div class="error-msg"><?= $errors['password'] ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="custom-label">Confirm Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-check-circle"></i></span>
                                <input type="password" name="confirmPassword" class="form-control <?= isset($errors['confirmPassword']) ? 'is-invalid' : '' ?>" placeholder="••••••••" required>
                            </div>
                            <?php if (isset($errors['confirmPassword'])): ?><div class="error-msg"><?= $errors['confirmPassword'] ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div class="form-section-label">Institutional Role</div>
                    <div class="row g-3 mb-5">
                        <div class="col-md-6">
                            <label class="custom-label">Assigned Role</label>
                            <select name="role_id" class="form-select <?= isset($errors['role_id']) ? 'is-invalid' : '' ?>" required>
                                <option value="" disabled selected>Select Role</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['id'] ?>" <?= ($form_data['role_id'] ?? '') == $role['id'] ? 'selected' : '' ?>><?= ucfirst($role['role_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['role_id'])): ?><div class="error-msg"><?= $errors['role_id'] ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="custom-label">College Affiliation</label>
                            <select name="college_id" class="form-select">
                                <?php foreach ($colleges as $col): ?>
                                    <option value="<?= $col['id'] ?>" <?= ($form_data['college_id'] ?? '') == $col['id'] ? 'selected' : '' ?>><?= htmlspecialchars($col['college_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-scc w-100 py-3 fw-bold rounded-pill shadow-sm">
                        Create Institutional Account
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .btn-check:checked+label { background: var(--primary) !important; color: white !important; font-weight: 700; }
    </style>
</body>
</html>