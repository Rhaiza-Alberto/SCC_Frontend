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

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header("Location: manage_courses.php");
    exit();
}

// Fetch Course
$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    header("Location: manage_courses.php");
    exit();
}

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
            $stmt = $conn->prepare("UPDATE courses SET course_code = ?, course_title = ?, college_id = ? WHERE id = ?");
            $stmt->execute([$course_code, $course_title, $college_id, $id]);
            header("Location: manage_courses.php?updated=true");
            exit();
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

$colleges = get_colleges();

// Sidebar counts
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
    <title>Edit Course — SCC Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php $active_page = 'courses'; include '_sidebar.php'; ?>

    <main class="scc-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color:var(--text)">Edit <span style="color:var(--primary)">Course</span></h4>
                <p style="font-size:0.85rem;color:var(--text-secondary);margin:0">Update existing course information in the institutional catalog</p>
            </div>
            <div>
                <a href="manage_courses.php" class="btn btn-light border fw-bold text-secondary rounded-pill px-4">
                    <i class="bi bi-arrow-left me-2"></i> Back to Catalog
                </a>
            </div>
        </div>

        <div class="scc-card animate-in" style="max-width: 600px; margin: 0 auto;">
            <div class="card-header border-0 bg-transparent p-4 pb-0 text-center">
                <h6 class="fw-bold mb-0" style="color:var(--text)">Course <span style="color:var(--primary)">Catalog Update</span></h6>
                <p class="small text-muted">Modify existing course parameters below</p>
            </div>
            <div class="card-body p-4 p-md-5">
                <?php if ($error): ?>
                    <div class="alert alert-danger border-0 shadow-sm mb-4 d-flex align-items-center animate-in" style="border-radius:var(--radius-md)">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-secondary">Course Code <span class="text-danger">*</span></label>
                        <input type="text" name="course_code" class="form-control" value="<?= htmlspecialchars($course['course_code']) ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-secondary">Course Title <span class="text-danger">*</span></label>
                        <input type="text" name="course_title" class="form-control" value="<?= htmlspecialchars($course['course_title']) ?>" required>
                    </div>
                    <div class="mb-5">
                        <label class="form-label fw-bold small text-secondary">Assigned College <span class="text-danger">*</span></label>
                        <select name="college_id" class="form-select" required>
                            <option value="">-- Select College --</option>
                            <?php foreach ($colleges as $college): ?>
                                <option value="<?= $college['id'] ?>" <?= ($course['college_id'] == $college['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($college['college_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold rounded-pill shadow-sm">
                            <i class="bi bi-check2-circle me-2"></i> Update Course Entry
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/common.js"></script>
<script>
    function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('active');}
</script>
