<?php
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Simple validation
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Please fill in all fields';
        header('Location: login.php');
        exit();
    }

    // Email domain validation
    if (!preg_match("/@gmail\.com$/i", $email)) {
        $_SESSION['error'] = 'Invalid email. Only @gmail.com addresses are accepted.';
        header('Location: login.php');
        exit();
    }

    $demo_users = [
        'faculty@gmail.com' => [
            'password' => 'faculty123',
            'username' => 'Achy',
            'role' => 'faculty'
        ],
        'admin@gmail.com' => [
            'password' => 'admin123',
            'username' => 'Admin User',
            'role' => 'admin'
        ],
        'dept@gmail.com' => [
            'password' => 'dept123',
            'username' => 'Dr. Jane Smith',
            'role' => 'dept_head'
        ],
        'vpaa@gmail.com' => [
            'password' => 'vpaa123',
            'username' => 'VPAA',
            'role' => 'vpaa'
        ]
    ];

    // Check if user exists and password matches
    if (isset($demo_users[$email]) && $demo_users[$email]['password'] === $password) {
        // Set session variables
        $_SESSION['logged_in'] = true;
        $_SESSION['email'] = $email;
        $_SESSION['username'] = $demo_users[$email]['username'];
        $_SESSION['role'] = $demo_users[$email]['role'];

        // Redirect based on role
        switch ($demo_users[$email]['role']) {
            case 'faculty':
                header('Location: faculty/faculty_dashboard.php');
                break;
            case 'admin':
                header('Location: admin/admin_dashboard.php');
                break;
            case 'dept_head':
                header('Location: dept_head/dept_dashboard.php');
                break;
            case 'vpaa':
                header('Location: vpaa/vpaa_dashboard.php');
                break;
            default:
                header('Location: faculty/faculty_dashboard.php');
        }
        exit();
    } else {
        // Invalid credentials
        $_SESSION['error'] = 'Invalid email or password';
        header('Location: login.php');
        exit();
    }
} else {
    // If not POST request, redirect to login
    header('Location: login.php');
    exit();
}
?>