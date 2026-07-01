<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

ensure_role_in_session();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$role_display = 'Faculty Panel';

$syllabus_id = (int) ($_GET['id'] ?? 0);
if (!$syllabus_id) {
    header('Location: my_submissions.php');
    exit();
}

if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: edit_syllabus.php?id=' . $syllabus_id);
    exit();
}

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
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
$unread_count = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);

$rejection = null;
if ($syllabus['status'] === 'Rejected') {
    $reject_stmt = $conn->prepare("
        SELECT comment, action_at 
        FROM syllabus_workflow 
        WHERE syllabus_id = ? AND action = 'Rejected' 
        ORDER BY action_at DESC 
        LIMIT 1
    ");
    $reject_stmt->execute([$syllabus_id]);
    $rejection = $reject_stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Submission - SCC-CCS Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .text-orange {
            color: #ff8800 !important;
        }

        .notif-dot {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 10px;
            height: 10px;
            background: #dc3545;
            border-radius: 50%;
            border: 2px solid #fff;
        }
    </style>
</head>
<body class="bg-light">
    <?php include '_sidebar.php'; ?>

    <main class="scc-main">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-0" style="color:var(--text)">Edit <span style="color:var(--primary)">Syllabus</span></h4>
                    <p class="text-muted small mb-0">Modify submission details and update documents</p>
                </div>
                <div class="d-flex align-items-center gap-3" id="navbarActions">
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
                <?php if ($rejection && !empty($rejection['comment'])): ?>
                    <div class="col-12 mb-4">
                        <div class="alert alert-warning border-0 shadow-sm rounded-3 d-flex align-items-start gap-3 p-3">
                            <i class="bi bi-exclamation-triangle-fill fs-4 text-warning mt-1"></i>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">Revision Required</h6>
                                <p class="mb-1 text-secondary small">Rejection Reason: <strong><?= htmlspecialchars($rejection['comment']) ?></strong></p>
                                <span class="text-muted" style="font-size: 0.7rem;"><i class="bi bi-clock"></i> Declined on <?= date('M d, Y h:i A', strtotime($rejection['action_at'])) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

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
                                                <option value="<?= $lvl ?>" <?= ($syllabus['year_level'] ?? '') === $lvl ? 'selected' : '' ?>><?= $lvl ?></option>
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
                                        <a href="view_syllabus.php?file=<?= urlencode(basename($syllabus['file_path'])) ?>"
                                           target="_blank" class="btn btn-sm btn-outline-primary fw-bold">View PDF</a>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold small">Replace File (PDF Only — optional)</label>
                                    <input type="file" class="form-control" name="pdf_file" accept=".pdf">
                                    <div class="form-text small">Leave blank to keep the current file. Max size: 10MB.</div>
                                </div>

                                <div class="d-flex gap-2 pt-3 border-top">
                                    <button type="button" onclick="confirmSyllabusEdit()" class="btn btn-primary-scc px-4">Save Changes</button>
                                    <a href="my_submissions.php" class="btn btn-light border px-4">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="scc-card animate-in" style="animation-delay: 0.1s">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Submission Tips</h6>
                            <ul class="list-unstyled small text-muted mb-0" style="line-height: 1.8">
                                <li><i class="bi bi-check2 text-success me-2"></i> Ensure the course code matches exactly.</li>
                                <li><i class="bi bi-check2 text-success me-2"></i> PDF files should be clear and readable.</li>
                                <li><i class="bi bi-check2 text-success me-2"></i> Only Pending or Rejected syllabi can be edited.</li>
                                <li><i class="bi bi-check2 text-success me-2"></i> Once approved, editing is disabled.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/common.js"></script>
    <script>
        function confirmSyllabusEdit() {
            Swal.fire({
                title: 'Confirm Changes',
                text: "Are you sure you want to update this syllabus submission?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: 'var(--primary)',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Save Changes'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.querySelector('form').submit();
                }
            });
        }
    </script>
</body>
</html>