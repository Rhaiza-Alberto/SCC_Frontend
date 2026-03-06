<?php
/**
 * dept_head/process_profile.php
 * Handles profile update POST for the department head.
 * Fixes: $pdo → get_db(), removed non-existent columns (department/username),
 *        correct session key names, redirects to correct paths.
 */
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$user_id    = $_SESSION['user_id'] ?? null;
$first_name  = trim($_POST['first_name']  ?? '');
$middle_name = trim($_POST['middle_name'] ?? '') ?: null;
$last_name   = trim($_POST['last_name']   ?? '');
$birthdate   = trim($_POST['birthdate']   ?? '');
$sex         = trim($_POST['sex']         ?? '');

// Validate required fields
if (empty($first_name) || empty($last_name) || empty($birthdate) || empty($sex)) {
    $_SESSION['error_message'] = 'Please fill in all required fields.';
    header('Location: profile.php?edit=true');
    exit();
}

$sex_normalized = ucfirst(strtolower($sex));
if (!in_array($sex_normalized, ['Male', 'Female'])) {
    $_SESSION['error_message'] = 'Invalid sex value.';
    header('Location: profile.php?edit=true');
    exit();
}

if (!$user_id) {
    header('Location: ../login.php');
    exit();
}

try {
    $conn = get_db();

    // Update only the columns that actually exist in the users table
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
        $middle_name,
        $last_name,
        $birthdate,
        $sex_normalized,
        $user_id,
    ]);

    // Update session display name
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