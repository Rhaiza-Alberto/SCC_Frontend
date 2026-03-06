 <?php
/**
 * process_register.php
 * Handles faculty registration form submission.
 */
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit();
}

$first_name       = trim($_POST['firstName']       ?? '');
$middle_name      = trim($_POST['middleName']       ?? '') ?: null;
$last_name        = trim($_POST['lastName']         ?? '');
$email            = trim($_POST['email']            ?? '');
$password         = $_POST['password']              ?? '';
$confirm_password = $_POST['confirmPassword']       ?? '';
$birthdate        = $_POST['birthdate']             ?? '';
$sex              = $_POST['sex']                   ?? '';
$department_id    = $_POST['department']            ?? '';

// ── Validation ────────────────────────────────────────────────────────────────
if (empty($first_name) || empty($last_name) || empty($email) || empty($password)
    || empty($confirm_password) || empty($birthdate) || empty($sex) || empty($department_id)) {
    $_SESSION['register_error'] = 'Please fill in all required fields.';
    header('Location: register.php');
    exit();
}

if ($password !== $confirm_password) {
    $_SESSION['register_error'] = 'Passwords do not match.';
    header('Location: register.php');
    exit();
}

if (strlen($password) < 6) {
    $_SESSION['register_error'] = 'Password must be at least 6 characters.';
    header('Location: register.php');
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match("/@gmail\.com$/i", $email)) {
    $_SESSION['register_error'] = 'Invalid email. Only @gmail.com addresses are accepted.';
    header('Location: register.php');
    exit();
}

$sex_normalized = ucfirst(strtolower($sex)); // 'male' → 'Male'
if (!in_array($sex_normalized, ['Male', 'Female'])) {
    $_SESSION['register_error'] = 'Invalid sex value.';
    header('Location: register.php');
    exit();
}

try {
    $conn = get_db();

    // Check if email already exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND is_deleted = 0");
    $check->execute([$email]);
    if ($check->fetch()) {
        $_SESSION['register_error'] = 'This email is already registered.';
        header('Location: register.php');
        exit();
    }

    // Verify the department exists
    $deptCheck = $conn->prepare("SELECT id FROM departments WHERE id = ?");
    $deptCheck->execute([$department_id]);
    if (!$deptCheck->fetch()) {
        $_SESSION['register_error'] = 'Invalid department selected.';
        header('Location: register.php');
        exit();
    }

    // Get faculty role ID
    $roleStmt = $conn->prepare("SELECT id FROM roles WHERE role_name = 'faculty'");
    $roleStmt->execute();
    $role = $roleStmt->fetch(PDO::FETCH_ASSOC);

    if (!$role) {
        $_SESSION['register_error'] = 'System error: role not found. Please contact admin.';
        header('Location: register.php');
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Insert new user (is_approved = 0, pending department head approval)
    $stmt = $conn->prepare("
        INSERT INTO users
            (first_name, middle_name, last_name, birthdate, sex,
             email, password, role_id, department_id,
             created_at, is_deleted, is_approved, reset_requested)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 0, 0, 0)
    ");
    $stmt->execute([
        $first_name,
        $middle_name,
        $last_name,
        $birthdate,
        $sex_normalized,
        $email,
        $hashed_password,
        $role['id'],
        $department_id,
    ]);

    // Notify department head of the new registration request
    $dept_head = get_department_head((int) $department_id);
    if ($dept_head) {
        notify_user(
            $dept_head['id'],
            "New faculty registration request from {$first_name} {$last_name}.",
            null
        );
    }

    header('Location: register.php?success=true');
    exit();

} catch (PDOException $e) {
    error_log("Registration Error: " . $e->getMessage());
    $_SESSION['register_error'] = 'A database error occurred. Please try again later.';
    header('Location: register.php');
    exit();
}