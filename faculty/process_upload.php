<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

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
$uploader_role = $_SESSION['role_name'] ?? $_SESSION['role'] ?? 'faculty';
// Normalize: session 'role' for dean is 'dean', role_name is also 'dean'
$is_dean = in_array($uploader_role, ['dean', 'admin']);

// Redirect targets
$back_url    = $is_dean ? '../admin/upload_syllabus.php' : 'upload_syllabus.php';
$success_url = $is_dean ? '../admin/my_submissions.php'  : 'my_submissions.php';

// ── Read fields ───────────────────────────────────────────────────────────────
$course_code  = trim($_POST['course_code']      ?? '');
$course_title = trim($_POST['course_title']     ?? '');
$course_name  = trim($_POST['course']           ?? '');
$subject_type = trim($_POST['subject_type']     ?? '');
$semester     = trim($_POST['subject_semester'] ?? $_POST['semester'] ?? '');
$school_year  = trim($_POST['school_year']      ?? get_current_school_year());
$year_level   = trim($_POST['year_level']       ?? '');

// ── Validate required fields ─────────────────────────────────────────────────
if (empty($course_code) || empty($course_title) || empty($subject_type) || empty($semester)) {
    $_SESSION['upload_error'] = 'Please fill in all required fields.';
    header('Location: ' . $back_url);
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
    $_SESSION['upload_error'] = $upload_errors[$err_code] ?? 'Unknown upload error.';
    header('Location: ' . $back_url);
    exit();
}

$file     = $_FILES['pdf_file'];
$tmp_path = $file['tmp_name'];
$filetype = mime_content_type($tmp_path);

if ($filetype !== 'application/pdf') {
    $_SESSION['upload_error'] = 'Only PDF files are allowed.';
    header('Location: ' . $back_url);
    exit();
}

if ($file['size'] > 10 * 1024 * 1024) {
    $_SESSION['upload_error'] = 'File size must not exceed 10 MB.';
    header('Location: ' . $back_url);
    exit();
}

// ── Save file ─────────────────────────────────────────────────────────────────
$upload_dir = __DIR__ . '/../uploads/syllabi/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$safe_code   = preg_replace('/[^A-Za-z0-9_-]/', '_', $course_code);
$safe_sem    = preg_replace('/[^A-Za-z0-9_-]/', '_', $semester);
$unique_name = $safe_code . '_' . $safe_sem . '_' . date('Y') . '_' . uniqid() . '.pdf';
$dest_path   = $upload_dir . $unique_name;

if (!move_uploaded_file($tmp_path, $dest_path)) {
    $_SESSION['upload_error'] = 'Failed to save the uploaded file. Please try again.';
    header('Location: ' . $back_url);
    exit();
}

$web_path = 'uploads/syllabi/' . $unique_name;

// ── Try to match existing course FK (optional) ────────────────────────────────
$conn  = get_db();
$cstmt = $conn->prepare("SELECT id FROM courses WHERE course_code = ? LIMIT 1");
$cstmt->execute([$course_code]);
$matched   = $cstmt->fetch(PDO::FETCH_ASSOC);
$course_id = $matched ? (int) $matched['id'] : null;

try {
    $conn->beginTransaction();

    // ── Insert syllabus row ───────────────────────────────────────────────────
    $stmt = $conn->prepare("
        INSERT INTO syllabus
            (uploaded_by, course_id, course_code, course_title, course_name,
             subject_type, semester, school_year, file_path, status, submitted_at)
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
        $school_year,
        $web_path,
    ]);
    $syllabus_id = (int) $conn->lastInsertId();

    // ── Insert workflow step based on who uploaded ────────────────────────────
    if ($is_dean) {
        // Dean's own upload: skip dean review, go straight to VPAA
        $vpaa_role_id = get_role_id('vpaa');
        if (!$vpaa_role_id) {
            throw new Exception("'vpaa' role not found in roles table.");
        }
        $conn->prepare("
            INSERT INTO syllabus_workflow (syllabus_id, step_order, role_id, action)
            VALUES (?, 2, ?, 'Pending')
        ")->execute([$syllabus_id, $vpaa_role_id]);

        $conn->commit();

        // Notify VPAA
        notify_next_reviewer($syllabus_id, 'vpaa');

        $_SESSION['upload_success'] = "Syllabus for \"{$course_code}\" submitted and sent to VPAA for final approval.";

    } else {
        // Faculty upload: first reviewer is the dean
        $dean_role_id = get_role_id('dean');
        if (!$dean_role_id) {
            throw new Exception("'dean' role not found in roles table.");
        }
        $conn->prepare("
            INSERT INTO syllabus_workflow (syllabus_id, step_order, role_id, action)
            VALUES (?, 1, ?, 'Pending')
        ")->execute([$syllabus_id, $dean_role_id]);

        $conn->commit();

        // Notify dean
        notify_next_reviewer($syllabus_id, 'dean');

        $_SESSION['upload_success'] = "Syllabus for \"{$course_code}\" submitted and sent to the Dean for review.";
    }

    // Use session key that both admin and faculty upload pages read
    $_SESSION['success_message'] = $_SESSION['upload_success'];
    header('Location: ' . $success_url);
    exit();

} catch (Exception $e) {
    $conn->rollBack();
    if (file_exists($dest_path)) unlink($dest_path);
    error_log('Upload DB Error: ' . $e->getMessage());
    $_SESSION['upload_error'] = 'A database error occurred. Please try again.';
    header('Location: ' . $back_url);
    exit();
}