<?php
/**
 * my_submissions.php 
 */
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

ensure_role_in_session();

$user_id      = $_SESSION['user_id'];
$username     = $_SESSION['username'] ?? 'User';
$role_display = "Dean's Panel";

if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: my_submissions.php');
    exit();
}

$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

$all = get_faculty_submissions($user_id);

$pending  = array_values(array_filter($all, fn($s) => $s['status'] === 'Pending'));
$approved = array_values(array_filter($all, fn($s) => $s['status'] === 'Approved'));
$rejected = array_values(array_filter($all, fn($s) => $s['status'] === 'Rejected'));

$unread_count  = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);

$conn = get_db();
$pending_review_count = (int) $conn->query("
    SELECT COUNT(DISTINCT sw.syllabus_id)
    FROM syllabus_workflow sw
    JOIN roles r ON sw.role_id = r.id
    WHERE r.role_name = 'dean' AND sw.action = 'Pending'
")->fetchColumn();

$reg_count = (int) $conn->query("SELECT COUNT(*) FROM users WHERE is_approved = 0 AND is_deleted = 0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submissions — SCC Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .position-relative.mb-4 {
            z-index: 1070 !important;
        }
        .dropdown-menu {
            z-index: 1080 !important;
        }
        .scc-tab-search-wrapper {
            overflow: visible !important;
            position: relative;
            z-index: 1050;
        }
    </style>
</head>
<body>
    <?php $active_page = 'submissions'; include '_sidebar.php'; ?>

    <main class="scc-main">
        <div class="mb-4 position-relative" style="z-index: 1070;">
            <nav aria-label="breadcrumb" class="animate-in" style="--animation-order:1">
                <ol class="breadcrumb mb-2" style="font-size:0.75rem;letter-spacing:0.5px;text-transform:uppercase;">
                    <li class="breadcrumb-item"><a href="admin_dashboard.php" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item active fw-bold" style="color:var(--primary)" aria-current="page">My Submissions</li>
                </ol>
            </nav>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 animate-in" style="--animation-order:2">
                <div>
                    <h2 class="fw-bold mb-1" style="color:var(--text);letter-spacing:-0.5px;">My <span class="text-orange">Submissions</span></h2>
                    <p class="text-secondary mb-0" style="font-size:0.95rem;">Track and manage your institutional syllabus submission status.</p>
                </div>
                <div class="d-flex align-items-center gap-3" style="position: relative; z-index: 1075;">
                    <div class="dropdown">
                        <div class="position-relative" style="cursor:pointer" data-bs-toggle="dropdown">
                            <i class="bi bi-bell fs-5" style="color:var(--text)"></i>
                            <?php if ($unread_count > 0): ?>
                                <span class="notif-badge" style="top:-2px;right:-2px;"><?= $unread_count > 9 ? '9+' : $unread_count ?></span>
                            <?php endif; ?>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 animate-in" style="width:340px;max-height:420px;overflow-y:auto;border-radius:var(--radius-md);background:var(--bg-card);--animation-order:1; z-index: 1080 !important;">
                            <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom sticky-top" style="background:var(--bg-card); z-index: 12;">
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
                </div>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4 rounded-3 border-0 shadow-sm animate-in" style="--animation-order:1">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="scc-tab-search-wrapper animate-in flex-wrap gap-3" style="--animation-order:3; overflow: visible !important;">
            <div class="scc-tabs-container" id="submissionTabs" role="tablist">
                <button class="scc-tab-item tab-orange active" data-bs-toggle="tab" data-bs-target="#tabPending" type="button">
                    <span class="tab-indicator"></span> Pending <span class="tab-count"><?= count($pending) ?></span>
                </button>
                <button class="scc-tab-item tab-green" data-bs-toggle="tab" data-bs-target="#tabApproved" type="button">
                    <span class="tab-indicator"></span> Approved <span class="tab-count"><?= count($approved) ?></span>
                </button>
                <button class="scc-tab-item tab-red" data-bs-toggle="tab" data-bs-target="#tabDeclined" type="button">
                    <span class="tab-indicator"></span> Declined <span class="tab-count"><?= count($rejected) ?></span>
                </button>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center w-100 w-md-auto">
                <div class="position-relative search-container" style="width:100%;max-width:240px;">
                    <i class="bi bi-search position-absolute" style="left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
                    <input type="text" id="submissionSearch" class="form-control ps-5" placeholder="Filter current list..." style="border-radius:var(--radius-md);background:var(--bg-card);border:1px solid var(--border);height:45px;">
                </div>
                <select id="filterSubjectType" class="form-select form-select-sm" style="width: 140px; height: 45px; border-radius: var(--radius-md); background:var(--bg-card); border:1px solid var(--border);">
                    <option value="">All Categories</option>
                    <option value="Institutional Subject">Institutional Subject</option>
                    <option value="General Education (GE)">General Education (GE)</option>
                    <option value="Core Subject">Core Subject</option>
                    <option value="Professional Subjects">Professional Subjects</option>
                    <option value="Mandatory / Elect Subject">Mandatory / Elect Subject</option>
                </select>
                <select id="filterSemester" class="form-select form-select-sm" style="width: 130px; height: 45px; border-radius: var(--radius-md); background:var(--bg-card); border:1px solid var(--border);">
                    <option value="">All Semesters</option>
                    <option value="1st Semester">1st Semester</option>
                    <option value="2nd Semester">2nd Semester</option>
                    <option value="Summer">Summer</option>
                </select>
                <select id="filterYearLevel" class="form-select form-select-sm" style="width: 120px; height: 45px; border-radius: var(--radius-md); background:var(--bg-card); border:1px solid var(--border);">
                    <option value="">All Years</option>
                    <option value="1st Year">1st Year</option>
                    <option value="2nd Year">2nd Year</option>
                    <option value="3rd Year">3rd Year</option>
                    <option value="4th Year">4th Year</option>
                </select>
            </div>
        </div>

        <div class="tab-content pt-1">
            <div class="tab-pane fade show active" id="tabPending">
                <?php if (empty($pending)): ?>
                    <div class="scc-card p-5 text-center animate-in" style="--animation-order:4">
                        <div class="mb-3"><i class="bi bi-inbox text-muted" style="font-size:3rem;opacity:0.3"></i></div>
                        <h5 class="fw-bold" style="color:var(--text)">No Pending Submissions</h5>
                        <p class="text-secondary mb-0">You don't have any syllabi currently awaiting review.</p>
                    </div>
                <?php else: ?>
                    <div class="scc-card scc-premium-shadow border-0 animate-in" style="--animation-order:4">
                        <div class="table-responsive">
                            <table class="scc-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Course Details</th>
                                        <th class="d-none d-md-table-cell">Year Level</th>
                                        <th>Status</th>
                                        <th class="text-center">Syllabus</th>
                                        <th>Submitted</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending as $sub): ?>
                                        <tr class="submission-item" 
                                            data-search="<?= strtolower(htmlspecialchars($sub['course_code'] . ' ' . $sub['course_title'])) ?>"
                                            data-subject-type="<?= htmlspecialchars($sub['subject_type'] ?? '') ?>"
                                            data-semester="<?= htmlspecialchars($sub['semester'] ?? '') ?>"
                                            data-year-level="<?= htmlspecialchars($sub['year_level'] ?? '') ?>">
                                            <td>
                                                <div class="fw-bold small" style="color:var(--text)"><?= htmlspecialchars($sub['course_code']) ?></div>
                                                <div class="text-muted text-truncate" style="font-size:0.7rem;max-width:200px;"><?= htmlspecialchars($sub['course_title']) ?></div>
                                            </td>
                                            <td class="d-none d-md-table-cell small"><?= htmlspecialchars($sub['year_level'] ?? '&mdash;') ?></td>
                                            <td><?= format_syllabus_status($sub['status'], $sub['current_stage_role'] ?? null) ?></td>
                                            <td class="text-center">
                                                <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($sub['file_path'])) ?>" target="_blank" class="text-primary d-inline-block"><i class="bi bi-file-earmark-pdf fs-5"></i></a>
                                            </td>
                                            <td class="small text-muted"><?= date('M d, Y', strtotime($sub['submitted_at'])) ?></td>
                                            <td class="text-center text-nowrap">
                                                <button type="button" onclick="showTrackerModal(<?= (int)$sub['id'] ?>, '<?= htmlspecialchars($sub['course_code'], ENT_QUOTES) ?>', '<?= htmlspecialchars($sub['course_title'], ENT_QUOTES) ?>')" class="btn btn-sm btn-light border px-2 py-1 text-primary shadow-sm" title="Track Progress"><i class="bi bi-geo-alt"></i></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade" id="tabApproved">
                <?php if (empty($approved)): ?>
                    <div class="scc-card p-5 text-center animate-in">
                        <div class="mb-3"><i class="bi bi-check2-all text-success" style="font-size:3rem;opacity:0.3"></i></div>
                        <h5 class="fw-bold" style="color:var(--text)">No Approved Syllabi</h5>
                        <p class="text-secondary mb-0">Your approved submissions will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="scc-card scc-premium-shadow border-0 animate-in">
                        <div class="table-responsive">
                            <table class="scc-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Course Details</th>
                                        <th class="d-none d-md-table-cell">Year Level</th>
                                        <th>Status</th>
                                        <th class="text-center">Syllabus</th>
                                        <th>Approved At</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($approved as $sub): ?>
                                        <tr class="submission-item" 
                                            data-search="<?= strtolower(htmlspecialchars($sub['course_code'] . ' ' . $sub['course_title'])) ?>"
                                            data-subject-type="<?= htmlspecialchars($sub['subject_type'] ?? '') ?>"
                                            data-semester="<?= htmlspecialchars($sub['semester'] ?? '') ?>"
                                            data-year-level="<?= htmlspecialchars($sub['year_level'] ?? '') ?>">
                                            <td>
                                                <div class="fw-bold small" style="color:var(--text)"><?= htmlspecialchars($sub['course_code']) ?></div>
                                                <div class="text-muted text-truncate" style="font-size:0.7rem;max-width:200px;"><?= htmlspecialchars($sub['course_title']) ?></div>
                                            </td>
                                            <td class="d-none d-md-table-cell small"><?= htmlspecialchars($sub['year_level'] ?? '&mdash;') ?></td>
                                            <td><?= format_syllabus_status($sub['status']) ?></td>
                                            <td class="text-center">
                                                <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($sub['file_path'])) ?>" target="_blank" class="text-success d-inline-block"><i class="bi bi-file-earmark-pdf fs-5"></i></a>
                                            </td>
                                            <td class="small text-muted"><?= date('M d, Y', strtotime($sub['submitted_at'])) ?></td>
                                            <td class="text-center">
                                                <button type="button" onclick="showTrackerModal(<?= (int)$sub['id'] ?>, '<?= htmlspecialchars($sub['course_code'], ENT_QUOTES) ?>', '<?= htmlspecialchars($sub['course_title'], ENT_QUOTES) ?>')" class="btn btn-sm btn-light border px-2 py-1 text-primary shadow-sm" title="Track Progress"><i class="bi bi-geo-alt"></i></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade" id="tabDeclined">
                <?php if (empty($rejected)): ?>
                    <div class="scc-card p-5 text-center animate-in">
                        <div class="mb-3"><i class="bi bi-x-circle text-muted" style="font-size:3rem;opacity:0.3"></i></div>
                        <h5 class="fw-bold" style="color:var(--text)">No Declined Syllabi</h5>
                        <p class="text-secondary mb-0">Submissions that require revision will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="scc-card scc-premium-shadow border-0 animate-in">
                        <div class="table-responsive">
                            <table class="scc-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Course Details</th>
                                        <th>Status</th>
                                        <th>Reason</th>
                                        <th class="text-center">Syllabus</th>
                                        <th class="text-center">Revision</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rejected as $sub): ?>
                                        <tr class="submission-item" 
                                            data-search="<?= strtolower(htmlspecialchars($sub['course_code'] . ' ' . $sub['course_title'])) ?>"
                                            data-subject-type="<?= htmlspecialchars($sub['subject_type'] ?? '') ?>"
                                            data-semester="<?= htmlspecialchars($sub['semester'] ?? '') ?>"
                                            data-year-level="<?= htmlspecialchars($sub['year_level'] ?? '') ?>">
                                            <td>
                                                <div class="fw-bold small" style="color:var(--text)"><?= htmlspecialchars($sub['course_code']) ?></div>
                                                <div class="text-muted text-truncate" style="font-size:0.7rem;max-width:200px;"><?= htmlspecialchars($sub['course_title']) ?></div>
                                            </td>
                                            <td><?= format_syllabus_status($sub['status'], null, $sub['rejecting_role'] ?? null, $sub['rejecting_name'] ?? null) ?></td>
                                            <td class="small text-danger fw-medium"><?= htmlspecialchars($sub['reject_comment'] ?? 'No reason provided') ?></td>
                                            <td class="text-center">
                                                <a href="../faculty/view_syllabus.php?file=<?= urlencode(basename($sub['file_path'])) ?>" target="_blank" class="text-danger d-inline-block"><i class="bi bi-file-earmark-pdf fs-5"></i></a>
                                            </td>
                                            <td class="text-center text-nowrap">
                                                <button type="button" onclick="showTrackerModal(<?= (int)$sub['id'] ?>, '<?= htmlspecialchars($sub['course_code'], ENT_QUOTES) ?>', '<?= htmlspecialchars($sub['course_title'], ENT_QUOTES) ?>')" class="btn btn-sm btn-light border px-2 py-1 text-primary shadow-sm me-1" title="Track Progress"><i class="bi bi-geo-alt"></i></button>
                                                <a href="edit_syllabus.php?id=<?= $sub['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3"><i class="bi bi-pencil-square me-1"></i> Revise</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/common.js"></script>
    <script>
    function applyTableFilters() {
        const query = document.getElementById('submissionSearch').value.toLowerCase().trim();
        const subjectVal = document.getElementById('filterSubjectType').value;
        const semVal = document.getElementById('filterSemester').value;
        const yearVal = document.getElementById('filterYearLevel').value;
        
        const activePane = document.querySelector('.tab-pane.active') || document;
        const items = activePane.querySelectorAll('.submission-item');
        
        items.forEach(item => {
            const text = item.getAttribute('data-search') || '';
            const rowSubject = item.getAttribute('data-subject-type') || '';
            const rowSem = item.getAttribute('data-semester') || '';
            const rowYear = item.getAttribute('data-year-level') || '';
            
            const matchesSearch = text.includes(query);
            const matchesSubject = !subjectVal || rowSubject === subjectVal;
            const matchesSem = !semVal || rowSem === semVal;
            const matchesYear = !yearVal || rowYear === yearVal;
            
            if (matchesSearch && matchesSubject && matchesSem && matchesYear) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }

    document.getElementById('submissionSearch').addEventListener('input', applyTableFilters);
    document.getElementById('filterSubjectType').addEventListener('change', applyTableFilters);
    document.getElementById('filterSemester').addEventListener('change', applyTableFilters);
    document.getElementById('filterYearLevel').addEventListener('change', applyTableFilters);

    document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tabEl => {
        tabEl.addEventListener('shown.bs.tab', () => {
            applyTableFilters();
        });
    });

    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('active');
    }
    </script>
    <?php include __DIR__ . '/../_tracker_modal.php'; ?>
</body>
</html>