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
$username = $_SESSION['username'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$role = $_SESSION['role'] ?? 'faculty';
$role_display = 'Faculty Panel';

// Success message
$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

// Read submissions from session, filter by current user
$all_submissions = $_SESSION['submissions'] ?? [];
$user_submissions = array_filter($all_submissions, function($sub) use ($email) {
    return ($sub['uploader_email'] ?? '') === $email;
});

// Split by status
$pending_submissions  = array_values(array_filter($user_submissions, fn($s) => $s['status'] === 'Pending'));
$approved_submissions = array_values(array_filter($user_submissions, fn($s) => $s['status'] === 'Approved'));
$declined_submissions = array_values(array_filter($user_submissions, fn($s) => $s['status'] === 'Rejected'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submissions - SCC-CCS Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .text-orange { color: #ff8800 !important; }
        .btn-orange { background-color: #ff8800 !important; color: white !important; border: none; }
        .btn-orange:hover { background-color: #e67a00 !important; color: white !important; }
        .stat-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
    </style>
</head>

<body class="bg-light">
<div class="d-flex">

    <!-- Sidebar -->
    <div class="sidebar sidebar-premium text-white p-2 min-vh-100 d-flex flex-column"
         style="width: 260px; position: fixed; z-index: 1100;">
        <div class="text-center mb-3 mt-2">
            <img src="../css/logo.png" alt="CCS Logo" class="rounded-circle mb-2"
                 style="width: 80px; height: 80px; border: 2px solid rgba(255,136,0,0.5); padding: 3px;">
            <h5 class="font-serif fw-bold text-orange mb-0"><?php echo $role_display; ?></h5>
            <p class="text-white-50 small fw-bold mb-0" style="font-size: 0.75rem;">
                <?php echo htmlspecialchars($username); ?>
            </p>
        </div>

        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
        <a href="faculty_dashboard.php" class="nav-link text-white p-3 rounded hover-effect">Dashboard</a>

        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
        <a href="upload_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">Upload Syllabus</a>
        <a href="my_submissions.php" class="nav-link text-white active-nav-link p-3 rounded">My Submissions</a>
        <a href="shared_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">Shared Syllabus</a>

        <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
        <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect">Profile</a>
        <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content flex-grow-1 p-5" style="margin-left: 260px;">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <h3 class="text-orange font-serif fw-bold mb-0">My Syllabus Submissions</h3>
            <div class="notification-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                     class="bi bi-bell" viewBox="0 0 16 16">
                    <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zM8 1.918l-.797.161A4.002 4.002 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4.002 4.002 0 0 0-3.203-3.92L8 1.917zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5.002 5.002 0 0 1 13 6c0 .88.32 4.2 1.22 6z"/>
                </svg>
                <span class="notification-badge-dot"></span>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tabbed Submissions Section -->
        <div class="card premium-card p-4 mb-5 shadow-sm border-orange border-opacity-10">
            <ul class="nav nav-tabs nav-fill mb-4 border-bottom" id="submissionTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active font-serif fw-bold text-orange" id="pending-tab"
                            data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">
                        Pending Approval
                        <?php if (count($pending_submissions) > 0): ?>
                            <span class="badge bg-warning text-dark ms-1"><?php echo count($pending_submissions); ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link font-serif fw-bold text-orange" id="approved-tab"
                            data-bs-toggle="tab" data-bs-target="#approved" type="button" role="tab">
                        Approved
                        <?php if (count($approved_submissions) > 0): ?>
                            <span class="badge bg-success ms-1"><?php echo count($approved_submissions); ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link font-serif fw-bold text-orange" id="declined-tab"
                            data-bs-toggle="tab" data-bs-target="#declined" type="button" role="tab">
                        Declined
                        <?php if (count($declined_submissions) > 0): ?>
                            <span class="badge bg-danger ms-1"><?php echo count($declined_submissions); ?></span>
                        <?php endif; ?>
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="submissionTabContent">

                <!-- ── Pending Tab ── -->
                <div class="tab-pane fade show active" id="pending" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-premium">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-secondary small">#</th>
                                    <th class="text-secondary small">COURSE</th>
                                    <th class="text-secondary small d-none d-lg-table-cell">TYPE</th>
                                    <th class="text-secondary small d-none d-xl-table-cell">SEM / YEAR</th>
                                    <th class="text-secondary small">STATUS</th>
                                    <th class="text-secondary small text-center">FILE</th>
                                    <th class="text-secondary small">SUBMITTED ON</th>
                                    <th class="text-secondary small text-center">ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pending_submissions)): ?>
                                    <tr><td colspan="8" class="text-center text-muted py-4">No pending submissions</td></tr>
                                <?php else: foreach ($pending_submissions as $i => $sub): ?>
                                    <tr>
                                        <td><?php echo $i + 1; ?></td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold small"><?php echo htmlspecialchars($sub['course_code']); ?></span>
                                                <span class="text-muted text-truncate" style="font-size:0.7rem;max-width:150px;">
                                                    <?php echo htmlspecialchars($sub['course_title']); ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="d-none d-lg-table-cell small"><?php echo htmlspecialchars($sub['subject_type']); ?></td>
                                        <td class="d-none d-xl-table-cell small">
                                            <?php echo htmlspecialchars($sub['semester']); ?><br>
                                            <?php echo htmlspecialchars($sub['year']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning text-dark bg-opacity-25 border border-warning rounded-pill px-3" style="font-size:0.75rem;">
                                                Pending
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="view_syllabus.php?file=<?php echo urlencode(basename($sub['file_path'])); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-link text-orange p-0">
                                                <i class="bi bi-file-earmark-pdf fs-5"></i>
                                            </a>
                                        </td>
                                        <td class="small"><?php echo htmlspecialchars($sub['submitted_on']); ?></td>
                                        <td class="text-center">
                                            <a href="edit_syllabus.php?id=<?php echo $sub['id']; ?>" class="btn btn-sm btn-outline-warning rounded-pill px-3">Edit</a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ── Approved Tab ── -->
                <div class="tab-pane fade" id="approved" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-premium">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-secondary small">#</th>
                                    <th class="text-secondary small">COURSE</th>
                                    <th class="text-secondary small d-none d-lg-table-cell">TYPE</th>
                                    <th class="text-secondary small d-none d-xl-table-cell">SEM / YEAR</th>
                                    <th class="text-secondary small">STATUS</th>
                                    <th class="text-secondary small">REVIEWER</th>
                                    <th class="text-secondary small text-center">FILE</th>
                                    <th class="text-secondary small">SUBMITTED ON</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($approved_submissions)): ?>
                                    <tr><td colspan="8" class="text-center text-muted py-4">No approved submissions found</td></tr>
                                <?php else: foreach ($approved_submissions as $i => $sub): ?>
                                    <tr>
                                        <td><?php echo $i + 1; ?></td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold small"><?php echo htmlspecialchars($sub['course_code']); ?></span>
                                                <span class="text-muted text-truncate" style="font-size:0.7rem;max-width:150px;">
                                                    <?php echo htmlspecialchars($sub['course_title']); ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="d-none d-lg-table-cell small"><?php echo htmlspecialchars($sub['subject_type']); ?></td>
                                        <td class="d-none d-xl-table-cell small">
                                            <?php echo htmlspecialchars($sub['semester']); ?><br>
                                            <?php echo htmlspecialchars($sub['year']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-success text-success bg-opacity-25 border border-success rounded-pill px-3" style="font-size:0.75rem;">
                                                Approved
                                            </span>
                                        </td>
                                        <td class="small"><?php echo htmlspecialchars($sub['reviewer'] ?? '—'); ?></td>
                                        <td class="text-center">
                                            <a href="view_syllabus.php?file=<?php echo urlencode(basename($sub['file_path'])); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-link text-orange p-0">
                                                <i class="bi bi-file-earmark-pdf fs-5"></i>
                                            </a>
                                        </td>
                                        <td class="small"><?php echo htmlspecialchars($sub['submitted_on']); ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ── Declined Tab ── -->
                <div class="tab-pane fade" id="declined" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-premium">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-secondary small">#</th>
                                    <th class="text-secondary small">COURSE</th>
                                    <th class="text-secondary small d-none d-lg-table-cell">TYPE</th>
                                    <th class="text-secondary small d-none d-xl-table-cell">SEM / YEAR</th>
                                    <th class="text-secondary small">STATUS</th>
                                    <th class="text-secondary small">COMMENT</th>
                                    <th class="text-secondary small text-center">FILE</th>
                                    <th class="text-secondary small">SUBMITTED ON</th>
                                    <th class="text-secondary small text-center">ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($declined_submissions)): ?>
                                    <tr><td colspan="9" class="text-center text-muted py-4">No declined submissions found</td></tr>
                                <?php else: foreach ($declined_submissions as $i => $sub): ?>
                                    <tr>
                                        <td><?php echo $i + 1; ?></td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold small"><?php echo htmlspecialchars($sub['course_code']); ?></span>
                                                <span class="text-muted text-truncate" style="font-size:0.7rem;max-width:150px;">
                                                    <?php echo htmlspecialchars($sub['course_title']); ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="d-none d-lg-table-cell small"><?php echo htmlspecialchars($sub['subject_type']); ?></td>
                                        <td class="d-none d-xl-table-cell small">
                                            <?php echo htmlspecialchars($sub['semester']); ?><br>
                                            <?php echo htmlspecialchars($sub['year']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger text-danger bg-opacity-25 border border-danger rounded-pill px-3" style="font-size:0.75rem;">
                                                Rejected
                                            </span>
                                        </td>
                                        <td class="small"><?php echo htmlspecialchars($sub['comment'] ?? '—'); ?></td>
                                        <td class="text-center">
                                            <a href="view_syllabus.php?file=<?php echo urlencode(basename($sub['file_path'])); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-link text-orange p-0">
                                                <i class="bi bi-file-earmark-pdf fs-5"></i>
                                            </a>
                                        </td>
                                        <td class="small"><?php echo htmlspecialchars($sub['submitted_on']); ?></td>
                                        <td class="text-center">
                                            <a href="upload_syllabus.php?resubmit=<?php echo $sub['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3">Resubmit</a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div><!-- /tab-content -->
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>