<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Get user information from session
$username = $_SESSION['username'] ?? 'User';
$email = $_SESSION['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submissions - SCC-CCS Syllabus Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Inter:wght@400;600&display=swap"
        rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
</head>

<body class="bg-light">

    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar bg-black text-white p-3 min-vh-100 d-flex flex-column"
            style="width: 280px; position: fixed;">
            <div class="text-center mb-4 mt-3">
                <img src="../css/logo.png" alt="CCS Logo" class="rounded-circle mb-2"
                    style="width: 80px; height: 80px;">
                <h4 class="font-serif fw-bold">CCS Panel</h4>
            </div>

            <nav class="nav flex-column gap-2 mb-auto">
                <a href="faculty_dashboard.php" class="nav-link text-white p-3 rounded hover-effect">
                    Dashboard
                </a>
                <a href="upload_syllabus.php" class="nav-link text-white p-3 rounded hover-effect">
                    Upload Syllabus
                </a>
                <a href="my_submissions.php" class="nav-link text-white active-nav-link p-3 rounded">
                    My Submissions
                </a>
                <a href="profile.php" class="nav-link text-white p-3 rounded hover-effect">
                    Profile
                </a>
                <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">
                    Logout
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-grow-1 p-5" style="margin-left: 280px;">
            <h2 class="text-orange font-serif fw-bold mb-4">My Syllabus Submissions</h2>

            <!-- Submissions Table -->
            <div class="card border-0 shadow-sm p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="text-secondary small">#</th>
                                <th scope="col" class="text-secondary small">COURSE CODE</th>
                                <th scope="col" class="text-secondary small">TITLE</th>
                                <th scope="col" class="text-secondary small">SUBJECT TYPE</th>
                                <th scope="col" class="text-secondary small">SEMESTER</th>
                                <th scope="col" class="text-secondary small">YEAR</th>
                                <th scope="col" class="text-secondary small">STATUS</th>
                                <th scope="col" class="text-secondary small">REVIEWER</th>
                                <th scope="col" class="text-secondary small">COMMENT</th>
                                <th scope="col" class="text-secondary small">FILE</th>
                                <th scope="col" class="text-secondary small">SUBMITTED ON</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // TODO: Replace this with actual database query
                            // For now, using an empty array to demonstrate the empty state
                            $submissions = []; // This should come from database
                            
                            if (empty($submissions)) {
                                // Show empty state
                                echo '<tr>';
                                echo '<td colspan="11" class="text-center text-muted py-4">No submissions found</td>';
                                echo '</tr>';
                            } else {
                                $counter = 1;
                                foreach ($submissions as $submission) {
                                    echo '<tr class="bg-light-gray">';
                                    echo '<td>' . $counter . '</td>';
                                    echo '<td>' . htmlspecialchars($submission['course_code']) . '</td>';
                                    echo '<td>' . htmlspecialchars($submission['title']) . '</td>';
                                    echo '<td>' . htmlspecialchars($submission['subject_type']) . '</td>';
                                    echo '<td>' . htmlspecialchars($submission['semester']) . '</td>';
                                    echo '<td>' . htmlspecialchars($submission['year']) . '</td>';

                                    // Status badge with color
                                    $statusClass = '';
                                    if ($submission['status'] == 'Pending') {
                                        $statusClass = 'bg-warning text-dark bg-opacity-25 border border-warning';
                                    } elseif ($submission['status'] == 'Approved') {
                                        $statusClass = 'bg-success text-success bg-opacity-25 border border-success';
                                    } elseif ($submission['status'] == 'Rejected') {
                                        $statusClass = 'bg-danger text-danger bg-opacity-25 border border-danger';
                                    }
                                    echo '<td><span class="badge ' . $statusClass . ' rounded-pill px-3">' . htmlspecialchars($submission['status']) . '</span></td>';

                                    echo '<td>' . ($submission['reviewer'] ?? '—') . '</td>';
                                    echo '<td>' . ($submission['comment'] ?? '—') . '</td>';
                                    echo '<td><a href="' . htmlspecialchars($submission['file_path']) . '" class="text-orange text-decoration-underline">Preview</a></td>';
                                    echo '<td>' . htmlspecialchars($submission['submitted_on']) . '</td>';
                                    echo '</tr>';
                                    $counter++;
                                }
                            }

                            /* EXAMPLE FORMAT - What a submission row looks like:
                            <tr class="bg-light-gray">
                                <td>1</td>
                                <td>HIS100</td>
                                <td>History</td>
                                <td>General Education (GE)</td>
                                <td>1st</td>
                                <td>2025-2026</td>
                                <td><span class="badge bg-warning text-dark bg-opacity-25 border border-warning rounded-pill px-3">Pending</span></td>
                                <td>—</td>
                                <td>—</td>
                                <td><a href="#" class="text-orange text-decoration-underline">Preview</a></td>
                                <td>Dec 17, 2025</td>
                            </tr>
                            */
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>