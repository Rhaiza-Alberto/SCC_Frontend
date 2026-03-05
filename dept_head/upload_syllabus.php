<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Get user information from session
$username = $_SESSION['username'] ?? 'Dr. Jane Smith';
$email = $_SESSION['email'] ?? '';
$role = $_SESSION['role'] ?? 'dept_head';
$role_display = 'Dept Head Panel';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Syllabus - SCC-CCS Syllabus Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap"
        rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .text-orange {
            color: #ff8800 !important;
        }

        .btn-orange {
            background-color: #ff8800 !important;
            color: white !important;
            border: none;
        }

        .btn-orange:hover {
            background-color: #e67a00 !important;
            color: white !important;
        }

        .stat-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
        }
    </style>
</head>

<body class="bg-light">

    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar sidebar-premium text-white p-2 min-vh-100 d-flex flex-column"
            style="width: 260px; position: fixed; z-index: 1100;">
            <div class="text-center mb-3 mt-2">
                <img src="../css/logo.png" alt="CCS Logo" class="rounded-circle mb-2"
                    style="width: 80px; height: 80px; border: 2px solid rgba(255, 136, 0, 0.5); padding: 3px;">
                <h5 class="font-serif fw-bold text-orange mb-0"><?php echo $role_display; ?></h5>
                <p class="text-white-50 small fw-bold mb-0" style="font-size: 0.75rem;">
                    <?php echo htmlspecialchars($username); ?>
                </p>
            </div>


            <nav class="nav flex-column gap-2 mb-auto">
                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
                <a href="dept_dashboard.php" class="nav-link text-white p-3 rounded hover-effect">
                    Dashboard
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
                <a href="syllabus_review.php" class="nav-link text-white p-3 rounded hover-effect">
                    Syllabus Review
                </a>
                <a href="upload_syllabus.php" class="nav-link text-white active-nav-link p-3 rounded">
                    Upload Syllabus
                </a>
                <a href="my_submissions.php" class="nav-link text-white p-3 rounded hover-effect">
                    My Submissions
                </a>
                <a href="shared_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">
                    Shared Syllabus
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">ACCOUNT MANAGEMENT</div>
                <a href="registration_requests.php" class="nav-link text-white p-3 rounded hover-effect">
                    Registration Requests
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
                <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect">
                    Profile
                </a>
                <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">
                    Logout
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-grow-1 p-5" style="margin-left: 260px;">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <h3 class="text-orange font-serif fw-bold mb-0">Upload Syllabus</h3>
                <div class="notification-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                        class="bi bi-bell" viewBox="0 0 16 16">
                        <path
                            d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zM8 1.918l-.797.161A4.002 4.002 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4.002 4.002 0 0 0-3.203-3.92L8 1.917zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5.002 5.002 0 0 1 13 6c0 .88.32 4.2 1.22 6z" />
                    </svg>
                    <span class="notification-badge-dot"></span>
                </div>
            </div>

            <div class="card premium-card shadow-sm p-5 bg-white mx-auto" style="max-width: 800px;">
                <form action="process_upload.php" method="POST" enctype="multipart/form-data">
                    <!-- Similar form content as faculty upload... -->
                    <!-- Course Code -->
                    <div class="mb-3">
                        <label for="courseCode" class="form-label fw-bold small">Course Code <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="courseCode" name="course_code"
                            placeholder="E.G., CS101" required>
                    </div>

                    <!-- Course Title / Subject Name -->
                    <div class="mb-3">
                        <label for="courseTitle" class="form-label fw-bold small">Course Title / Subject Name <span
                                class="text-danger">*</span></label>
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
                        <label for="subjectType" class="form-label fw-bold small">Subject Type <span
                                class="text-danger">*</span></label>
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
                        <label for="subjectSemester" class="form-label fw-bold small">Subject Semester <span
                                class="text-danger">*</span></label>
                        <select class="form-select" id="subjectSemester" name="subject_semester" required>
                            <option selected disabled>-- Select Semester --</option>
                            <option value="1st Semester">1st Semester</option>
                            <option value="2nd Semester">2nd Semester</option>
                            <option value="Summer">Summer</option>
                        </select>
                    </div>

                    <!-- Upload File -->
                    <div class="mb-4">
                        <label for="pdfFile" class="form-label fw-bold small">Upload File (PDF Only) <span
                                class="text-danger">*</span></label>
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
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>