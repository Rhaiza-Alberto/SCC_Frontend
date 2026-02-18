<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['firstName'] ?? '');
    $last_name = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirmPassword'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $sex = $_POST['sex'] ?? '';
    $department = $_POST['department'] ?? '';

    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password) || empty($birthdate) || empty($sex) || empty($department)) {
        $_SESSION['register_error'] = 'Please fill in all required fields.';
        header('Location: register.php');
        exit();
    }

    if ($password !== $confirm_password) {
        $_SESSION['register_error'] = 'Passwords do not match.';
        header('Location: register.php');
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match("/@gmail\.com$/i", $email)) {
        $_SESSION['register_error'] = 'Invalid email. Only @gmail.com addresses are accepted.';
        header('Location: register.php');
        exit();
    }

    // Simulate backend processing (since backend isn't starting yet)
    // In a real scenario, we would save to database here.

    // Redirect to success page
    header('Location: register.php?success=true');
    exit();
} else {
    header('Location: register.php');
    exit();
}
?>