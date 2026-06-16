<?php
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCC-CCS — Syllabus Management Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/design-system.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .landing-nav {
            background: rgba(255, 255, 255, 0.8) !important;
            backdrop-filter: blur(15px);
            border-bottom: 1px solid var(--border);
            transition: all 0.3s ease;
        }

        [data-theme="dark"] .landing-nav {
            background: rgba(15, 15, 20, 0.8) !important;
            border-bottom-color: rgba(255, 255, 255, 0.05);
        }

        .hero-premium {
            position: relative;
            min-height: 700px;
            padding: 140px 0 100px;
            background:
                linear-gradient(rgba(0, 0, 0, 0.8), rgba(30, 15, 0, 0.6), rgba(0, 0, 0, 0.85)),
                url('css/background.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            color: white;
            overflow: hidden;
        }

        .hero-premium::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 136, 0, 0.4) 0%, transparent 70%);
            z-index: 1;
        }

        .cta-card-compact {
            max-width: 800px;
            margin: 0 auto;
            padding: 2.5rem !important;
            border-radius: var(--radius-xl) !important;
            background: var(--primary) !important;
            color: white !important;
            border: none !important;
            box-shadow: 0 20px 40px rgba(255, 136, 0, 0.2) !important;
        }

        .feature-icon-wrapper {
            width: 48px;
            height: 48px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 1.25rem;
        }

        .theme-toggle-nav {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .theme-toggle-nav:hover {
            background: var(--primary-light);
            color: var(--primary);
            border-color: var(--primary-border);
        }
    </style>
</head>

<body class="bg-light">
    <!-- Premium Navigation -->
    <nav class="navbar navbar-expand-lg landing-nav sticky-top py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-3" href="index.php">
                <img src="css/logo.png" alt="Logo" width="42" height="42" class="rounded-circle shadow-sm">
                <div class="d-none d-md-block">
                    <div class="fw-bold fs-5 mb-0" style="color:var(--text); line-height:1">SCCs <span
                            style="color:var(--primary)">Syllabus</span></div>
                    <small class="text-muted"
                        style="font-size: 0.6rem; letter-spacing: 1px; text-transform: uppercase;">College of Computing
                        Studies</small>
                </div>
            </a>

            <div class="ms-auto d-flex gap-2 gap-md-3 align-items-center" id="navbarActions">
                <button type="button" class="theme-toggle-nav" id="themeToggleBtn" title="Toggle Theme">
                    <i class="bi bi-moon-fill"></i>
                </button>
                <div class="vr mx-1 opacity-10 d-none d-md-block"></div>
                <a href="login.php" class="text-decoration-none fw-bold small px-2 py-1" style="color:var(--text)">Log
                    In</a>
                <a href="register.php"
                    class="btn btn-primary-scc rounded-pill px-4 py-2 shadow-sm small fw-bold">Register</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-premium">
        <div class="container position-relative" style="z-index: 2;">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <span class="badge rounded-pill mb-3 px-3 py-2 fw-bold"
                        style="background:rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25); backdrop-filter: blur(5px);">
                        <i class="bi bi-shield-check me-2 text-warning"></i> SCCs-CCS OFFICIAL PORTAL
                    </span>
                    <h1 class="display-3 fw-bold mb-4" style="text-shadow: 0 2px 10px rgba(0,0,0,0.3);">Streamlining
                        Academic <span style="color:var(--primary)">Excellence.</span></h1>
                    <p class="lead mb-5"
                        style="max-width: 600px; color: rgba(255,255,255,0.9); text-shadow: 0 1px 5px rgba(0,0,0,0.2);">
                        The centralized hub for SCC College of Computing Studies syllabus management. Accelerate
                        approvals, ensure compliance, and manage curriculum workflows with institutional precision.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="login.php" class="btn btn-light btn-lg rounded-pill px-5 py-3 fw-bold shadow">
                            Get Started <i class="bi bi-arrow-right ms-2"></i>
                        </a>
                        <a href="#features" class="btn btn-outline-light btn-lg rounded-pill px-5 py-3 fw-bold">
                            Capabilities
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5 mt-5">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h6 class="text-primary fw-bold text-uppercase mb-2" style="letter-spacing: 2px; font-size: 0.75rem;">
                    Platform Capabilities</h6>
                <h2 class="display-6 fw-bold" style="color:var(--text)">Syllabus Management <span
                        class="text-orange">Simplified</span></h2>
            </div>

            <div class="row g-4 pt-4">
                <div class="col-md-4">
                    <div class="scc-card p-4 h-100 border-0 shadow-sm">
                        <div class="feature-icon-wrapper">
                            <i class="bi bi-cloud-arrow-up"></i>
                        </div>
                        <h5 class="fw-bold mb-3" style="font-size: 1.1rem;">Institutional Uploads</h5>
                        <p class="text-muted small mb-0">Standardized syllabus submission portal for all faculty members
                            with automatic versioning and archiving.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="scc-card p-4 h-100 border-0 shadow-sm">
                        <div class="feature-icon-wrapper" style="background:rgba(34,197,94,0.1); color:#22c55e">
                            <i class="bi bi-diagram-3"></i>
                        </div>
                        <h5 class="fw-bold mb-3" style="font-size: 1.1rem;">Multi-tier Approval</h5>
                        <p class="text-muted small mb-0">Automated workflow routing from Faculty to Dean for streamlined
                            institutional approval.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="scc-card p-4 h-100 border-0 shadow-sm">
                        <div class="feature-icon-wrapper" style="background:rgba(99,102,241,0.1); color:#6366f1">
                            <i class="bi bi-bell"></i>
                        </div>
                        <h5 class="fw-bold mb-3" style="font-size: 1.1rem;">Smart Notifications</h5>
                        <p class="text-muted small mb-0">Real-time status updates and action-required alerts keep the
                            syllabus cycle moving efficiently.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-5 mb-5">
        <div class="container">
            <div class="scc-card cta-card-compact text-center">
                <h2 class="fw-bold mb-3" style="font-size: 2rem;">Ready to modernize your workflow?</h2>
                <p class="mb-4 opacity-90 small mx-auto" style="max-width: 500px;">Join the SCC Faculty and start
                    managing your syllabi with precision and ease through our unified platform.</p>
                <div class="d-flex gap-3 justify-content-center">
                    <a href="register.php" class="btn btn-light rounded-pill px-5 py-2 fw-bold shadow-sm">Create
                        Account</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white border-top py-5 mt-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <img src="css/logo.png" alt="Logo" width="38" height="38" class="rounded-circle">
                        <div class="fw-bold fs-5" style="color:var(--text)">SCC <span
                                style="color:var(--primary)">Syllabus</span></div>
                    </div>
                    <p class="text-muted small mb-4" style="max-width: 320px;">
                        The College of Computing Studies' dedicated portal for syllabus management and institutional
                        compliance.
                    </p>
                    <div class="d-flex gap-3 text-muted">
                        <a href="#" class="text-reset"><i class="bi bi-facebook fs-5"></i></a>
                        <a href="#" class="text-reset"><i class="bi bi-twitter-x fs-5"></i></a>
                        <a href="#" class="text-reset"><i class="bi bi-linkedin fs-5"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 ms-auto">
                    <h6 class="fw-bold mb-4 small text-uppercase" style="letter-spacing: 1px;">Platform</h6>
                    <ul class="list-unstyled text-muted small">
                        <li class="mb-2"><a href="login.php" class="text-reset text-decoration-none">Log In</a></li>
                        <li class="mb-2"><a href="register.php" class="text-reset text-decoration-none">Register</a>
                        </li>
                        <li class="mb-2"><a href="#" class="text-reset text-decoration-none">Support</a></li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h6 class="fw-bold mb-4 small text-uppercase" style="letter-spacing: 1px;">Institutional</h6>
                    <ul class="list-unstyled text-muted small">
                        <li class="mb-2"><i class="bi bi-geo-alt me-2 text-primary"></i> Pilar St., Zamboanga City</li>
                        <li class="mb-2"><i class="bi bi-envelope me-2 text-primary"></i> ccs@scc.edu.ph</li>
                        <li class="mb-2"><i class="bi bi-telephone me-2 text-primary"></i> 991-6892</li>
                    </ul>
                </div>
            </div>
            <hr class="my-5 opacity-5">
            <div class="text-center text-muted small">
                &copy; <?= date('Y') ?> Southern City Colleges. All Rights Reserved.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/theme.js"></script>
    <script src="js/common.js"></script>
</body>

</html>
>