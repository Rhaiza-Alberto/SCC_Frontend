<?php
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Please fill in all fields.';
        header('Location: login.php');
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Invalid email address.';
        header('Location: login.php');
        exit();
    }

    try {
        $conn = get_db();
        $stmt = $conn->prepare("
            SELECT u.*, r.role_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.email = ? AND u.is_deleted = 0
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['logged_in']   = true;
            $_SESSION['user_id']     = $user['id'];
            $_SESSION['email']       = $user['email'];
            $_SESSION['username']    = trim($user['first_name'] . ' ' . $user['last_name']);
            $_SESSION['role']        = $user['role_name'];
            $_SESSION['role_id']     = $user['role_id'];
            $_SESSION['department_id'] = $user['department_id'];

            switch ($user['role_name']) {
                case 'faculty':
                    header('Location: faculty/faculty_dashboard.php'); break;
                case 'department_head':
                    header('Location: dept_head/dept_dashboard.php'); break;
                case 'dean':
                    header('Location: admin/admin_dashboard.php'); break;
                case 'vpaa':
                    header('Location: vpaa/vpaa_dashboard.php'); break;
                default:
                    header('Location: faculty/faculty_dashboard.php');
            }
            exit();
        } else {
            $_SESSION['error'] = 'Invalid email or password.';
            header('Location: login.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Login error. Please try again.';
        header('Location: login.php');
        exit();
    }
} else {
    header('Location: login.php');
    exit();
}
?>
