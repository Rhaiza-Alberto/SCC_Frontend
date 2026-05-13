<?php
/**
 * faculty/shared_syllabus.php
 * Displays all VPAA-approved syllabi shared across faculty and dean.
 */
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

restrict_to_role('faculty');

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$role_display = 'Faculty Panel';

if (isset($_GET['mark_read'])) {
    mark_all_notifications_read($user_id);
    header('Location: shared_syllabus.php');
    exit();
}

$unread_count = count_unread_notifications($user_id);
$notifications = get_notifications($user_id, 5);

/* ── Fetch approved syllabi (college-scoped) ── */
$approved_syllabi = get_shared_syllabi($_SESSION['college_id'] ?? null);

/* ── Unique filter values ── */
$colleges = array_unique(array_filter(array_column($approved_syllabi, 'college_name')));
$semesters = array_unique(array_filter(array_column($approved_syllabi, 'semester')));
$years = array_unique(array_filter(array_column($approved_syllabi, 'school_year')));
sort($colleges);
sort($semesters);
rsort($years);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared Syllabus — SCC Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php $active_page = 'shared'; include '_sidebar.php'; ?>

    <main class="scc-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color:var(--text)">Shared <span style="color:var(--primary)">Syllabus</span></h4>
                <p style="font-size:0.85rem;color:var(--text-secondary);margin:0">Approved institutional syllabi repository</p>
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
            </div>
        </div>

            <!-- Stats Row -->
            <div class="row g-3 mb-4 animate-in">
                <div class="col-12 col-md-4">
                    <div class="scc-stat p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background:rgba(34,197,94,0.1);color:var(--success)"><i class="bi bi-journal-check"></i></div>
                            <div>
                                <h3 class="stat-value mb-0"><?= count($approved_syllabi) ?></h3>
                                <p class="stat-label mb-0">Approved Syllabi</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="scc-stat p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background:rgba(59,130,246,0.1);color:var(--primary)"><i class="bi bi-people"></i></div>
                            <div>
                                <h3 class="stat-value mb-0"><?= count(array_unique(array_column($approved_syllabi, 'faculty_name'))) ?></h3>
                                <p class="stat-label mb-0">Contributing Faculty</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="scc-stat p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background:rgba(245,158,11,0.1);color:var(--warning)"><i class="bi bi-building"></i></div>
                            <div>
                                <h3 class="stat-value mb-0"><?= count($colleges) ?></h3>
                                <p class="stat-label mb-0">Colleges</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter / Search Bar -->
            <div class="scc-card p-3 mb-4 animate-in">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-5">
                        <label class="form-label small fw-bold text-muted mb-1">Search repository</label>
                        <div class="position-relative">
                            <i class="bi bi-search position-absolute" style="left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
                            <input type="text" id="searchInput" class="form-control ps-5" placeholder="Course code, title, or faculty…" style="border-radius:var(--radius-sm);background:var(--bg-card);border:1px solid var(--border); padding:0.6rem 0.6rem 0.6rem 2.5rem;">
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Semester</label>
                        <select id="semFilter" class="form-select" style="border-radius:var(--radius-sm);background:var(--bg-card);border:1px solid var(--border); padding:0.6rem;">
                            <option value="">All Semesters</option>
                            <?php foreach ($semesters as $sem): ?>
                                <option value="<?= htmlspecialchars($sem) ?>"><?= htmlspecialchars($sem) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">School Year</label>
                        <select id="yearFilter" class="form-select" style="border-radius:var(--radius-sm);background:var(--bg-card);border:1px solid var(--border); padding:0.6rem;">
                            <option value="">All School Years</option>
                            <?php foreach ($years as $yr): ?>
                                <option value="<?= htmlspecialchars($yr) ?>"><?= htmlspecialchars($yr) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-1 d-flex align-items-end">
                        <button class="btn btn-outline-scc w-100" id="clearFilters" title="Clear filters" style="border-radius:var(--radius-sm); padding:0.6rem;">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Syllabi Table -->
            <div class="scc-card animate-in">
                <div class="card-body p-0">
                    <?php if (empty($approved_syllabi)): ?>
                        <div class="text-center py-5" style="color:var(--text-muted)">
                            <i class="bi bi-folder2-open fs-1 mb-3 d-block opacity-50"></i>
                            <p class="mb-0">No approved syllabi yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="scc-table" id="syllabi-table">
                                <thead>
                                    <tr>
                                        <th class="text-secondary small ps-4">#</th>
                                        <th class="text-secondary small">COURSE</th>
                                        <th class="text-secondary small">FACULTY</th>
                                        <th class="text-secondary small d-none d-lg-table-cell">COLLEGE</th>
                                        <th class="text-secondary small d-none d-md-table-cell">SEMESTER</th>
                                        <th class="text-secondary small d-none d-xl-table-cell">SCHOOL YEAR</th>
                                        <th class="text-secondary small d-none d-md-table-cell">SUBMITTED</th>
                                        <th class="text-secondary small text-center">STATUS</th>
                                        <th class="text-secondary small text-center pe-4">FILE</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($approved_syllabi as $i => $row): ?>
                                        <tr data-search="<?= strtolower(htmlspecialchars($row['course_code'] . ' ' . $row['course_title'] . ' ' . $row['faculty_name'])) ?>"
                                            data-dept="<?= htmlspecialchars($row['college_name'] ?? '') ?>"
                                            data-sem="<?= htmlspecialchars($row['semester'] ?? '') ?>"
                                            data-year="<?= htmlspecialchars($row['school_year'] ?? '') ?>">
                                            <td class="ps-4 text-muted small"><?= $i + 1 ?></td>
                                            <td>
                                                <div class="fw-bold small"><?= htmlspecialchars($row['course_code']) ?></div>
                                                <div class="text-muted" style="font-size:.72rem;max-width:180px;"
                                                    class="text-truncate">
                                                    <?= htmlspecialchars($row['course_title']) ?>
                                                </div>
                                            </td>
                                            <td class="small"><?= htmlspecialchars($row['faculty_name']) ?></td>
                                            <td class="small d-none d-lg-table-cell text-muted">
                                                <?= htmlspecialchars($row['college_name'] ?? '—') ?>
                                            </td>
                                            <td class="small d-none d-md-table-cell">
                                                <?= htmlspecialchars($row['semester'] ?? '—') ?>
                                            </td>
                                            <td class="small d-none d-xl-table-cell">
                                                <?= htmlspecialchars($row['school_year'] ?? '—') ?>
                                            </td>
                                            <td class="small d-none d-md-table-cell text-muted">
                                                <?= date('M d, Y', strtotime($row['submitted_at'])) ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge-status bg-success-subtle text-success">
                                                    <i class="bi bi-check-circle-fill me-1"></i>Approved
                                                </span>
                                            </td>
                                            <td class="text-center pe-4">
                                                <a href="view_syllabus.php?file=<?= urlencode(basename($row['file_path'])) ?>"
                                                    target="_blank" rel="noopener" class="btn btn-sm btn-link text-orange p-0"
                                                    title="View PDF">
                                                    <i class="bi bi-file-earmark-pdf fs-5"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div id="noResults" class="text-center py-5 text-muted d-none">
                            <i class="bi bi-search fs-2 mb-2 d-block opacity-50"></i>
                            No syllabi match your filters.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/common.js"></script>
    <script>
    function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('active');}
    </script>
    <script>
        (function () {
            const searchInput = document.getElementById('searchInput');
            const semFilter = document.getElementById('semFilter');
            const yearFilter = document.getElementById('yearFilter');
            const clearBtn = document.getElementById('clearFilters');
            const table = document.getElementById('syllabi-table');
            const noResults = document.getElementById('noResults');

            if (!table) return;

            function applyFilters() {
                const q = searchInput.value.toLowerCase().trim();
                const sem = semFilter.value;
                const yr = yearFilter.value;
                let visible = 0;

                table.querySelectorAll('tbody tr').forEach(row => {
                    const matchSearch = !q || row.dataset.search.includes(q);
                    const matchSem = !sem || row.dataset.sem === sem;
                    const matchYear = !yr || row.dataset.year === yr;
                    const show = matchSearch && matchSem && matchYear;
                    row.style.display = show ? '' : 'none';
                    if (show) visible++;
                });

                noResults.classList.toggle('d-none', visible > 0);
            }

            [searchInput, semFilter, yearFilter].forEach(el =>
                el.addEventListener('input', applyFilters));

            clearBtn.addEventListener('click', () => {
                searchInput.value = '';
                semFilter.value = '';
                yearFilter.value = '';
                applyFilters();
            });
        })();
    </script>
</body>

</html>