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
$dept_stats = ['CS' => ['total' => 0, 'approved' => 0], 'IT' => ['total' => 0, 'approved' => 0], 'IS' => ['total' => 0, 'approved' => 0]];

foreach ($submissions as $s) {
    if (isset($dept_stats[$s['department']])) {
        $dept_stats[$s['department']]['total']++;
        if ($s['status'] == 'Approved')
            $dept_stats[$s['department']]['approved']++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compliance Reports - SCC VPAA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
</head>

<body class="bg-light">
    <div class="d-flex">
        <!-- Sidebar (Simplified for report) -->
        <div class="sidebar sidebar-premium text-white p-2 min-vh-100 d-flex flex-column"
            style="width: 260px; position: fixed;">
            <div class="text-center mb-3 mt-2">
                <img src="../css/logo.png" alt="CCS Logo" class="rounded-circle mb-2"
                    style="width: 80px; height: 80px;">
                <h5 class="font-serif fw-bold text-orange">VPAA HUB</h5>
            </div>
            <nav class="nav flex-column gap-2 mb-auto">
                <a href="vpaa_dashboard.php" class="nav-link text-white p-3 rounded">Back to Dashboard</a>
            </nav>
        </div>

        <div class="main-content flex-grow-1 p-5" style="margin-left: 260px;">
            <h2 class="text-orange font-serif fw-bold mb-4">Departmental Compliance Reports</h2>

            <div class="row g-4">
                <?php foreach ($dept_stats as $dept => $data): ?>
                    <?php $pct = $data['total'] > 0 ? round(($data['approved'] / $data['total']) * 100) : 0; ?>
                    <div class="col-md-4">
                        <div class="card premium-card p-4 border-0 shadow-sm text-center">
                            <h4 class="fw-bold mb-3">
                                <?php echo $dept; ?>
                            </h4>
                            <div class="progress mb-3" style="height: 20px;">
                                <div class="progress-bar bg-success" style="width: <?php echo $pct; ?>%">
                                    <?php echo $pct; ?>%
                                </div>
                            </div>
                            <p class="text-muted small">Approved:
                                <?php echo $data['approved']; ?> /
                                <?php echo $data['total']; ?>
                            </p>
                            <span class="badge <?php echo $pct > 80 ? 'bg-success' : 'bg-warning'; ?> rounded-pill">
                                <?php echo $pct > 80 ? 'Ready for Audit' : 'Action Required'; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card premium-card p-4 mt-5 border-0 shadow-sm">
                <h5 class="font-serif fw-bold mb-3">Audit Log Preview</h5>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>INSTRUCTOR</th>
                            <th>DEPARTMENT</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($submissions, 0, 10) as $s): ?>
                            <tr>
                                <td>#
                                    <?php echo $s['id']; ?>
                                </td>
                                <td>
                                    <?php echo $s['uploader_name']; ?>
                                </td>
                                <td>
                                    <?php echo $s['department']; ?>
                                </td>
                                <td><span
                                        class="badge <?php echo $s['status'] == 'Approved' ? 'bg-success' : 'bg-warning'; ?>">
                                        <?php echo $s['status']; ?>
                                    </span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>