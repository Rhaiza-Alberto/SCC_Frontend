<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$user_id      = $_SESSION['user_id'];
$username     = $_SESSION['username'] ?? 'Admin User';
$role_display = "Dean's Panel";

$notifications = get_notifications($user_id, 10);
$unread_count  = count_unread_notifications($user_id);

if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: upload_syllabus.php');
    exit();
}

$conn = get_db();
$pending_review_count = (int) $conn->query("
    SELECT COUNT(DISTINCT sw.syllabus_id)
    FROM syllabus_workflow sw
    JOIN roles r ON sw.role_id = r.id
    WHERE r.role_name = 'dean' AND sw.action = 'Pending'
")->fetchColumn();

$reg_count = (int) $conn->query("SELECT COUNT(*) FROM users WHERE is_approved = 0 AND is_deleted = 0")->fetchColumn();

// Fetch courses for dropdown
$courses_stmt = $conn->prepare("SELECT * FROM courses ORDER BY course_code");
$courses_stmt->execute();
$course_list = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);

$success_message = $_SESSION['success_message'] ?? '';
$error_message   = $_SESSION['error_message']   ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Syllabus — SCC Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php $active_page = 'upload'; include '_sidebar.php'; ?>

    <main class="scc-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color:var(--text)">Upload <span style="color:var(--primary)">Syllabus</span></h4>
                <p style="font-size:0.85rem;color:var(--text-secondary);margin:0">Create and submit new syllabus for institutional review</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <div class="position-relative" style="cursor:pointer" data-bs-toggle="dropdown">
                        <i class="bi bi-bell fs-5" style="color:var(--text)"></i>
                        <?php if ($unread_count > 0): ?><span class="notif-badge"><?= $unread_count > 9 ? '9+' : $unread_count ?></span><?php endif; ?>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="width:340px;max-height:420px;overflow-y:auto;border-radius:var(--radius-md);background:var(--bg-card)">
                        <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom sticky-top" style="background:var(--bg-card)">
                            <strong style="font-size:0.9rem;color:var(--text)">Notifications</strong>
                            <?php if ($unread_count > 0): ?><a href="?mark_read=1" class="text-decoration-none small" style="color:var(--primary)">Mark all read</a><?php endif; ?>
                        </li>
                        <?php if (empty($notifications)): ?>
                            <li class="px-3 py-4 text-center" style="color:var(--text-muted)"><i class="bi bi-bell-slash fs-4 d-block mb-2 opacity-50"></i><span class="small">No notifications</span></li>
                        <?php else: foreach ($notifications as $n): $color = get_notification_color($n['message']); ?>
                            <li class="border-bottom" style="<?= !$n['is_read'] ? 'background:var(--primary-light)' : '' ?>">
                                <a href="notifications.php?notif_id=<?= $n['id'] ?>" class="text-decoration-none d-block px-3 py-2">
                                    <p class="mb-0 small" style="color:var(--text)"><span class="<?= $color['text'] ?> fw-bold me-1"><?= $color['icon'] ?></span><?= htmlspecialchars($n['message']) ?></p>
                                    <span style="font-size:.7rem;color:var(--text-muted)"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></span>
                                </a>
                            </li>
                        <?php endforeach; endif; ?>
                        <li style="background:var(--bg-card);border-top:1px solid var(--border)"><a href="notifications.php" class="d-block text-center text-decoration-none small fw-bold py-2" style="color:var(--primary)">View all notifications</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4 rounded-3 border-0 shadow-sm">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4 rounded-3 border-0 shadow-sm">
                <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="scc-card animate-in" style="max-width: 800px; margin: 0 auto;">
            <div class="card-header border-0 bg-transparent p-4 pb-0 text-center">
                <h6 class="fw-bold mb-0" style="color:var(--text)">Syllabus <span style="color:var(--primary)">Submission Form</span></h6>
                <p class="small text-muted">Please select the course and upload the corresponding PDF document</p>
            </div>
            <div class="card-body p-4 p-md-5">
                <form id="uploadForm" action="../faculty/process_upload.php" method="POST" enctype="multipart/form-data">
                    <div class="row g-4">
                        <div class="col-12">
                            <label class="form-label fw-bold small" style="color:var(--text-secondary)">Select Course <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <i class="bi bi-book position-absolute" style="left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
                                <select class="form-select ps-5" name="course_id" required style="padding:0.75rem 0.75rem 0.75rem 2.5rem;">
                                    <option value="" selected disabled>-- Choose Course --</option>
                                    <?php foreach($course_list as $course): ?>
                                        <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['course_code']) ?> — <?= htmlspecialchars($course['course_title']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-bold small" style="color:var(--text-secondary)">Subject Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="subject_type" required style="padding:0.75rem;">
                                <option selected disabled value="">Select Type</option>
                                <option>Institutional Subject</option>
                                <option>General Education (GE)</option>
                                <option>Core Subject</option>
                                <option>Professional Subjects</option>
                                <option>Mandatory / Elect Subject</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small" style="color:var(--text-secondary)">Semester <span class="text-danger">*</span></label>
                            <select class="form-select" name="subject_semester" required style="padding:0.75rem;">
                                <option selected disabled value="">Select Semester</option>
                                <option>1st Semester</option>
                                <option>2nd Semester</option>
                                <option>Summer</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small" style="color:var(--text-secondary)">Year Level <span class="text-danger">*</span></label>
                            <select class="form-select" name="year_level" required style="padding:0.75rem;">
                                <option selected disabled value="">Select Year</option>
                                <option>1st Year</option>
                                <option>2nd Year</option>
                                <option>3rd Year</option>
                                <option>4th Year</option>
                            </select>
                        </div>
                        
                        <div class="col-md-12">
                             <label class="form-label fw-bold small" style="color:var(--text-secondary)">School Year <span class="text-danger">*</span></label>
                             <input type="text" class="form-control" name="school_year" placeholder="e.g. 2023-2024" required value="<?= get_current_school_year() ?>" style="padding:0.75rem;">
                        </div>

                        <div class="col-12 mt-4">
                            <div class="upload-zone p-4 text-center" style="border: 2px dashed var(--border); border-radius: var(--radius-md); background: var(--bg-card); transition: all 0.3s ease; position: relative;">
                                <i class="bi bi-cloud-arrow-up fs-1" style="color:var(--primary); opacity:0.6;"></i>
                                <p class="mb-2 mt-2 small fw-bold" style="color:var(--text)">Click to browse or drag and drop</p>
                                <p class="mb-3 text-muted" style="font-size:0.7rem;">Only PDF files are supported. Max size: 10MB</p>
                                <input type="file" class="form-control" name="pdf_file" id="pdfFile" accept=".pdf" required style="opacity: 0; position: absolute; top: 0; left: 0; width: 100%; height: 100%; cursor: pointer;">
                                <div id="fileInfo" class="mt-2 small fw-bold text-success" style="display:none;"></div>
                            </div>
                        </div>
                        <div class="col-12 mt-4 pt-2">
                            <button type="submit" class="btn btn-primary-scc btn-lg w-100 fw-bold rounded-3 py-3">
                                <i class="bi bi-send-check me-2"></i> Submit for Institutional Review
                            </button>
                        </div>
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
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Confirm Submission',
                text: "Are you sure you want to submit this syllabus for review?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ff8800',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, submit it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });

        // Show selected filename
        document.getElementById('pdfFile').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : '';
            const infoDiv = document.getElementById('fileInfo');
            if (fileName) {
                infoDiv.textContent = 'Selected: ' + fileName;
                infoDiv.style.display = 'block';
            } else {
                infoDiv.style.display = 'none';
            }
        });
    </script>
</body>
</html>