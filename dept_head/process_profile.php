<?php
session_start();
include_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['logged_in'])) {
    $email = $_SESSION['email'];
    $first_name = $_POST['first_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $sex = $_POST['sex'] ?? '';
    $department = $_POST['department'] ?? '';

    try {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, birthdate = ?, sex = ?, department = ?, username = ? WHERE email = ?");
        $new_username = $first_name . ' ' . $last_name;
        $stmt->execute([$first_name, $middle_name, $last_name, $birthdate, $sex, $department, $new_username, $email]);

        // Update session variables
        $_SESSION['username'] = $new_username;

        $_SESSION['success'] = "Profile updated successfully!";
        header("Location: profile.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Update failed: " . $e->getMessage();
        header("Location: profile.php?edit=true");
        exit();
    }
} else {
    header("Location: ../login.php");
    exit();
}
?>