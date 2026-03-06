<?php
/**
 * process_edit_syllabus.php
 * Handles editing a pending syllabus submission.
 */
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my_submissions.php');
    exit();
}

ensure_role_in_session();

$user_id     = $_SESSION['user_id'];
$syllabus_id = (int) ($_POST['syllabus_id'] ?? 0);

if (!$syllabus_id) {
    header('Location: my_submissions.php');
    exit();
}

// Verify ownership + status
$conn = get_db();
$stmt = $conn->prepare("SELECT * FROM syllabus WHERE id = ? AND uploaded_by = ? AND status = 'Pending'");
$stmt->execute([$syllabus_id, $user_id]);
$syllabus = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$syllabus) {
    $_SESSION['error_message'] = "Submission not found or cannot be edited.";
    header('Location: my_submissions.php');
    exit();
}

// Read fields
$course_code  = trim($_POST['course_code']      ?? '');
$course_title = trim($_POST['course_title']     ?? '');
$course_name  = trim($_POST['course']           ?? '');
$subject_type = trim($_POST['subject_type']     ?? '');
$semester     = trim($_POST['subject_semester'] ?? '');

if (empty($course_code) || empty($course_title) || empty($subject_type) || empty($semester)) {
    $_SESSION['error_message'] = "Please fill in all required fields.";
    header('Location: edit_syllabus.php?id=' . $syllabus_id);
    exit();
}

// Optional file replacement
$new_file_path = $syllabus['file_path']; // keep existing by default
$old_dest_path = null;

if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
    $file     = $_FILES['pdf_file'];
    $tmp_path = $file['tmp_name'];
    $filesize = $file['size'];
    $filetype = mime_content_type($tmp_path);

    if ($filetype !== 'application/pdf') {
        $_SESSION['error_message'] = "Only PDF files are allowed.";
        header('Location: edit_syllabus.php?id=' . $syllabus_id);
        exit();
    }
    if ($filesize > 10 * 1024 * 1024) {
        $_SESSION['error_message'] = "File size must not exceed 10 MB.";
        header('Location: edit_syllabus.php?id=' . $syllabus_id);
        exit();
    }

    $upload_dir = __DIR__ . '/../uploads/syllabi/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $safe_code     = preg_replace('/[^A-Za-z0-9_-]/', '_', $course_code);
    $safe_semester = preg_replace('/[^A-Za-z0-9_-]/', '_', $semester);
    $unique_name   = $safe_code . '_' . $safe_semester . '_' . date('Y') . '_' . uniqid() . '.pdf';
    $dest_path     = $upload_dir . $unique_name;

    if (!move_uploaded_file($tmp_path, $dest_path)) {
        $_SESSION['error_message'] = "Failed to save the uploaded file. Please try again.";
        header('Location: edit_syllabus.php?id=' . $syllabus_id);
        exit();
    }

    $new_file_path = 'uploads/syllabi/' . $unique_name;
    $old_dest_path = __DIR__ . '/../' . $syllabus['file_path'];
}

// Try to re-match course FK
$cstmt = $conn->prepare("SELECT id FROM courses WHERE course_code = ? LIMIT 1");
$cstmt->execute([$course_code]);
$matched   = $cstmt->fetch(PDO::FETCH_ASSOC);
$course_id = $matched ? (int) $matched['id'] : null;

try {
    $upd = $conn->prepare("
        UPDATE syllabus
        SET course_id    = ?,
            course_code  = ?,
            course_title = ?,
            course_name  = ?,
            subject_type = ?,
            semester     = ?,
            file_path    = ?
        WHERE id = ? AND uploaded_by = ? AND status = 'Pending'
    ");
    $upd->execute([
        $course_id,
        $course_code,
        $course_title,
        $course_name ?: null,
        $subject_type,
        $semester,
        $new_file_path,
        $syllabus_id,
        $user_id,
    ]);

    // Delete old file if replaced
    if ($old_dest_path && file_exists($old_dest_path)) {
        unlink($old_dest_path);
    }

    $_SESSION['success_message'] = "Submission updated successfully.";
    header('Location: my_submissions.php');
    exit();

} catch (PDOException $e) {
    error_log("Edit Syllabus Error: " . $e->getMessage());
    $_SESSION['error_message'] = "A database error occurred. Please try again.";
    header('Location: edit_syllabus.php?id=' . $syllabus_id);
    exit();
}