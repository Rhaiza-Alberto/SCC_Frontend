 <?php
/**
 * view_syllabus.php
 * Streams a stored PDF to the browser so users can view it inline.
 * Usage: view_syllabus.php?file=COURSECODE_1stSemester_2025_abc123.pdf
 */
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Access denied.');
}

$filename  = basename($_GET['file'] ?? '');   // basename prevents directory traversal
$file_path = __DIR__ . '/../uploads/syllabi/' . $filename;

if (empty($filename) || !file_exists($file_path)) {
    http_response_code(404);
    exit('File not found.');
}

if (mime_content_type($file_path) !== 'application/pdf') {
    http_response_code(400);
    exit('Invalid file type.');
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: private, max-age=0, must-revalidate');

readfile($file_path);
exit();