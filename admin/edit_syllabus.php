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

// Sidebar counts
$pending_review_count = (int) $conn->query("
    SELECT COUNT(DISTINCT sw.syllabus_id)
    FROM syllabus_workflow sw
    JOIN roles r ON sw.role_id = r.id
    WHERE r.role_name = 'dean' AND sw.action = 'Pending'
")->fetchColumn();

$reg_count = (int) $conn->query("SELECT COUNT(*) FROM users WHERE is_approved = 0 AND is_deleted = 0")->fetchColumn();
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
            <a href="syllabus_review.php" class="nav-link text-white p-3 rounded hover-effect">
                Syllabus Review
                <?php if ($pending_review_count > 0): ?>
                    <span class="badge bg-danger ms-1"><?= $pending_review_count ?></span>
                <?php endif; ?>
            </a>
            <a href="upload_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">Upload Syllabus</a>
            <a href="my_submissions.php" class="nav-link text-white active-nav-link p-3 rounded">My Submissions</a>
            <a href="shared_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">Shared Syllabus</a>

            <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">USER MANAGEMENT</div>
            <a href="registration_requests.php" class="nav-link text-white p-3 rounded hover-effect">
                Registration Requests
                <?php if ($reg_count > 0): ?>
                    <span class="badge bg-danger ms-1"><?= $reg_count ?></span>
                <?php endif; ?>
            </a>
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
                <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="width:320px;max-height:400px;overflow-y:auto;">
                    <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom sticky-top bg-white" style="z-index:11;">
                        <strong>Notifications</strong>
                        <?php if ($unread_count > 0): ?>
                            <a href="?id=<?= $syllabus_id ?>&mark_read=1" class="text-decoration-none small text-orange">Mark all read</a>
                        <?php endif; ?>
                    </li>
                    <?php if (empty($notifications)): ?>
                        <li class="px-3 py-3 text-center text-muted small">No notifications yet</li>
                    <?php else: foreach ($notifications as $n): 
                        $color = get_notification_color($n['message']); ?>
                        <li class="border-bottom <?= !$n['is_read'] ? 'bg-light' : '' ?>">
                            <a href="notifications.php?notif_id=<?= $n['id'] ?>" class="text-decoration-none text-dark d-block px-3 py-2">
                                <p class="mb-0 small">
                                    <span class="<?= $color['text'] ?> fw-bold me-1"><?= $color['icon'] ?></span>
                                    <span class="<?= $color['text'] ?>"><?= htmlspecialchars($n['message']) ?></span>
                                </p>
                                <span class="text-muted" style="font-size:.7rem;"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></span>
                            </a>
                        </li>
                    <?php endforeach; endif; ?>
                    <li class="dropdown-menu-sticky-footer">
                        <a href="notifications.php" class="d-block text-center text-orange text-decoration-none small fw-bold py-2">View all notifications</a>
                    </li>
                </ul>
            </div>
        </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-3 border-0 shadow-sm mb-4">
                    <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3 border-0 shadow-sm mb-4">
                    <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <div class="scc-card animate-in">
                        <div class="card-body p-4">
                            <form action="process_edit_syllabus.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="syllabus_id" value="<?= $syllabus_id ?>">

                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small">Course Code <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="course_code"
                                            value="<?= htmlspecialchars($syllabus['course_code']) ?>" required>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label fw-bold small">Course Title / Subject Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="course_title"
                                            value="<?= htmlspecialchars($syllabus['course_title']) ?>" required>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold small">Course Name</label>
                                    <input type="text" class="form-control" name="course"
                                        value="<?= htmlspecialchars($syllabus['course_name'] ?? '') ?>" placeholder="e.g. BS in Computer Science">
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small">Subject Type <span class="text-danger">*</span></label>
                                        <select class="form-select" name="subject_type" required>
                                            <option disabled>-- Select --</option>
                                            <?php
                                            $types = ['Institutional Subject', 'General Education (GE)', 'Core Subject', 'Professional Subjects', 'Mandatory / Elect Subject'];
                                            foreach ($types as $t): ?>
                                                <option value="<?= $t ?>" <?= ($syllabus['subject_type'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small">Semester <span class="text-danger">*</span></label>
                                        <select class="form-select" name="subject_semester" required>
                                            <option disabled>-- Select --</option>
                                            <?php foreach (['1st Semester', '2nd Semester', 'Summer'] as $sem): ?>
                                                <option value="<?= $sem ?>" <?= ($syllabus['semester'] ?? '') === $sem ? 'selected' : '' ?>><?= $sem ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small">Year Level <span class="text-danger">*</span></label>
                                        <select class="form-select" name="year_level" required>
                                            <option disabled>-- Select --</option>
                                            <?php foreach (['1st Year', '2nd Year', '3rd Year', '4th Year'] as $lvl): ?>
                                                <option value="<?= $lvl ?>" <?= ($syllabus['school_year'] ?? '') === $lvl ? 'selected' : '' ?>><?= $lvl ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="p-3 bg-light rounded-3 mb-4 border">
                                    <label class="form-label fw-bold small d-block mb-2">Current Document</label>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-file-earmark-pdf fs-4 text-danger"></i>
                                            <span class="small fw-bold text-dark"><?= htmlspecialchars(basename($syllabus['file_path'])) ?></span>
                                        </div>
                                        <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($syllabus['file_path'])) ?>"
                                           target="_blank" class="btn btn-sm btn-outline-primary fw-bold">View PDF</a>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold small">Replace File (PDF Only — optional)</label>
                                    <input type="file" class="form-control" name="pdf_file" accept=".pdf">
                                    <div class="form-text small">Leave blank to keep current file. Max 10MB.</div>
                                </div>

                                <div class="d-flex gap-2 pt-3 border-top">
                                    <button type="submit" class="btn btn-primary-scc px-4">Save Changes</button>
                                    <a href="my_submissions.php" class="btn btn-light border px-4">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="scc-card animate-in" style="animation-delay: 0.1s">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3"><i class="bi bi-shield-check me-2 text-primary"></i>Dean Access</h6>
                            <p class="small text-muted mb-3">You are editing a syllabus submitted under your account. These follow the standard institutional workflow.</p>
                            <ul class="list-unstyled small text-muted mb-0" style="line-height: 1.8">
                                <li><i class="bi bi-check2 text-success me-2"></i> Only Pending/Rejected files can be updated.</li>
                                <li><i class="bi bi-check2 text-success me-2"></i> Replaced files must be PDF.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/theme.js"></script>
</body>
</html>
