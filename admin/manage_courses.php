<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

restrict_to_role('dean');

$username = $_SESSION['username'] ?? 'Dean / Admin';
$role_display = "Dean's Panel";
$user_id = $_SESSION['user_id'];

$conn = get_db();

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: manage_courses.php?deleted=true");
    exit();
}

$query = "SELECT c.*, col.college_name, d.department_name 
          FROM courses c 
          LEFT JOIN colleges col ON c.college_id = col.id 
          LEFT JOIN departments d ON c.department_id = d.id
          ORDER BY col.college_name, d.department_name, c.course_code";
$stmt = $conn->prepare($query);
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unread_count = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);

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
    <title>Manage Courses — SCC Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php $active_page = 'courses'; include '_sidebar.php'; ?>

    <main class="scc-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color:var(--text)">Manage <span style="color:var(--primary)">Courses</span></h4>
                <p style="font-size:0.85rem;color:var(--text-secondary);margin:0">Institutional course repository for syllabus generation</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <div class="position-relative" style="cursor:pointer" data-bs-toggle="dropdown">
                        <i class="bi bi-bell fs-5" style="color:var(--text)"></i>
                        <?php if ($unread_count > 0): ?><span class="notif-badge"><?= $unread_count > 9 ? '9+' : $unread_count ?></span><?php endif; ?>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="width:340px;max-height:420px;overflow-y:auto;border-radius:var(--radius-md);background:var(--bg-card)">
                        <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom sticky-top" style="background:var(--bg-card)">
                            <strong style="font-size:0.9rem;color:var(--text)">Notifications</strong>
                            <?php if ($unread_count > 0): ?><a href="?mark_read=1" class="text-decoration-none small" style="color:var(--primary)">Mark all read</a><?php endif; ?>
                        </li>
                        <?php if (empty($notifications)): ?>
                            <li class="px-3 py-4 text-center" style="color:var(--text-muted)"><i class="bi bi-bell-slash fs-4 d-block mb-2 opacity-50"></i><span class="small">No notifications</span></li>
                        <?php else: foreach ($notifications as $n): $color = get_notification_color($n['message']); ?>
                            <li class="border-bottom" style="<?= !$n['is_read'] ? 'background:var(--primary-light)' : '' ?>">
                                <a href="notifications.php?notif_id=<?= $n['id'] ?>" class="text-decoration-none d-block px-3 py-2">
                                    <p class="mb-0 small" style="color:var(--text)"><span class="<?= $color['text'] ?> fw-bold me-1"><?= $color['icon'] ?></span><?= htmlspecialchars($n['message']) ?></p>
                                    <span style="font-size:.7rem;color:var(--text-muted)"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></span>
                                </a>
                            </li>
                        <?php endforeach; endif; ?>
                        <li style="background:var(--bg-card);border-top:1px solid var(--border)"><a href="notifications.php" class="d-block text-center text-decoration-none small fw-bold py-2" style="color:var(--primary)">View all notifications</a></li>
                    </ul>
                </div>
                <a href="add_course.php" class="btn btn-primary rounded-pill px-4 fw-bold">
                    <i class="bi bi-plus-lg me-1"></i> Add Course
                </a>
            </div>
        </div>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm p-3 mb-4" role="alert" style="background:var(--success-light);color:var(--success)">
                <i class="bi bi-check-circle-fill me-2"></i> Course successfully removed from the repository.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="scc-card animate-in">
            <div class="card-header border-0 bg-transparent p-4 pb-0">
                <h6 class="fw-bold mb-0" style="color:var(--text)">Course <span style="color:var(--primary)">Catalog</span></h6>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="scc-table">
                        <thead>
                            <tr>
                                <th style="width: 50px">#</th>
                                <th>COURSE CODE</th>
                                <th>COURSE TITLE</th>
                                <th>COLLEGE</th>
                                <th>DEPARTMENT</th>
                                <th class="text-center">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($courses)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">No courses found.</td>
                                </tr>
                            <?php else: 
                                $c = 1;
                                foreach ($courses as $course): ?>
                                    <tr>
                                        <td class="small text-muted"><?= $c++ ?></td>
                                        <td><span class="fw-bold" style="color:var(--text)"><?= htmlspecialchars($course['course_code']) ?></span></td>
                                        <td class="small" style="color:var(--text-secondary)"><?= htmlspecialchars($course['course_title']) ?></td>
                                        <td>
                                            <span class="badge rounded-pill border px-3 py-1" style="font-size:0.7rem;background:var(--primary-light);color:var(--primary);border-color:var(--primary-light) !important">
                                                <?= htmlspecialchars($course['college_name'] ?? 'Unassigned') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill border px-3 py-1 bg-light text-dark border-secondary-subtle">
                                                <?= htmlspecialchars($course['department_name'] ?? 'General') ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="edit_course.php?id=<?= $course['id'] ?>" class="btn btn-sm btn-light border" title="Edit"><i class="bi bi-pencil"></i></a>
                                                <button class="btn btn-sm btn-light border text-danger" title="Delete" onclick="confirmCourseDelete(<?= $course['id'] ?>, '<?= htmlspecialchars($course['course_code']) ?>')"><i class="bi bi-trash"></i></button>
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
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/common.js"></script>
    <script>
        function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('active');}
        function confirmCourseDelete(courseId, courseCode) {
            Swal.fire({
                title: 'Delete Course?',
                html: `Are you sure you want to remove <strong>${courseCode}</strong>? This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `?delete=${courseId}`;
                }
            });
        }
    </script>
</body>
</html>