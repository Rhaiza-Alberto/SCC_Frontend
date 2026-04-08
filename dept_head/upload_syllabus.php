 <?php
/**
 * dept_head/upload_syllabus.php
 * Upload Syllabus — dept_head version, posts to ../faculty/process_upload.php
 * Frontend matches faculty/upload_syllabus.php design.
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
$role_display = 'Dept Head Panel';

if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: upload_syllabus.php');
    exit();
}

// Flash messages from process_upload.php
$success_message = $_SESSION['upload_success'] ?? '';
$error_message   = $_SESSION['upload_error']   ?? '';
unset($_SESSION['upload_success'], $_SESSION['upload_error']);

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
        /* ── Colour tokens ── */
        :root {
            --orange:       #ff8800;
            --orange-dark:  #e67a00;
            --orange-light: rgba(255,136,0,.08);
        }
        .text-orange  { color: var(--orange) !important; }
        .btn-orange   { background: var(--orange) !important; color: #fff !important; border: none; }
        .btn-orange:hover { background: var(--orange-dark) !important; }
        .notif-dot    { position:absolute; top:2px; right:2px; width:10px; height:10px;
                        background:#dc3545; border-radius:50%; border:2px solid #fff; }

        /* ── Upload drop-zone ── */
        .drop-zone {
            border: 2.5px dashed rgba(255,136,0,.45);
            border-radius: 16px;
            background: var(--orange-light);
            cursor: pointer;
            transition: border-color .25s, background .25s;
            padding: 2.5rem 1rem;
            text-align: center;
        }
        .drop-zone.dragover,
        .drop-zone:hover {
            border-color: var(--orange);
            background: rgba(255,136,0,.14);
        }
        .drop-zone .dz-icon { font-size: 3rem; color: var(--orange); opacity: .7; }
        .drop-zone .dz-label { font-size: .95rem; color: #555; margin-top: .5rem; }
        .drop-zone .dz-hint  { font-size: .78rem; color: #aaa; margin-top: .25rem; }

        /* ── File chip ── */
        #fileChip {
            display: none;
            align-items: center;
            background: rgba(255,136,0,.1);
            border: 1px solid rgba(255,136,0,.35);
            border-radius: 50px;
            padding: .35rem .9rem;
            font-size: .85rem;
            font-weight: 600;
            color: var(--orange);
            gap: .5rem;
        }
        #fileChip .remove-file {
            cursor: pointer; font-size: 1rem; opacity: .6;
        }
        #fileChip .remove-file:hover { opacity: 1; }

        /* ── Form labels ── */
        .form-label.required::after {
            content: ' *';
            color: #dc3545;
        }

        /* ── Step indicator ── */
        .step-circle {
            width: 36px; height: 36px; border-radius: 50%;
            background: var(--orange-light);
            border: 2px solid rgba(255,136,0,.35);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .85rem; color: var(--orange);
            flex-shrink: 0;
        }

        /* ── Card polish ── */
        .upload-card {
            border: 0;
            border-radius: 20px;
            box-shadow: 0 4px 24px rgba(0,0,0,.07);
        }
        .section-divider {
            border: 0;
            border-top: 1.5px dashed rgba(255,136,0,.2);
            margin: 1.75rem 0;
        }
    </style>
</head>
<body class="bg-light">
<div class="d-flex">

    <!-- ── Sidebar ── -->
    <div class="sidebar sidebar-premium text-white p-2 min-vh-100 d-flex flex-column"
         style="width:260px; position:fixed; z-index:1100;">
        <div class="text-center mb-3 mt-2">
            <img src="../css/logo.png" alt="CCS Logo" class="rounded-circle mb-2"
                 style="width:80px;height:80px;border:2px solid rgba(255,136,0,.5);padding:3px;">
            <h5 class="font-serif fw-bold text-orange mb-0"><?= $role_display ?></h5>
            <p class="text-white-50 small fw-bold mb-0" style="font-size:.75rem;"><?= htmlspecialchars($username) ?></p>
        </div>
        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
        <a href="dept_dashboard.php"        class="nav-link text-white p-3 rounded hover-effect">Dashboard</a>
        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
        <a href="syllabus_review.php"       class="nav-link text-white p-3 rounded hover-effect">Syllabus Review</a>
        <a href="upload_syllabus.php"       class="nav-link text-white active-nav-link p-3 rounded">Upload Syllabus</a>
        <a href="my_submissions.php"        class="nav-link text-white p-3 rounded hover-effect">My Submissions</a>
        <a href="shared_syllabus.php"       class="nav-link text-white p-3 rounded hover-effect">Shared Syllabus</a>
        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">ACCOUNT MANAGEMENT</div>
        <a href="registration_requests.php" class="nav-link text-white p-3 rounded hover-effect">Registration Requests</a>
        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
        <a href="profile.php"               class="nav-link text-white p-3 rounded hover-effect">Profile</a>
        <a href="../logout.php"             class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
    </div>

    <!-- ── Main Content ── -->
    <div class="main-content flex-grow-1 p-5" style="margin-left:260px;">

        <!-- Top bar -->
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h3 class="text-orange font-serif fw-bold mb-0">Upload Syllabus</h3>
                <p class="text-muted small mb-0">Submit a new course syllabus for review</p>
            </div>
            <!-- Notification Bell -->
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
    </ul>
</div>

        <!-- Flash messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4 shadow-sm rounded-3" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm rounded-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Info banner -->
        <div class="alert border-0 shadow-sm mb-5 d-flex align-items-center p-3 rounded-3"
             style="background:rgba(255,136,0,.08);">
            <div class="rounded-circle p-2 me-3 d-flex align-items-center justify-content-center"
                 style="width:45px;height:45px;background:var(--orange);flex-shrink:0;">
                <i class="bi bi-info-lg text-white fs-5"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-1 text-orange">Submission Guidelines</h6>
                <p class="mb-0 small text-muted">
                    Uploaded syllabi will be reviewed by the Dean, then by the VPAA before final approval.
                    PDF only, max 10 MB.
                </p>
            </div>
        </div>

        <!-- Upload Card -->
        <div class="card upload-card p-5 bg-white mx-auto" style="max-width:820px;">

            <form action="../faculty/process_upload.php" method="POST" enctype="multipart/form-data"
                  id="uploadForm" novalidate>

                <!-- ── Section 1: Course Information ── -->
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="step-circle">1</div>
                    <div>
                        <h5 class="font-serif fw-bold mb-0 text-dark">Course Information</h5>
                        <p class="text-muted small mb-0">Identify the course this syllabus belongs to</p>
                    </div>
                </div>

                <div class="row g-3 mb-2">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small required">Course Code</label>
                        <input type="text" class="form-control" name="course_code"
                               placeholder="e.g. CS101" required maxlength="50">
                        <div class="invalid-feedback">Course code is required.</div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold small required">Course Title / Subject Name</label>
                        <input type="text" class="form-control" name="course_title"
                               placeholder="e.g. Computer Programming 1" required maxlength="255">
                        <div class="invalid-feedback">Course title is required.</div>
                    </div>
                </div>

                <div class="row g-3 mb-2">
                    <div class="col-md-12">
                        <label class="form-label fw-semibold small">Course / Program</label>
                        <input type="text" class="form-control" name="course"
                               placeholder="e.g. Bachelor of Science in Computer Science" maxlength="255">
                    </div>
                </div>

                <hr class="section-divider">

                <!-- ── Section 2: Schedule Details ── -->
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="step-circle">2</div>
                    <div>
                        <h5 class="font-serif fw-bold mb-0 text-dark">Schedule Details</h5>
                        <p class="text-muted small mb-0">Specify when this subject is offered</p>
                    </div>
                </div>

                <div class="row g-3 mb-2">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small required">Subject Type</label>
                        <select class="form-select" name="subject_type" required>
                            <option value="" selected disabled>-- Select Subject Type --</option>
                            <option value="Institutional Subject">Institutional Subject</option>
                            <option value="General Education (GE)">General Education (GE)</option>
                            <option value="Core Subject">Core Subject</option>
                            <option value="Professional Subjects">Professional Subjects</option>
                            <option value="Mandatory / Elect Subject">Mandatory / Elect Subject</option>
                        </select>
                        <div class="invalid-feedback">Please select a subject type.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small required">Semester</label>
                        <select class="form-select" name="subject_semester" required>
                            <option value="" selected disabled>-- Select Semester --</option>
                            <option value="1st Semester">1st Semester</option>
                            <option value="2nd Semester">2nd Semester</option>
                            <option value="Summer">Summer</option>
                        </select>
                        <div class="invalid-feedback">Please select a semester.</div>
                    </div>
                </div>

                <hr class="section-divider">

                <!-- ── Section 3: File Upload ── -->
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="step-circle">3</div>
                    <div>
                        <h5 class="font-serif fw-bold mb-0 text-dark">Syllabus File</h5>
                        <p class="text-muted small mb-0">Upload your PDF syllabus document</p>
                    </div>
                </div>

                <!-- Hidden file input -->
                <input type="file" id="pdfFile" name="pdf_file" accept=".pdf"
                       class="d-none" required>

                <!-- Drop zone -->
                <div class="drop-zone mb-3" id="dropZone">
                    <i class="bi bi-cloud-arrow-up dz-icon"></i>
                    <p class="dz-label fw-semibold mb-0">Drag & drop your PDF here</p>
                    <p class="dz-hint">or <span class="text-orange fw-bold" style="cursor:pointer;"
                                                 onclick="document.getElementById('pdfFile').click()">browse files</span></p>
                    <p class="dz-hint">PDF only · Max 10 MB</p>
                </div>

                <!-- File chip (shown after selection) -->
                <div id="fileChip" class="mb-3">
                    <i class="bi bi-file-earmark-pdf text-danger fs-5"></i>
                    <span id="fileChipName"></span>
                    <span id="fileChipSize" class="text-muted" style="font-size:.75rem;"></span>
                    <span class="remove-file" onclick="clearFile()" title="Remove file">
                        <i class="bi bi-x-circle"></i>
                    </span>
                </div>

                <hr class="section-divider">

                <!-- ── Submit ── -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-login btn-lg fw-bold shadow-sm" id="submitBtn">
                        <i class="bi bi-send me-2"></i>Submit for Review
                    </button>
                    <a href="my_submissions.php" class="btn btn-outline-secondary btn-lg">
                        View My Submissions
                    </a>
                </div>

            </form>
        </div>

        <!-- Workflow Steps -->
        <div class="card upload-card p-4 bg-white mx-auto mt-4" style="max-width:820px;">
            <h6 class="font-serif fw-bold text-orange mb-3">What happens after you submit?</h6>
            <div class="row g-3 text-center">
                <?php
                $steps = [
                    ['icon' => 'bi-upload',             'color' => '#ff8800', 'label' => 'You Submit',       'desc' => 'Your syllabus is uploaded and queued'],
                    ['icon' => 'bi-person-badge',        'color' => '#17a2b8', 'label' => 'Dean Reviews',     'desc' => 'Dean approves or returns for revision'],
                    ['icon' => 'bi-building',            'color' => '#6f42c1', 'label' => 'VPAA Approval',    'desc' => 'VPAA gives final sign-off'],
                    ['icon' => 'bi-patch-check-fill',    'color' => '#28a745', 'label' => 'Published',        'desc' => 'Added to the shared repository'],
                ];
                foreach ($steps as $i => $step): ?>
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded-3 h-100" style="background:rgba(0,0,0,.02);">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                                 style="width:46px;height:46px;background:<?= $step['color'] ?>22;">
                                <i class="bi <?= $step['icon'] ?>" style="color:<?= $step['color'] ?>;font-size:1.3rem;"></i>
                            </div>
                            <p class="fw-bold small mb-1"><?= $step['label'] ?></p>
                            <p class="text-muted mb-0" style="font-size:.75rem;"><?= $step['desc'] ?></p>
                        </div>
                        <?php if ($i < 3): ?>
                            <div class="d-none d-md-block text-muted opacity-25" style="position:absolute;right:-12px;top:50%;transform:translateY(-50%);">
                                <i class="bi bi-chevron-right"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div><!-- /main-content -->
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const dropZone  = document.getElementById('dropZone');
const fileInput = document.getElementById('pdfFile');
const fileChip  = document.getElementById('fileChip');
const chipName  = document.getElementById('fileChipName');
const chipSize  = document.getElementById('fileChipSize');

// Open file picker on drop-zone click
dropZone.addEventListener('click', () => fileInput.click());

// Drag-and-drop visual feedback
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', ()  => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file) setFile(file);
});

// File input change
fileInput.addEventListener('change', () => {
    if (fileInput.files[0]) setFile(fileInput.files[0]);
});

function setFile(file) {
    if (file.type !== 'application/pdf') {
        alert('Only PDF files are accepted.');
        return;
    }
    if (file.size > 10 * 1024 * 1024) {
        alert('File size exceeds 10 MB.');
        return;
    }
    // Assign to real input via DataTransfer
    const dt = new DataTransfer();
    dt.items.add(file);
    fileInput.files = dt.files;

    chipName.textContent  = file.name;
    chipSize.textContent  = ' · ' + (file.size / 1024 / 1024).toFixed(2) + ' MB';
    fileChip.style.display = 'flex';
    dropZone.style.display = 'none';
}

function clearFile() {
    fileInput.value = '';
    fileChip.style.display = 'none';
    dropZone.style.display  = 'block';
}

// Bootstrap 5 client-side validation
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    if (!this.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
    }
    this.classList.add('was-validated');
    if (!fileInput.files.length) {
        e.preventDefault();
        dropZone.style.borderColor = '#dc3545';
        dropZone.querySelector('.dz-hint').style.color = '#dc3545';
    }
});
</script>
</body>
</html>