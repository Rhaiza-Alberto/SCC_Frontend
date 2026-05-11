<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$username = $_SESSION['username'] ?? 'Dean / Admin';
$role_display = "Dean's Panel";
$user_id = $_SESSION['user_id'];

$conn = get_db();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_code = trim($_POST['course_code'] ?? '');
    $course_title = trim($_POST['course_title'] ?? '');
    $college_id = (int)($_POST['college_id'] ?? 0);

    if (empty($course_code) || empty($course_title) || $college_id === 0) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO courses (course_code, course_title, college_id) VALUES (?, ?, ?)");
            $stmt->execute([$course_code, $course_title, $college_id]);
            $success = 'Course successfully added!';
            header("Location: manage_courses.php?added=true");
            exit();
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

$colleges = get_colleges();

// Counts for sidebar
$pending_review_count = (int) $conn->query("
    SELECT COUNT(DISTINCT sw.syllabus_id)
    FROM syllabus_workflow sw
    JOIN roles r ON sw.role_id = r.id
    WHERE r.role_name = 'dean' AND sw.action = 'Pending'
")->fetchColumn();

$reg_count = (int) $conn->query("
    SELECT COUNT(*) FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE r.role_name = 'faculty' AND u.is_approved = 0 AND u.is_deleted = 0
")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Course - SCC-CCS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .text-orange { color: #ff8800 !important; }
        .btn-orange { background-color: #ff8800 !important; color: white !important; border: none; }
        .btn-orange:hover { background-color: #e67a00 !important; }
    </style>
</head>
<body class="bg-light">
    <div class="d-flex">
        <!-- Sidebar (Omitted for brevity, but should match manage_courses.php) -->
        <div class="sidebar sidebar-premium text-white p-2 min-vh-100 d-flex flex-column" style="width:260px; position:fixed; z-index:1100;">
            <div class="text-center mb-3 mt-2">
                <img src="../css/logo.png" alt="CCS Logo" class="rounded-circle mb-2" style="width:80px;height:80px;border:2px solid rgba(255,136,0,.5);padding:3px;">
                <h5 class="font-serif fw-bold text-orange mb-0"><?= $role_display ?></h5>
                <p class="text-white-50 small fw-bold mb-0" style="font-size:.75rem;"><?= htmlspecialchars($username) ?></p>
            </div>
            <nav class="nav flex-column gap-2 mb-auto">
                <a href="admin_dashboard.php" class="nav-link text-white p-3 rounded hover-effect">Dashboard</a>
                <a href="manage_courses.php" class="nav-link text-white p-3 rounded active-nav-link">Manage Courses</a>
                <!-- ... other links ... -->
                <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
            </nav>
        </div>

        <div class="main-content flex-grow-1 p-5" style="margin-left:260px;">
            <div class="mb-5">
                <a href="manage_courses.php" class="btn btn-outline-dark rounded-pill px-3 py-1 small mb-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to List
                </a>
                <h2 class="text-orange font-serif fw-bold mb-0">Add New Course</h2>
                <p class="text-muted">Register a new course in the system</p>
            </div>

            <div class="card premium-card p-5 shadow-sm border-0 mx-auto" style="max-width: 600px;">
                <?php if ($error): ?>
                    <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4 small"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Course Code</label>
                        <input type="text" name="course_code" class="form-control" placeholder="e.g. CS101" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Course Title</label>
                        <input type="text" name="course_title" class="form-control" placeholder="e.g. Introduction to Computing" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small">College</label>
                        <select name="college_id" class="form-select" required>
                            <option value="">-- Select College --</option>
                            <?php foreach ($colleges as $college): ?>
                                <option value="<?= $college['id'] ?>"><?= htmlspecialchars($college['college_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-orange btn-lg fw-bold rounded-pill">Create Course</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
