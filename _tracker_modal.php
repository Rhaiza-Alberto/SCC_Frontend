<div class="modal fade" id="trackerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: var(--radius-lg); overflow: hidden;">
            <div class="modal-header border-bottom-0 pb-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="modal-title fw-800 mb-0" style="color: var(--text); letter-spacing: -0.5px;">
                        <i class="bi bi-geo-alt-fill text-orange me-2"></i>Submission <span class="text-orange">Tracker</span>
                    </h5>
                    <p class="text-secondary small mb-0 mt-1" id="trackerCourseTitle">Loading course details...</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="trackerModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <p class="text-muted small">Fetching real-time tracking data...</p>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0 pb-4 px-4">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold shadow-sm" data-bs-dismiss="modal">Close Tracker</button>
            </div>
        </div>
    </div>
</div>

<script>
function showTrackerModal(syllabusId, courseCode, courseTitle) {
    const modal = new bootstrap.Modal(document.getElementById('trackerModal'));
    document.getElementById('trackerCourseTitle').innerHTML = `<strong>${courseCode}</strong> — ${courseTitle}`;
    document.getElementById('trackerModalBody').innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary mb-3" role="status"></div>
            <p class="text-muted small">Fetching real-time tracking data...</p>
        </div>
    `;
    modal.show();

    fetch(`../get_tracker_html.php?id=${syllabusId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('trackerModalBody').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('trackerModalBody').innerHTML = `
                <div class="alert alert-danger text-center m-4">
                    <i class="bi bi-exclamation-triangle fs-4 d-block mb-2"></i>
                    Failed to load tracking data. Please try again.
                </div>
            `;
        });
}
</script>
