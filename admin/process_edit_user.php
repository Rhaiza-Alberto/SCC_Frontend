<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_user.php');
    exit();
}

$user_id = (int)($_POST['user_id'] ?? 0);
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role_id = (int)($_POST['role_id'] ?? 0);
$department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;

if (!$user_id || !$first_name || !$last_name || !$email || !$role_id) {
    $_SESSION['error_message'] = "All mandatory fields are required.";
    header("Location: edit_user.php?id=$user_id");
    exit();
}

if (update_user($user_id, $first_name, $last_name, $email, $role_id, $department_id)) {
    $_SESSION['success_message'] = "User updated successfully.";
    header('Location: manage_user.php');
} else {
    $_SESSION['error_message'] = "Failed to update user.";
    header("Location: edit_user.php?id=$user_id");
}
exit();
