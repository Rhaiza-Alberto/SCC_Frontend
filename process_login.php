<?php
session_start();

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

    $demo_users = [
        'faculty@scc.edu' => [
            'password' => 'faculty123',
            'username' => 'Xy',
            'role' => 'faculty'
        ],
        'admin@scc.edu' => [
            'password' => 'admin123',
            'username' => 'Admin User',
            'role' => 'admin'
        ],
        'dept@scc.edu' => [
            'password' => 'dept123',
            'username' => 'Department Head',
            'role' => 'dept_head'
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