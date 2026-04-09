<?php
/**
 * admin/edit_syllabus.php
 * Allows deans/admins to edit their OWN pending or rejected syllabus submission.
 */
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

ensure_role_in_session();

$user_id      = $_SESSION['user_id'];
$username     = $_SESSION['username'] ?? 'User';
$role_display = "Dean's Panel";

$syllabus_id = (int) ($_GET['id'] ?? 0);
if (!$syllabus_id) {
    header('Location: my_submissions.php');
    exit();
}

// Handle mark-all-read
if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: edit_syllabus.php?id=' . $syllabus_id);
    exit();
}

// Fetch the syllabus — must belong to this user and be Pending or Rejected
$conn = get_db();
$stmt = $conn->prepare("
    SELECT s.*, c.course_code AS matched_code, c.course_title AS matched_title
    FROM syllabus s
    LEFT JOIN courses c ON s.course_id = c.id
    WHERE s.id = ? AND s.uploaded_by = ? AND s.status IN ('Pending', 'Rejected')
");
$stmt->execute([$syllabus_id, $user_id]);
$syllabus = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$syllabus) {
    $_SESSION['error_message'] = "Submission not found or cannot be edited.";
    header('Location: my_submissions.php');
    exit();
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message   = $_SESSION['error_message']   ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$unread_count  = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Submission - SCC-CCS Dean's Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .text-orange  { color: #ff8800 !important; }
        .notif-dot { position:absolute;top:2px;right:2px;width:10px;height:10px;
                     background:#dc3545;border-radius:50%;border:2px solid #fff; }
    </style>
</head>
<body class="bg-light">
<div class="d-flex">

    <!-- Sidebar -->
    <div class="sidebar sidebar-premium text-white p-2 min-vh-100 d-flex flex-column"
         style="width:260px; position:fixed; z-index:1100;">
        <div class="text-center mb-3 mt-2">
            <img src="../css/logo.png" alt="CCS Logo" class="rounded-circle mb-2"
                 style="width:80px;height:80px;border:2px solid rgba(255,136,0,.5);padding:3px;">
            <h5 class="font-serif fw-bold text-orange mb-0"><?= $role_display ?></h5>
            <p class="text-white-50 small fw-bold mb-0" style="font-size:.75rem;"><?= htmlspecialchars($username) ?></p>
        </div>
        <nav class="nav flex-column gap-2 mb-auto">
            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
            <a href="admin_dashboard.php" class="nav-link text-white p-3 rounded hover-effect">Dashboard</a>

            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
            <a href="syllabus_review.php" class="nav-link text-white p-3 rounded hover-effect">Syllabus Review</a>
            <a href="upload_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">Upload Syllabus</a>
            <a href="my_submissions.php" class="nav-link text-white active-nav-link p-3 rounded">My Submissions</a>
            <a href="shared_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">Shared Syllabus</a>

            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">USER MANAGEMENT</div>
            <a href="registration_requests.php" class="nav-link text-white p-3 rounded hover-effect">Registration Requests</a>
            <a href="manage_user.php" class="nav-link text-white p-3 rounded hover-effect">Manage Users</a>
            <a href="add_user.php" class="nav-link text-white p-3 rounded hover-effect">Add User</a>

            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
            <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect">Profile</a>
            <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 p-5" style="margin-left:260px;">

        <div class="d-flex justify-content-between align-items-center mb-5">
            <h3 class="text-orange font-serif fw-bold mb-0">Edit Submission</h3>

            <!-- Notification Bell -->
            <div class="dropdown">
                <div class="position-relative" style="cursor:pointer;" data-bs-toggle="dropdown">
                    <i class="bi bi-bell fs-4 text-dark"></i>
                    <?php if ($unread_count > 0): ?>
                        <span class="notif-dot"></span>
                    <?php endif; ?>
                </div>
                <ul class="dropdown-menu dropdown-menu-end shadow" style="width:320px;max-height:400px;overflow-y:auto;">
                    <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom">
                        <strong>Notifications</strong>
                        <?php if ($unread_count > 0): ?>
                            <a href="?mark_read=1" class="text-decoration-none small text-orange">Mark all read</a>
                        <?php endif; ?>
                    </li>
                    <?php if (empty($notifications)): ?>
                        <li class="px-3 py-3 text-center text-muted small">No notifications yet</li>
                    <?php else: ?>
                        <?php foreach ($notifications as $n):
                            $color = get_notification_color($n['message']); ?>
                            <li class="border-bottom <?= !$n['is_read'] ? 'bg-light' : '' ?>">
                                <a href="notifications.php?notif_id=<?= $n['id'] ?>" class="d-block px-3 py-2 text-decoration-none">
                                <p class="mb-0 small">
                                    <span class="<?= $color['text'] ?> fw-bold me-1"><?= $color['icon'] ?></span>
                                    <span class="<?= $color['text'] ?>"><?= htmlspecialchars($n['message']) ?></span>
                                </p>
                                <span class="text-muted" style="font-size:.7rem;">
                                    <?= date('M d, Y h:i A', strtotime($n['created_at'])) ?>
                                </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <li class="border-top">
                        <a href="notifications.php" class="d-block text-center text-orange text-decoration-none small fw-bold py-2">
                            View all notifications
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4">
                <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card premium-card shadow-sm p-5 bg-white mx-auto" style="max-width:800px;">
            <p class="text-muted small mb-4">
                Update your syllabus details below.
            </p>

            <form action="process_edit_syllabus.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="syllabus_id" value="<?= $syllabus_id ?>">

                <!-- Course Code -->
                <div class="mb-3">
                    <label class="form-label fw-bold small">Course Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="course_code"
                           value="<?= htmlspecialchars($syllabus['course_code']) ?>" required>
                </div>

                <!-- Course Title -->
                <div class="mb-3">
                    <label class="form-label fw-bold small">Course Title / Subject Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="course_title"
                           value="<?= htmlspecialchars($syllabus['course_title']) ?>" required>
                </div>

                <!-- Course Name -->
                <div class="mb-3">
                    <label class="form-label fw-bold small">Course</label>
                    <input type="text" class="form-control" name="course"
                           value="<?= htmlspecialchars($syllabus['course_name'] ?? '') ?>">
                </div>

                <!-- Subject Type -->
                <div class="mb-3">
                    <label class="form-label fw-bold small">Subject Type <span class="text-danger">*</span></label>
                    <select class="form-select" name="subject_type" required>
                        <option disabled>-- Select Subject Type --</option>
                        <?php
                        $types = [
                            'Institutional Subject',
                            'General Education (GE)',
                            'Core Subject',
                            'Professional Subjects',
                            'Mandatory / Elect Subject',
                        ];
                        foreach ($types as $t): ?>
                            <option value="<?= $t ?>" <?= ($syllabus['subject_type'] ?? '') === $t ? 'selected' : '' ?>>
                                <?= $t ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Semester -->
                <div class="mb-3">
                    <label class="form-label fw-bold small">Subject Semester <span class="text-danger">*</span></label>
                    <select class="form-select" name="subject_semester" required>
                        <option disabled>-- Select Semester --</option>
                        <?php foreach (['1st Semester', '2nd Semester', 'Summer'] as $sem): ?>
                            <option value="<?= $sem ?>" <?= ($syllabus['semester'] ?? '') === $sem ? 'selected' : '' ?>>
                                <?= $sem ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Year Level -->
                <div class="mb-3">
                    <label class="form-label fw-bold small">Year Level <span class="text-danger">*</span></label>
                    <select class="form-select" name="year_level" required>
                        <option disabled>-- Select Year Level --</option>
                        <?php
                        $levels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
                        foreach ($levels as $lvl): ?>
                            <option value="<?= $lvl ?>" <?= ($syllabus['year_level'] ?? '') === $lvl ? 'selected' : '' ?>>
                                <?= $lvl ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Current File -->
                <div class="mb-3">
                    <label class="form-label fw-bold small">Current File</label>
                    <div class="d-flex align-items-center gap-2">
                        <a href="view_syllabus.php?file=<?= urlencode(basename($syllabus['file_path'])) ?>"
                           target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-file-earmark-pdf me-1 text-orange"></i>View Current PDF
                        </a>
                    </div>
                </div>

                <!-- Replace File (optional) -->
                <div class="mb-4">
                    <label class="form-label fw-bold small">Replace File (PDF Only — optional)</label>
                    <input type="file" class="form-control" name="pdf_file" accept=".pdf">
                    <small class="text-muted">Leave blank to keep the existing file. Maximum file size: 10MB.</small>
                </div>

                <!-- Buttons -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-orange btn-lg fw-bold rounded-pill">
                        <i class="bi bi-save me-2"></i>Save Changes
                    </button>
                    <a href="my_submissions.php" class="btn btn-outline-secondary btn-lg rounded-pill">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
