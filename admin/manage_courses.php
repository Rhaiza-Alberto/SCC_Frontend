<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

restrict_to_role('dean');

$username = $_SESSION['username'] ?? 'Dean / Admin';
$role_display = "Dean's Panel";
$user_id = $_SESSION['user_id'];

$conn = get_db();

// Handle Delete Course
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: manage_courses.php?deleted=true");
    exit();
}

// Fetch Courses with College Names
$query = "SELECT c.*, col.college_name 
          FROM courses c 
          LEFT JOIN colleges col ON c.college_id = col.id 
          ORDER BY col.college_name, c.course_code";
$stmt = $conn->prepare($query);
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unread_count = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);

// Get counts for sidebar badges
$pending_review_count = (int) $conn->query("
    SELECT COUNT(DISTINCT sw.syllabus_id)
    FROM syllabus_workflow sw
    JOIN roles r ON sw.role_id = r.id
    WHERE r.role_name = 'dean' AND sw.action = 'Pending'
")->fetchColumn();

$reg_count = (int) $conn->query("
    SELECT COUNT(*) FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE r.role_name = 'faculty' AND u.is_approved = 0 AND u.is_deleted = 0
")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - SCC-CCS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/common.js"></script>
    <style>
        .text-orange { color: #ff8800 !important; }
        .btn-orange { background-color: #ff8800 !important; color: white !important; border: none; }
        .btn-orange:hover { background-color: #e67a00 !important; color: white !important; }
        .premium-card { border: none; border-radius: 15px; background: #ffffff; }
        .table thead th { background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; color: #6c757d; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; }
    </style>
</head>
<body class="bg-light">
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar sidebar-premium text-white p-2 min-vh-100 d-flex flex-column" style="width:260px; position:fixed; z-index:1100;">
            <div class="text-center mb-3 mt-2">
                <img src="../css/logo.png" alt="CCS Logo" class="rounded-circle mb-2" style="width:80px;height:80px;border:2px solid rgba(255,136,0,.5);padding:3px;">
                <h5 class="font-serif fw-bold text-orange mb-0"><?= $role_display ?></h5>
                <p class="text-white-50 small fw-bold mb-0" style="font-size:.75rem;"><?= htmlspecialchars($username) ?></p>
            </div>
            <nav class="nav flex-column gap-2 mb-auto">
                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
                <a href="admin_dashboard.php" class="nav-link text-white p-3 rounded hover-effect">Dashboard</a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
                <a href="syllabus_review.php" class="nav-link text-white p-3 rounded hover-effect">
                    Syllabus Review
                    <?php if ($pending_review_count > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $pending_review_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="upload_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">Upload Syllabus</a>
                <a href="manage_courses.php" class="nav-link text-white p-3 rounded active-nav-link">Manage Courses</a>
                <a href="my_submissions.php" class="nav-link text-white p-3 rounded hover-effect">My Submissions</a>
                <a href="shared_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">Shared Syllabus</a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">USER MANAGEMENT</div>
                <a href="registration_requests.php" class="nav-link text-white p-3 rounded hover-effect">
                    Registration Requests
                    <?php if ($reg_count > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $reg_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="manage_user.php" class="nav-link text-white p-3 rounded hover-effect">Manage Users</a>
                <a href="add_user.php" class="nav-link text-white p-3 rounded hover-effect">Add User</a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
                <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect">Profile</a>
                <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5 logout-link">Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-grow-1 p-5" style="margin-left:260px;">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="text-orange font-serif fw-bold mb-0">Manage Courses</h2>
                    <p class="text-muted">Institutional course list for the Syllabus Portal</p>
                </div>
                <a href="add_course.php" class="btn btn-orange rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-plus-lg me-2"></i>Add New Course
                </a>
            </div>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm p-3 mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> Course successfully deleted.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card premium-card p-4 shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr class="small text-muted text-uppercase border-bottom">
                                <th class="py-3">#</th>
                                <th class="py-3">Course Code</th>
                                <th class="py-3">Course Title</th>
                                <th class="py-3">College</th>
                                <th class="py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($courses)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">No courses found.</td>
                                </tr>
                            <?php else: 
                                $c = 1;
                                foreach ($courses as $course): ?>
                                    <tr class="border-bottom">
                                        <td class="small"><?= $c++ ?></td>
                                        <td class="fw-bold text-dark small"><?= htmlspecialchars($course['course_code']) ?></td>
                                        <td class="small"><?= htmlspecialchars($course['course_title']) ?></td>
                                        <td class="small">
                                            <span class="badge bg-light text-dark border rounded-pill px-3">
                                                <?= htmlspecialchars($course['college_name'] ?? 'Unassigned') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a href="edit_course.php?id=<?= $course['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-2" style="font-size: .75rem;">
                                                    <i class="bi bi-pencil me-1"></i> Edit
                                                </a>
                                                <a href="#" 
                                                   class="btn btn-sm btn-outline-danger rounded-pill px-3" 
                                                   style="font-size: .75rem;"
                                                   onclick="confirmCourseDelete(<?= $course['id'] ?>, '<?= htmlspecialchars($course['course_code']) ?>')">
                                                    <i class="bi bi-trash me-1"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmCourseDelete(courseId, courseCode) {
            Swal.fire({
                title: 'Are you sure?',
                text: `You are about to delete course ${courseCode}. This cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `?delete=${courseId}`;
                }
            });
        }

        function confirmLogout() {
            Swal.fire({
                title: 'Sign Out?',
                text: "Are you sure you want to end your current session?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff8800',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Logout',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../logout.php';
                }
            });
            return false;
        }
    </script>
</body>
</html>
