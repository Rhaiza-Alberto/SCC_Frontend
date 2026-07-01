<?php
/**
 * admin/process_profile.php
 */
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

restrict_to_role('dean');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit();
}

$user_id     = $_SESSION['user_id'];
$first_name  = trim($_POST['first_name']  ?? '');
$middle_name = trim($_POST['middle_name'] ?? '') ?: null;
$last_name   = trim($_POST['last_name']   ?? '');
$birthdate   = trim($_POST['birthdate']   ?? '');
$sex         = trim($_POST['sex']         ?? '');

if (empty($first_name) || empty($last_name) || empty($birthdate) || empty($sex)) {
    $_SESSION['error_message'] = 'Please fill in all required fields.';
    header('Location: profile.php?edit=true');
    exit();
}

if (strtotime($birthdate) > time()) {
    $_SESSION['error_message'] = 'Birthdate cannot be in the future.';
    header('Location: profile.php?edit=true');
    exit();
}

$sex_normalized = ucfirst(strtolower($sex));
if (!in_array($sex_normalized, ['Male', 'Female'])) {
    $_SESSION['error_message'] = 'Invalid sex value.';
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
        WHERE id = ?
    ");
    $stmt->execute([
        $first_name,
        $middle_name,
        $last_name,
        $birthdate,
        $sex_normalized,
        $user_id,
    ]);

    $_SESSION['username'] = trim($first_name . ' ' . $last_name);

    $_SESSION['success_message'] = 'Profile updated successfully!';
    header('Location: profile.php');
    exit();

} catch (PDOException $e) {
    error_log('Profile Update Error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Update failed. Please try again.';
    header('Location: profile.php?edit=true');
    exit();
}
