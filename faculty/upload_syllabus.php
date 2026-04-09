 <?php
/**
 * upload_syllabus.php
 * Faculty syllabus upload form — reads notification data from DB.
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
$role_display = 'Faculty Panel';

// Handle mark-all-read
if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: upload_syllabus.php');
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
    <title>Upload Syllabus - SCC-CCS Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .text-orange  { color: #ff8800 !important; }
        .btn-orange   { background-color: #ff8800 !important; color: white !important; border: none; }
        .btn-orange:hover { background-color: #e67a00 !important; color: white !important; }
        .stat-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
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
            <p class="text-white-50 small fw-bold mb-0" style="font-size:.75rem;">
                <?= htmlspecialchars($username) ?>
            </p>
        </div>
        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
        <a href="faculty_dashboard.php" class="nav-link text-white p-3 rounded hover-effect">Dashboard</a>

        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
        <a href="upload_syllabus.php"  class="nav-link text-white active-nav-link p-3 rounded">Upload Syllabus</a>
        <a href="my_submissions.php"   class="nav-link text-white p-3 rounded hover-effect">My Submissions</a>
        <a href="shared_syllabus.php"  class="nav-link text-white p-3 rounded hover-effect">Shared Syllabus</a>

        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
        <a href="profile.php"   class="nav-link text-white p-3 rounded hover-effect">Profile</a>
        <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 p-5" style="margin-left:260px;">

        <div class="d-flex justify-content-between align-items-center mb-5">
            <h3 class="text-orange font-serif fw-bold mb-0">Upload Syllabus</h3>

            <!-- Notification Bell (fixed — now has proper Bootstrap dropdown wrapper) -->
            <div class="dropdown">
                        <div class="position-relative" style="cursor:pointer;" data-bs-toggle="dropdown">
                        <i class="bi bi-bell fs-4 text-dark"></i>
                        <?php if ($unread_count > 0): ?>
                        <span class="notif-dot"></span>
                        <?php endif; ?>
                        </div>

                        <ul class="dropdown-menu dropdown-menu-end shadow"
                        style="width:320px;max-height:400px;overflow-y:auto;">

                        <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom">
                        <strong>Notifications</strong>
                        <?php if ($unread_count > 0): ?>
                        <a href="?mark_read=1" class="text-decoration-none small text-orange">
                            Mark all read
                        </a>
                        <?php endif; ?>
                        </li>

        <?php if (empty($notifications)): ?>
            <li class="px-3 py-3 text-center text-muted small">
                No notifications yet
            </li>
        <?php else: ?>

            <?php foreach ($notifications as $n):
                $color = get_notification_color($n['message']); ?>
                
                <li class="px-3 py-2 border-bottom <?= !$n['is_read'] ? 'bg-light' : '' ?>">
                    <p class="mb-0 small">
                        <span class="<?= $color['text'] ?> fw-bold me-1">
                            <?= $color['icon'] ?>
                        </span>
                        <span class="<?= $color['text'] ?>">
                            <?= htmlspecialchars($n['message']) ?>
                        </span>
                    </p>

                    <span class="text-muted" style="font-size:.7rem;">
                        <?= date('M d, Y h:i A', strtotime($n['created_at'])) ?>
                    </span>
                </li>

            <?php endforeach; ?>

        <?php endif; ?>
        <a href="notifications.php" 
   class="d-block text-center text-orange text-decoration-none small fw-bold py-2">
    View all notifications
</a>
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
            <form action="process_upload.php" method="POST" enctype="multipart/form-data">

                <!-- Course Code -->
                <div class="mb-3">
                    <label for="courseCode" class="form-label fw-bold small">Course Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="courseCode" name="course_code"
                           placeholder="E.G., CS101" required>
                </div>

                <!-- Course Title / Subject Name -->
                <div class="mb-3">
                    <label for="courseTitle" class="form-label fw-bold small">Course Title / Subject Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="courseTitle" name="course_title"
                           placeholder="E.G., Computer Programming 1" required>
                </div>

                <!-- Course -->
                <div class="mb-3">
                    <label for="course" class="form-label fw-bold small">Course</label>
                    <input type="text" class="form-control" id="course" name="course"
                           placeholder="E.G., Computer Science">
                </div>

                <!-- Subject Type -->
                <div class="mb-3">
                    <label for="subjectType" class="form-label fw-bold small">Subject Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="subjectType" name="subject_type" required>
                        <option selected disabled>-- Select Subject Type --</option>
                        <option value="Institutional Subject">Institutional Subject</option>
                        <option value="General Education (GE)">General Education (GE)</option>
                        <option value="Core Subject">Core Subject</option>
                        <option value="Professional Subjects">Professional Subjects</option>
                        <option value="Mandatory / Elect Subject">Mandatory / Elect Subject</option>
                    </select>
                </div>

                <!-- Subject Semester -->
                <div class="mb-3">
                    <label for="subjectSemester" class="form-label fw-bold small">Subject Semester <span class="text-danger">*</span></label>
                    <select class="form-select" id="subjectSemester" name="subject_semester" required>
                        <option selected disabled>-- Select Semester --</option>
                        <option value="1st Semester">1st Semester</option>
                        <option value="2nd Semester">2nd Semester</option>
                        <option value="Summer">Summer</option>
                    </select>
                </div>

                <!-- Year Level -->
                <div class="mb-3">
                    <label for="yearLevel" class="form-label fw-bold small">Year Level <span class="text-danger">*</span></label>
                    <select class="form-select" id="yearLevel" name="year_level" required>
                        <option selected disabled>-- Select Year Level --</option>
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                    </select>
                </div>

                <!-- Upload File -->
                <div class="mb-4">
                    <label for="pdfFile" class="form-label fw-bold small">Upload File (PDF Only) <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" id="pdfFile" name="pdf_file" accept=".pdf" required>
                    <small class="text-muted">Maximum file size: 10MB</small>
                </div>

                <!-- Submit Button -->
                <div class="d-grid">
                    <button type="submit" class="btn btn-login btn-lg fw-bold">Upload Syllabus</button>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>