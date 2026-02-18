<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Initialize session-based "database" for prototype if empty or contains dummy data
if (!isset($_SESSION['submissions']) || (isset($_SESSION['submissions'][0]['uploader_name']) && $_SESSION['submissions'][0]['uploader_name'] === 'Alice Johnson')) {
    $_SESSION['submissions'] = [];
}

$submissions = $_SESSION['submissions'] ?? [];
$approved = array_filter($submissions, fn($s) => $s['status'] == 'Approved');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Syllabus Vault - SCC VPAA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
</head>

<body class="bg-light">
    <div class="d-flex">
        <div class="sidebar sidebar-premium text-white p-2 min-vh-100 d-flex flex-column"
            style="width: 260px; position: fixed;">
            <div class="text-center mb-3 mt-2">
                <img src="../css/logo.png" alt="CCS Logo" class="rounded-circle mb-2"
                    style="width: 80px; height: 80px;">
                <h5 class="font-serif fw-bold text-orange">VPAA Vault</h5>
            </div>
            <nav class="nav flex-column gap-2 mb-auto">
                <a href="vpaa_dashboard.php" class="nav-link text-white p-3 rounded">Back to Dashboard</a>
            </nav>
        </div>

        <div class="main-content flex-grow-1 p-5" style="margin-left: 260px;">
            <h2 class="text-orange font-serif fw-bold mb-4">Accreditation Vault (Approved Syllabi)</h2>
            <p class="text-muted">Repository of all validated and archived syllabi for CCS accreditation audits.</p>

            <div class="card premium-card p-4 border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>Code</th>
                                <th>Course Title</th>
                                <th>Instructor</th>
                                <th>Archived On</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($approved)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5">No approved syllabi available for vault access
                                        yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($approved as $s): ?>
                                    <tr>
                                        <td><strong>
                                                <?php echo $s['course_code']; ?>
                                            </strong></td>
                                        <td>
                                            <?php echo $s['course_title']; ?>
                                        </td>
                                        <td>
                                            <?php echo $s['uploader_name']; ?>
                                        </td>
                                        <td>2024-02-18</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-orange rounded-pill"><i
                                                    class="bi bi-download me-1"></i>Download</button>
                                            <button class="btn btn-sm btn-outline-dark rounded-pill"><i
                                                    class="bi bi-eye me-1"></i>Preview</button>
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
</body>

</html>