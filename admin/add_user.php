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

// Fetch roles
$stmt = $conn->prepare("SELECT * FROM roles WHERE role_name != 'department_head' ORDER BY role_name");
$stmt->execute();
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch colleges
$stmt = $conn->prepare("SELECT * FROM colleges ORDER BY college_name");
$stmt->execute();
$colleges = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

    // Validation (same as register.php)
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
                
                // Redirect to onboarding verification for the requested UX flow
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .form-section-label { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 800; color: var(--primary); display: flex; align-items: center; gap: 10px; margin-bottom: 1.5rem; }
        .form-section-label::after { content: ''; flex-grow: 1; height: 1px; background: var(--border); opacity: 0.5; }
        .custom-label { font-size: 0.75rem; font-weight: 700; color: var(--text-dark); margin-bottom: 0.4rem; display: block; }
        .error-msg { color: #ef4444; font-size: 0.7rem; margin-top: 4px; font-weight: 500; }
    </style>
</head>
<body>
    <?php $active_page = 'add_user'; include '_sidebar.php'; ?>

    <main class="scc-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color:var(--text)">Add <span style="color:var(--primary)">User Account</span></h4>
                <p style="font-size:0.85rem;color:var(--text-secondary);margin:0">Register new staff using the standard institutional format</p>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success border-0 shadow-sm mb-4"><?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger border-0 shadow-sm mb-4"><?= $error ?></div>
        <?php endif; ?>

        <div class="scc-card animate-in" style="max-width: 800px; margin: 0 auto;">
            <div class="card-body p-4 p-md-5">
                <form method="POST">
                    <div class="form-section-label">Personal Information</div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-5">
                            <label class="custom-label">First Name</label>
                            <input type="text" name="firstName" class="form-control <?= isset($errors['firstName']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($form_data['firstName']) ?>" placeholder="First name" required>
                            <?php if (isset($errors['firstName'])): ?><div class="error-msg"><?= $errors['firstName'] ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-2">
                            <label class="custom-label">M.I.</label>
                            <input type="text" name="middleName" class="form-control" value="<?= htmlspecialchars($form_data['middleName']) ?>" placeholder="—">
                        </div>
                        <div class="col-md-5">
                            <label class="custom-label">Last Name</label>
                            <input type="text" name="lastName" class="form-control <?= isset($errors['lastName']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($form_data['lastName']) ?>" placeholder="Last name" required>
                            <?php if (isset($errors['lastName'])): ?><div class="error-msg"><?= $errors['lastName'] ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="custom-label">Birthdate</label>
                            <input type="date" name="birthdate" class="form-control <?= isset($errors['birthdate']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($form_data['birthdate']) ?>" required max="<?= date('Y-m-d') ?>">
                            <?php if (isset($errors['birthdate'])): ?><div class="error-msg"><?= $errors['birthdate'] ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="custom-label">Sex</label>
                            <div class="d-flex gap-2">
                                <input type="radio" class="btn-check" name="sex" id="sexMale" value="male" <?= $form_data['sex'] === 'male' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary w-100 border-0" style="background:var(--bg-secondary); font-size:0.8rem; border-radius:8px" for="sexMale">Male</label>
                                <input type="radio" class="btn-check" name="sex" id="sexFemale" value="female" <?= $form_data['sex'] === 'female' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary w-100 border-0" style="background:var(--bg-secondary); font-size:0.8rem; border-radius:8px" for="sexFemale">Female</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-section-label">Account Credentials</div>
                    <div class="mb-4">
                        <label class="custom-label">Email Address (@gmail.com)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($form_data['email']) ?>" placeholder="user@gmail.com" required>
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
                                    <option value="<?= $role['id'] ?>" <?= $form_data['role_id'] == $role['id'] ? 'selected' : '' ?>><?= ucfirst($role['role_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['role_id'])): ?><div class="error-msg"><?= $errors['role_id'] ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="custom-label">College Affiliation</label>
                            <select name="college_id" class="form-select">
                                <?php foreach ($colleges as $col): ?>
                                    <option value="<?= $col['id'] ?>" <?= $form_data['college_id'] == $col['id'] ? 'selected' : '' ?>><?= htmlspecialchars($col['college_name']) ?></option>
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