<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $course_code    = trim($_POST['course_code'] ?? '');
    $course_title   = trim($_POST['course_title'] ?? '');
    $department     = trim($_POST['course'] ?? '');
    $subject_type   = trim($_POST['subject_type'] ?? '');
    $semester       = trim($_POST['subject_semester'] ?? '');

    // ── Validate required fields ─────────────────────────────────────────────
    if (empty($course_code) || empty($course_title) || empty($subject_type) || empty($semester)) {
        $_SESSION['error_message'] = "Please fill in all required fields.";
        header('Location: upload_syllabus.php');
        exit();
    }

    // ── Validate file upload ─────────────────────────────────────────────────
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
    $ori_name = $file['name'];
    $filesize = $file['size'];
    $filetype = mime_content_type($tmp_path); // safer than trusting $_FILES['type']

    // ── Check it is actually a PDF ────────────────────────────────────────────
    if ($filetype !== 'application/pdf') {
        $_SESSION['error_message'] = "Only PDF files are allowed.";
        header('Location: upload_syllabus.php');
        exit();
    }

    // ── Check file size (max 10 MB) ───────────────────────────────────────────
    if ($filesize > 10 * 1024 * 1024) {
        $_SESSION['error_message'] = "File size must not exceed 10 MB.";
        header('Location: upload_syllabus.php');
        exit();
    }

    // ── Create upload directory if it doesn't exist ───────────────────────────
    $upload_dir = __DIR__ . '/../uploads/syllabi/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // ── Generate a unique filename ────────────────────────────────────────────
    // Format: COURSECODE_SEMESTER_YEAR_uniqid.pdf
    $safe_code     = preg_replace('/[^A-Za-z0-9_-]/', '_', $course_code);
    $safe_semester = preg_replace('/[^A-Za-z0-9_-]/', '_', $semester);
    $unique_name   = $safe_code . '_' . $safe_semester . '_' . date('Y') . '_' . uniqid() . '.pdf';
    $dest_path     = $upload_dir . $unique_name;

    // ── Move file to permanent location ──────────────────────────────────────
    if (!move_uploaded_file($tmp_path, $dest_path)) {
        $_SESSION['error_message'] = "Failed to save the uploaded file. Please try again.";
        header('Location: upload_syllabus.php');
        exit();
    }

    // ── Build the web-accessible path (relative to project root) ─────────────
    $web_path = '../uploads/syllabi/' . $unique_name;

    // ── Department shortcode mapping ──────────────────────────────────────────
    if (stripos($department, 'Computer Science') !== false)
        $dept_code = 'CS';
    elseif (stripos($department, 'Information Technology') !== false)
        $dept_code = 'IT';
    elseif (stripos($department, 'Information Systems') !== false)
        $dept_code = 'IS';
    else
        $dept_code = $department ?: 'CS';

    // ── Save to session (replace with DB insert when ready) ──────────────────
    if (!isset($_SESSION['submissions'])) {
        $_SESSION['submissions'] = [];
    }

    $new_id = count($_SESSION['submissions']) + 1;

    $_SESSION['submissions'][] = [
        'id'             => $new_id,
        'uploader_name'  => $_SESSION['username'] ?? 'Faculty User',
        'uploader_email' => $_SESSION['email']    ?? '',
        'department'     => $dept_code,
        'course_code'    => $course_code,
        'course_title'   => $course_title,
        'subject_type'   => $subject_type,
        'semester'       => $semester,
        'year'           => date('Y'),
        'file_path'      => $web_path,      // ← real path now
        'file_name'      => $unique_name,
        'original_name'  => $ori_name,
        'submitted_on'   => date('Y-m-d'),
        'status'         => 'Pending',
        'comment'        => null,
        'reviewer'       => null,
    ];

    $_SESSION['success_message'] = "Syllabus for \"$course_code\" uploaded successfully!";
    header('Location: my_submissions.php');
    exit();
}

// If accessed directly without POST, redirect back
header('Location: upload_syllabus.php');
exit();
?>