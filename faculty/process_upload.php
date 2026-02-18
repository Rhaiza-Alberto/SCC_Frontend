<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_code = $_POST['course_code'] ?? '';
    $course_title = $_POST['course_title'] ?? '';
    $department = $_POST['course'] ?? ''; // From the 'course' input which user likely meant as dept
    $subject_type = $_POST['subject_type'] ?? '';
    $semester = $_POST['subject_semester'] ?? '';

    // Simple mapping to ensure it matches VPAA expectation (CS, IT, IS)
    if (stripos($department, 'Computer Science') !== false)
        $department = 'CS';
    elseif (stripos($department, 'Information Technology') !== false)
        $department = 'IT';
    elseif (stripos($department, 'Information Systems') !== false)
        $department = 'IS';
    else
        $department = 'CS'; // Default for demo

    if (!isset($_SESSION['submissions'])) {
        $_SESSION['submissions'] = [];
    }

    $new_id = count($_SESSION['submissions']) + 1;
    $submission = [
        'id' => $new_id,
        'uploader_name' => $_SESSION['username'] ?? 'Faculty User',
        'uploader_email' => $_SESSION['email'] ?? 'faculty@gmail.com',
        'department' => $department,
        'course_code' => $course_code,
        'course_title' => $course_title,
        'subject_type' => $subject_type,
        'semester' => $semester,
        'year' => date('2024'),
        'file_path' => '#', // simulated upload
        'submitted_on' => date('Y-m-d'),
        'status' => 'Pending'
    ];

    $_SESSION['submissions'][] = $submission;

    $_SESSION['success_message'] = "Syllabus for $course_code uploaded successfully!";
    header('Location: my_submissions.php');
    exit();
}
?>