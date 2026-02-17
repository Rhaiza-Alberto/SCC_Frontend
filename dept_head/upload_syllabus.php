<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Get user information from session
$username = $_SESSION['username'] ?? 'Department Head';
$email = $_SESSION['email'] ?? '';
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
</head>

<body class="bg-light">

    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar bg-black text-white p-3 min-vh-100 d-flex flex-column"
            style="width: 280px; position: fixed;">
            <div class="text-center mb-4 mt-3">
                <img src="../css/logo.png" alt="CCS Logo" class="rounded-circle mb-2"
                    style="width: 80px; height: 80px;">
                <h4 class="font-serif fw-bold">Head Panel</h4>
            </div>

            <nav class="nav flex-column gap-2 mb-auto">
                <a href="dept_dashboard.php" class="nav-link text-white p-3 rounded hover-effect">
                    Dashboard
                </a>
                <a href="upload_syllabus.php" class="nav-link text-white active-nav-link p-3 rounded">
                    Upload Syllabus
                </a>
                <a href="my_submissions.php" class="nav-link text-white p-3 rounded hover-effect">
                    My Submissions
                </a>
                <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect">
                    Profile
                </a>
                <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">
                    Logout
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-grow-1 p-5" style="margin-left: 280px;">
            <div class="card border-0 shadow-sm p-5" style="max-width: 700px; margin: 0 auto;">
                <h3 class="text-orange font-serif fw-bold mb-4">Upload Syllabus</h3>

                <form action="process_upload.php" method="POST" enctype="multipart/form-data">

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

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>