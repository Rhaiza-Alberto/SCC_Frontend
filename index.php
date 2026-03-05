<?php
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . 'functions.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCC-CCS Syllabus Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap"
        rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-black py-2">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <!-- Placeholder for Logo -->
                <img src="css/logo.png" alt="Logo" class="me-2 rounded-circle" style="width: 50px; height: 50px;">
                <span class="fs-4 fw-bold font-serif">SCC- CCS Syllabus Portal</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <a href="login.php" class="btn btn-outline-warning px-4 rounded-pill">Log In</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center py-5">
        <div class="container py-5 mt-5">
            <h1 class="display-4 fw-bold font-serif mb-3" style=" text-shadow: 2px 2px 4px  rgba(159, 48, 0, 0.8);">
                Welcome to College of Computing Studies!</h1>
            <h2 class=" mb-4 font-serif" style="color: #ffffffff; text-shadow: 2px 2px 4px rgba(159, 48, 0, 0.8);">
                Course Syllabus Approval Portal</h2>
            <p class="lead mb-5 mx-auto"
                style="color: white; text-shadow: 0 2px 6px rgba(0, 0, 0, 0.5); max-width: 700px;">
                Streamline syllabus creation, submission, and approval across all departments and colleges.
            </p>
            <a href="login.php" class="btn btn-outline-warning btn-lg px-5 py-3 rounded-pill orange-text-btn "> Get
                Started</a>
        </div>
        <div class="scroll-indicator"></div>
    </section>

    <!-- Divider -->
    <hr class="container my-5" style="opacity: 0.1;">

    <!-- About Section -->
    <section class="about-section text-center py-5 ">
        <div class="container">
            <h2 class="fw-bold font-serif mb-4">About the Portal</h2>
            <p class="text-muted mb-5">This system helps SCC - CCS instructors and administrators manage course syllabi
                efficiently.</p>

            <div class="row g-4 mt-4 pb-5">
                <!-- Card 1 -->
                <div class="col-md-4">
                    <div class="card h-100 border-orange p-4 bg-transparent">
                        <div class="card-body">
                            <h5 class="card-title fw-bold font-serif mb-3">Instructor Access</h5>
                            <p class="card-text text-muted">Upload and update course syllabi with ease.</p>
                        </div>
                    </div>
                </div>
                <!-- Card 2 -->
                <div class="col-md-4">
                    <div class="card h-100 border-orange p-4 bg-transparent">
                        <div class="card-body">
                            <h5 class="card-title fw-bold font-serif mb-3">Approval Workflow</h5>
                            <p class="card-text text-muted">Streamlined review by Department Heads, Deans, and VPAA.</p>
                        </div>
                    </div>
                </div>
                <!-- Card 3 -->
                <div class="col-md-4">
                    <div class="card h-100 border-orange p-4 bg-transparent">
                        <div class="card-body">
                            <h5 class="card-title fw-bold font-serif mb-3">Notifications</h5>
                            <p class="card-text text-muted">Receive updates on approvals, revisions, and comments
                                instantly.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-black text-white py-5 mt-5">
        <div class="container">
            <div class="row g-4">
                <!-- About Section -->
                <div class="col-lg-4 col-md-6">
                    <h5 class="fw-bold mb-3 text-orange">About SCC-CCS</h5>
                    <p class="text-white-50 small">
                        The College of Computing Studies at Southern City College is dedicated to providing quality IT
                        education
                        and fostering innovation through comprehensive syllabus management.
                    </p>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6">
                    <h5 class="fw-bold mb-3 text-orange">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php"
                                class="text-white-50 text-decoration-none small hover-link">Home</a></li>
                        <li class="mb-2"><a href="login.php"
                                class="text-white-50 text-decoration-none small hover-link">Login</a></li>
                        <li class="mb-2"><a href="register.php"
                                class="text-white-50 text-decoration-none small hover-link">Register</a></li>
                        <li class="mb-2"><a href="#"
                                class="text-white-50 text-decoration-none small hover-link">Help</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div class="col-lg-3 col-md-6">
                    <h5 class="fw-bold mb-3 text-orange">Contact Us</h5>
                    <ul class="list-unstyled text-white-50 small">
                        <li class="mb-2">
                            <i class="bi bi-geo-alt-fill me-2"></i>
                            Zamboanga City, Souther City Colleges
                        <li class="mb-2">
                            <i class="bi bi-envelope-fill me-2"></i>
                            ccs@scc.edu.ph
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-telephone-fill me-2"></i>
                            97-6892 Local(120)
                        </li>
                    </ul>
                </div>

                <!-- Social Media -->
                <div class="col-lg-3 col-md-6">
                    <h5 class="fw-bold mb-3 text-orange">Follow Us</h5>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-white-50 hover-link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                class="bi bi-facebook" viewBox="0 0 16 16">
                                <path
                                    d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951z" />
                            </svg>
                        </a>
                        <a href="#" class="text-white-50 hover-link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                class="bi bi-twitter" viewBox="0 0 16 16">
                                <path
                                    d="M5.026 15c6.038 0 9.341-5.003 9.341-9.334 0-.14 0-.282-.006-.422A6.685 6.685 0 0 0 16 3.542a6.658 6.658 0 0 1-1.889.518 3.301 3.301 0 0 0 1.447-1.817 6.533 6.533 0 0 1-2.087.793A3.286 3.286 0 0 0 7.875 6.03a9.325 9.325 0 0 1-6.767-3.429 3.289 3.289 0 0 0 1.018 4.382A3.323 3.323 0 0 1 .64 6.575v.045a3.288 3.288 0 0 0 2.632 3.218 3.203 3.203 0 0 1-.865.115 3.23 3.23 0 0 1-.614-.057 3.283 3.283 0 0 0 3.067 2.277A6.588 6.588 0 0 1 .78 13.58a6.32 6.32 0 0 1-.78-.045A9.344 9.344 0 0 0 5.026 15z" />
                            </svg>
                        </a>
                        <a href="#" class="text-white-50 hover-link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                                class="bi bi-envelope" viewBox="0 0 16 16">
                                <path
                                    d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Copyright -->
            <hr class="my-4" style="opacity: 0.2;">
            <div class="text-center">
                <p class="mb-0 text-white-50 small">&copy; 2025 Southern City Colleges - College of Computing Studies.
                    All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>