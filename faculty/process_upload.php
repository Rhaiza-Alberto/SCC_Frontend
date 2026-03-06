 <?php
/**
 * process_upload.php
 * Handles faculty syllabus upload — saves to DB (syllabus + syllabus_workflow).
 * Works with free-text fields from upload_syllabus.php form.
 */
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

// Auth check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: upload_syllabus.php');
    exit();
}

ensure_role_in_session();

$user_id       = $_SESSION['user_id'];
$department_id = $_SESSION['department_id'] ?? null;

// Read free-text fields from the upload form
$course_code  = trim($_POST['course_code']      ?? '');
$course_title = trim($_POST['course_title']     ?? '');
$course_name  = trim($_POST['course']           ?? '');
$subject_type = trim($_POST['subject_type']     ?? '');
$semester     = trim($_POST['subject_semester'] ?? '');
$school_year  = trim($_POST['school_year']      ?? get_current_school_year());

// ── Validate required fields ─────────────────────────────────────────────────
if (empty($course_code) || empty($course_title) || empty($subject_type) || empty($semester)) {
    $_SESSION['error_message'] = "Please fill in all required fields.";
    header('Location: upload_syllabus.php');
    exit();
}

// ── Validate file upload ─────────────────────────────────────────────────────
if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server size limit.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit.',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the upload.',
    ];
    $err_code = $_FILES['pdf_file']['error'] ?? UPLOAD_ERR_NO_FILE;
    $_SESSION['error_message'] = $upload_errors[$err_code] ?? 'Unknown upload error.';
    header('Location: upload_syllabus.php');
    exit();
}

$file     = $_FILES['pdf_file'];
$tmp_path = $file['tmp_name'];
$filesize = $file['size'];
$filetype = mime_content_type($tmp_path);

// ── PDF only ──────────────────────────────────────────────────────────────────
if ($filetype !== 'application/pdf') {
    $_SESSION['error_message'] = "Only PDF files are allowed.";
    header('Location: upload_syllabus.php');
    exit();
}

// ── Max 10 MB ─────────────────────────────────────────────────────────────────
if ($filesize > 10 * 1024 * 1024) {
    $_SESSION['error_message'] = "File size must not exceed 10 MB.";
    header('Location: upload_syllabus.php');
    exit();
}

// ── Create upload directory ───────────────────────────────────────────────────
$upload_dir = __DIR__ . '/../uploads/syllabi/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// ── Build safe filename ───────────────────────────────────────────────────────
$safe_code     = preg_replace('/[^A-Za-z0-9_-]/', '_', $course_code);
$safe_semester = preg_replace('/[^A-Za-z0-9_-]/', '_', $semester);
$unique_name   = $safe_code . '_' . $safe_semester . '_' . date('Y') . '_' . uniqid() . '.pdf';
$dest_path     = $upload_dir . $unique_name;

// ── Move uploaded file ────────────────────────────────────────────────────────
if (!move_uploaded_file($tmp_path, $dest_path)) {
    $_SESSION['error_message'] = "Failed to save the uploaded file. Please try again.";
    header('Location: upload_syllabus.php');
    exit();
}

$web_path = 'uploads/syllabi/' . $unique_name;

// ── Try to match an existing course by code (optional FK) ────────────────────
$conn    = get_db();
$cstmt   = $conn->prepare("SELECT id FROM courses WHERE course_code = ? LIMIT 1");
$cstmt->execute([$course_code]);
$matched = $cstmt->fetch(PDO::FETCH_ASSOC);
$course_id = $matched ? (int) $matched['id'] : null;

try {
    $conn->beginTransaction();

    // Insert into syllabus table — free-text columns stored directly
    $stmt = $conn->prepare("
        INSERT INTO syllabus
            (uploaded_by, course_id, course_code, course_title, course_name,
             subject_type, semester, file_path, school_year, status, submitted_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())
    ");
    $stmt->execute([
        $user_id,
        $course_id,
        $course_code,
        $course_title,
        $course_name ?: null,
        $subject_type,
        $semester,
        $web_path,
        $school_year,
    ]);
    $syllabus_id = $conn->lastInsertId();

    // Insert first workflow step → department_head
    $dept_head_role_id = get_role_id('department_head');
    $wstmt = $conn->prepare("
        INSERT INTO syllabus_workflow (syllabus_id, step_order, role_id, action)
        VALUES (?, 1, ?, 'Pending')
    ");
    $wstmt->execute([$syllabus_id, $dept_head_role_id]);

    $conn->commit();

    // Notify the department head
    notify_next_reviewer($syllabus_id, 'department_head');

    $_SESSION['success_message'] = "Syllabus for \"{$course_code}\" uploaded successfully and sent for review!";
    header('Location: my_submissions.php');
    exit();

} catch (PDOException $e) {
    $conn->rollBack();
    // Remove uploaded file if DB insert failed
    if (file_exists($dest_path)) {
        unlink($dest_path);
    }
    error_log("Upload DB Error: " . $e->getMessage());
    $_SESSION['error_message'] = "A database error occurred. Please try again.";
    header('Location: upload_syllabus.php');
    exit();
}