<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}
restrict_to_role('dean');

$username = $_SESSION['username'] ?? 'Dean / Admin';
$role_display = "Dean's Panel";
$user_id = $_SESSION['user_id'];

$conn = get_db();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // FIX 1: Normalize course_code to UPPERCASE to avoid case-insensitive
    //         collation (utf8mb4_general_ci) treating "cs101" == "CS101"
    $course_code  = strtoupper(trim($_POST['course_code']  ?? ''));
    $course_title = trim($_POST['course_title'] ?? '');
    $college_id   = (int)($_POST['college_id']  ?? 0);
    $department_id = (int)($_POST['department_id'] ?? 0);

    if (empty($course_code) || empty($course_title) || $college_id === 0 || $department_id === 0) {
        $error = 'Please fill in all required fields.';
    } else {
        // FIX 2: Explicit pre-check with BINARY so the error message is clear
        //         and the user knows exactly what already exists
        $check = $conn->prepare("SELECT id FROM courses WHERE BINARY course_code = ?");
        $check->execute([$course_code]);

        if ($check->fetch()) {
            $error = 'Course code <strong>' . htmlspecialchars($course_code) . '</strong> already exists in the system. Please use a different code.';
        } else {
            try {
                // FIX 3: department_id column is NOT NULL (has FK constraint),
                //         so we must supply a valid value — no longer passing NULL
                $stmt = $conn->prepare("
                    INSERT INTO courses (course_code, course_title, college_id, department_id)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$course_code, $course_title, $college_id, $department_id]);
                header("Location: manage_courses.php?added=true");
                exit();
            } catch (PDOException $e) {
                // Catch any remaining DB-level duplicate (race condition safety net)
                if ($e->getCode() == 23000) {
                    $error = 'Course code <strong>' . htmlspecialchars($course_code) . '</strong> already exists in the system.';
                } else {
                    $error = 'Database Error: ' . $e->getMessage();
                }
            }
        }
    }
}

$colleges    = get_colleges();
$departments = $conn->query("SELECT id, department_name FROM departments ORDER BY department_name")->fetchAll(PDO::FETCH_ASSOC);

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

$unread_count  = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Course — SCC Syllabus Portal</title>
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
        <div class="scc-page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color:var(--text)">Add <span style="color:var(--primary)">Course</span></h4>
                <p style="font-size:0.85rem;color:var(--text-secondary);margin:0">Create a new course entry in the institutional catalog</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <!-- Notification Bell -->
                <div class="dropdown">
                    <div class="position-relative" style="cursor:pointer" data-bs-toggle="dropdown">
                        <i class="bi bi-bell fs-5" style="color:var(--text)"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="notif-badge"><?= $unread_count > 9 ? '9+' : $unread_count ?></span>
                        <?php endif; ?>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0"
                        style="width:340px;max-height:420px;overflow-y:auto;border-radius:var(--radius-md);background:var(--bg-card)">
                        <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom sticky-top"
                            style="background:var(--bg-card)">
                            <strong style="font-size:0.9rem;color:var(--text)">Notifications</strong>
                            <?php if ($unread_count > 0): ?>
                                <a href="?mark_read=1" class="text-decoration-none small" style="color:var(--primary)">Mark all read</a>
                            <?php endif; ?>
                        </li>
                        <?php if (empty($notifications)): ?>
                            <li class="px-3 py-4 text-center" style="color:var(--text-muted)">
                                <i class="bi bi-bell-slash fs-4 d-block mb-2 opacity-50"></i>
                                <span class="small">No notifications</span>
                            </li>
                        <?php else: foreach ($notifications as $n):
                            $color = get_notification_color($n['message']); ?>
                            <li class="border-bottom" style="<?= !$n['is_read'] ? 'background:var(--primary-light)' : '' ?>">
                                <a href="notifications.php?notif_id=<?= $n['id'] ?>" class="text-decoration-none d-block px-3 py-2">
                                    <p class="mb-0 small" style="color:var(--text)">
                                        <span class="<?= $color['text'] ?> fw-bold me-1"><?= $color['icon'] ?></span>
                                        <?= htmlspecialchars($n['message']) ?>
                                    </p>
                                    <span style="font-size:.7rem;color:var(--text-muted)">
                                        <?= date('M d, Y h:i A', strtotime($n['created_at'])) ?>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; endif; ?>
                        <li style="background:var(--bg-card);border-top:1px solid var(--border)">
                            <a href="notifications.php" class="d-block text-center text-decoration-none small fw-bold py-2"
                               style="color:var(--primary)">View all notifications</a>
                        </li>
                    </ul>
                </div>

                <a href="manage_courses.php" class="btn btn-light border fw-bold text-secondary rounded-pill px-4">
                    <i class="bi bi-arrow-left me-2"></i> Back to Catalog
                </a>
            </div>
        </div>

        <div class="scc-card animate-in" style="max-width:620px; margin:0 auto;">
            <div class="card-header border-0 bg-transparent p-4 pb-0 text-center">
                <h6 class="fw-bold mb-0" style="color:var(--text)">Course <span style="color:var(--primary)">Information</span></h6>
                <p class="small text-muted">Register a new course in the system</p>
            </div>
            <div class="card-body p-4 p-md-5">

                <?php if ($error): ?>
                    <div class="alert alert-danger border-0 shadow-sm mb-4 d-flex align-items-start animate-in"
                         style="border-radius:var(--radius-md)">
                        <i class="bi bi-exclamation-triangle-fill me-2 mt-1 flex-shrink-0"></i>
                        <span><?= $error ?></span>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <!-- Course Code -->
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-secondary">
                            Course Code <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            name="course_code"
                            class="form-control"
                            placeholder="e.g. CS101"
                            value="<?= htmlspecialchars($_POST['course_code'] ?? '') ?>"
                            style="text-transform:uppercase"
                            required
                        >
                        <div class="form-text">Will be saved in uppercase automatically.</div>
                    </div>

                    <!-- Course Title -->
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-secondary">
                            Course Title <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            name="course_title"
                            class="form-control"
                            placeholder="e.g. Introduction to Computing"
                            value="<?= htmlspecialchars($_POST['course_title'] ?? '') ?>"
                            required
                        >
                    </div>

                    <!-- Department (required — NOT NULL FK in DB) -->
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-secondary">
                            Department <span class="text-danger">*</span>
                        </label>
                        <select name="department_id" class="form-select" required>
                            <option value="">-- Select Department --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>"
                                    <?= (isset($_POST['department_id']) && $_POST['department_id'] == $dept['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['department_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- College -->
                    <div class="mb-5">
                        <label class="form-label fw-bold small text-secondary">
                            Assigned College <span class="text-danger">*</span>
                        </label>
                        <select name="college_id" class="form-select" required>
                            <option value="">-- Select College --</option>
                            <?php foreach ($colleges as $college): ?>
                                <option value="<?= $college['id'] ?>"
                                    <?= (isset($_POST['college_id']) && $_POST['college_id'] == $college['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($college['college_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold rounded-pill shadow-sm">
                            <i class="bi bi-plus-circle me-2"></i> Create Course Entry
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/common.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        }
    </script>
</body>
</html>