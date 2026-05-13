import re

with open('original_faculty_dashboard.php', 'r', encoding='utf-8') as f:
    orig = f.read()

with open('faculty/faculty_dashboard.php', 'r', encoding='utf-8') as f:
    current = f.read()

# We need to construct the new content.
# We'll take everything up to <!-- Pending Alert --> from `current`.
head_match = re.search(r'(.*?)<!-- Pending Alert -->', current, re.DOTALL)
top = head_match.group(1)

# Now grab the Pending Alert from `current`
pending_match = re.search(r'(<!-- Pending Alert -->.*?)<!-- Table Filters -->', current, re.DOTALL)
pending = pending_match.group(1).strip()

# Now we need the Stats Cards from `orig` but adapted to scc-card
stats_html = """
            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <?php
                $stats = [
                    ['label' => 'Total Submissions', 'value' => $total, 'color' => '#ff8800', 'icon' => 'bi-files', 'sub' => 'All uploaded syllabi', 'link' => 'my_submissions.php'],
                    ['label' => 'Approved', 'value' => $approved, 'color' => '#28a745', 'icon' => 'bi-check-circle', 'sub' => 'Validated content', 'link' => 'my_submissions.php'],
                    ['label' => 'Pending', 'value' => $pending, 'color' => '#ffc107', 'icon' => 'bi-clock-history', 'sub' => 'Awaiting review', 'link' => 'my_submissions.php'],
                    ['label' => 'Rejected', 'value' => $rejected, 'color' => '#dc3545', 'icon' => 'bi-x-circle', 'sub' => 'Needs revision', 'link' => 'my_submissions.php'],
                ];
                foreach ($stats as $s): ?>
                    <div class="col-md-3">
                        <div class="card stat-card shadow-sm border-0 bg-white cursor-pointer"
                             onclick="location.href='<?= $s['link'] ?>'"
                            style="border-left:5px solid <?= $s['color'] ?> !important; cursor:pointer;">
                            <div class="stat-card-content p-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="text-uppercase fw-bold text-muted small mb-0"><?= $s['label'] ?></h6>
                                    <div class="stat-icon"
                                        style="width:35px;height:35px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:<?= $s['color'] ?>15;color:<?= $s['color'] ?>">
                                        <i class="bi <?= $s['icon'] ?> fs-5"></i>
                                    </div>
                                </div>
                                <h3 class="fw-bold mb-1" style="color:var(--text);font-size:1.8rem"><?= $s['value'] ?></h3>
                                <p class="text-muted small mb-0" style="font-size:0.7rem"><?= $s['sub'] ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
"""

# Now we need Course Syllabi Status AND Recent Shared Syllabus in a row!
middle_html = """
            <div class="row g-4 mb-4">
                <!-- Course Syllabi Status -->
                <div class="col-xl-7">
                    <div class="scc-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h6 class="fw-bold mb-0">My Course Syllabi <span class="text-orange">Status</span></h6>
                            <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3 py-1 small">
                                <?= get_current_school_year() ?>
                            </span>
                        </div>
                        <?php if (empty($my_courses)): ?>
                            <div class="text-center py-4 text-muted small">No courses submitted yet.
                                <a href="upload_syllabus.php" style="color:var(--primary)">Upload one &rarr;</a>
                            </div>
                        <?php else:
                            foreach ($my_courses as $course):
                                $badge_class = match ($course['status']) {
                                    'Approved' => 'badge-approved',
                                    'Pending' => 'badge-pending',
                                    default => 'badge-rejected',
                                };
                                ?>
                                <div class="d-flex justify-content-between align-items-center p-3 mb-2 rounded-3"
                                    style="background:var(--bg-card);border:1px solid var(--border);border-left:4px solid var(--primary);">
                                    <div>
                                        <h6 class="mb-1 fw-bold" style="color:var(--text)"><?= htmlspecialchars($course['code']) ?></h6>
                                        <p class="text-muted small mb-0"><?= htmlspecialchars($course['title']) ?></p>
                                    </div>
                                    <span class="badge <?= $badge_class ?> rounded-pill px-3">
                                        <?= format_syllabus_status($course['status'], $course['current_role'], $course['rejecting_role'], $course['rejecting_name'] ?? null) ?>
                                    </span>
                                </div>
                            <?php endforeach; endif; ?>
                    </div>
                </div>

                <!-- Recent Shared Syllabus -->
                <div class="col-xl-5">
                    <div class="scc-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h6 class="fw-bold mb-0">Recent Shared <span class="text-orange">Syllabus</span></h6>
                            <a href="shared_syllabus.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                View All
                            </a>
                        </div>
                        <?php
                        $shared = array_slice(get_shared_syllabi($_SESSION['college_id'] ?? null), 0, 5);
                        ?>
                        <div class="table-responsive">
                            <table class="scc-table align-middle">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th class="text-center">File</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($shared)): ?>
                                        <tr>
                                            <td colspan="2" class="text-center text-muted py-4 small">
                                                No approved syllabi in the shared repository yet.
                                            </td>
                                        </tr>
                                    <?php else:
                                        foreach ($shared as $sh): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-bold small" style="color:var(--text)"><?= htmlspecialchars($sh['course_code']) ?></span>
                                                        <span class="text-muted text-truncate" style="font-size:.7rem;max-width:150px;">
                                                            <?= htmlspecialchars($sh['course_title']) ?>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <a href="view_syllabus.php?file=<?= urlencode(basename($sh['file_path'])) ?>" target="_blank" style="color:var(--primary)">
                                                        <i class="bi bi-file-earmark-pdf fs-5"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
"""

# Now grab the rest of the file from `current`
bottom_match = re.search(r'(<!-- Table Filters -->.*?</script>)', current, re.DOTALL)
bottom = bottom_match.group(1).strip()

# We need to add the Tracker Modal and the Action column back!
bottom = bottom.replace('<th>Submitted</th>', '<th>Submitted</th>\n                                    <th class="text-center">Action</th>')
bottom = bottom.replace('<td colspan="7"', '<td colspan="8"')
bottom = bottom.replace("<?= date('M d, Y', strtotime($sub['submitted_at'])) ?></td>", "<?= date('M d, Y', strtotime($sub['submitted_at'])) ?></td>\n                                            <td class=\"text-center\">\n                                                <button type=\"button\" onclick=\"showTrackerModal(<?= (int)$sub['id'] ?>, '<?= htmlspecialchars($sub['course_code'], ENT_QUOTES) ?>', '<?= htmlspecialchars($sub['course_title'], ENT_QUOTES) ?>')\" class=\"btn btn-sm btn-light border rounded-pill shadow-sm hover-lift text-primary fw-bold px-3\">\n                                                    <i class=\"bi bi-geo-alt me-1\"></i> Track\n                                                </button>\n                                            </td>")
bottom += "\n    <?php include __DIR__ . '/../_tracker_modal.php'; ?>"
bottom += "\n</body>\n</html>\n"

# Fix the missing div in the bottom part too! (which was what I fixed earlier)
bottom = bottom.replace("                    </div>\n                </div>\n            </div>\n\n    </main>", "                    </div>\n                </div>\n            </div>\n        </div>\n    </main>")

new_content = top + "\n" + pending + "\n\n" + stats_html + "\n" + middle_html + "\n" + bottom

with open('faculty/faculty_dashboard.php', 'w', encoding='utf-8') as f:
    f.write(new_content)
