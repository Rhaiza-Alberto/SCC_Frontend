 <?php
/**
 * process_profile.php
 * Handles faculty profile update — saves first/middle/last name, birthdate, sex to DB.
 */
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit();
}

ensure_role_in_session();

$user_id = $_SESSION['user_id'];

$first_name  = trim($_POST['first_name']  ?? '');
$middle_name = trim($_POST['middle_name'] ?? '');
$last_name   = trim($_POST['last_name']   ?? '');
$birthdate   = trim($_POST['birthdate']   ?? '');
$sex         = trim($_POST['sex']         ?? '');

// Basic validation
if (empty($first_name) || empty($last_name)) {
    $_SESSION['error_message'] = "First name and last name are required.";
    header('Location: profile.php?edit=true');
    exit();
}

if (!in_array($sex, ['Male', 'Female'])) {
    $_SESSION['error_message'] = "Invalid sex value.";
    header('Location: profile.php?edit=true');
    exit();
}

if ($birthdate && !strtotime($birthdate)) {
    $_SESSION['error_message'] = "Invalid birthdate.";
    header('Location: profile.php?edit=true');
    exit();
}

try {
    $conn = get_db();
    $stmt = $conn->prepare("
        UPDATE users
        SET first_name  = ?,
            middle_name = ?,
            last_name   = ?,
            birthdate   = ?,
            sex         = ?
        WHERE id = ? AND is_deleted = 0
    ");
    $stmt->execute([
        $first_name,
        $middle_name ?: null,
        $last_name,
        $birthdate   ?: null,
        $sex,
        $user_id
    ]);

    // Update session username
    $_SESSION['username'] = $first_name . ' ' . $last_name;

    $_SESSION['success_message'] = "Profile updated successfully.";
    header('Location: profile.php');
    exit();

} catch (PDOException $e) {
    error_log("Profile Update Error: " . $e->getMessage());
    $_SESSION['error_message'] = "A database error occurred. Please try again.";
    header('Location: profile.php?edit=true');
    exit();
}