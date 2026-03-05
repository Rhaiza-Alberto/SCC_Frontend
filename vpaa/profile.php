<?php
session_start();
require_once 'database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Get user information from session
$username = $_SESSION['username'] ?? 'VPAA';
$email = $_SESSION['email'] ?? '';
$role = $_SESSION['role'] ?? 'vpaa';
$role_display = 'VPAA';

// TODO: In a real application, fetch user profile data from database
// For now, using demo data
$profile = [
    'first_name' => 'John',
    'middle_name' => '',
    'last_name' => 'Doe',
    'birthdate' => '1990-12-01',
    'sex' => 'Male',
    'college' => 'College of Computing Studies',
    'department' => 'Department of Computer Science',
    'office' => 'Office of the Vice President for Academic Affairs',
    'email' => $email
];

$edit_mode = isset($_GET['edit']) && $_GET['edit'] == 'true';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VPAA Profile - SCC-CCS Syllabus Portal</title>
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
        <div class="sidebar sidebar-premium text-white p-2 min-vh-100 d-flex flex-column"
            style="width: 260px; position: fixed; z-index: 1100;">
            <div class="text-center mb-3 mt-2">
                <img src="../css/logo.png" alt="CCS Logo" class="rounded-circle mb-2"
                    style="width: 80px; height: 80px; border: 2px solid rgba(255, 136, 0, 0.5); padding: 3px;">
                <h5 class="font-serif fw-bold text-orange mb-0"><?php echo $role_display; ?></h5>
                <p class="text-white-50 small fw-bold mb-0" style="font-size: 0.75rem;">
                    <?php echo htmlspecialchars($username); ?>
                </p>
            </div>


                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">OVERVIEW</div>
                <a href="vpaa_dashboard.php" class="nav-link text-white p-3 rounded hover-effect text-decoration-none d-block">
                    Dashboard
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYLLABUS MANAGEMENT</div>
                <a href="syllabus_review.php" class="nav-link text-white p-3 rounded hover-effect text-decoration-none d-block">
                    Syllabus Review
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">ANALYTICS</div>
                <a href="compliance_reports.php" class="nav-link text-white p-3 rounded hover-effect text-decoration-none d-block">
                    Compliance Reports
                </a>
                <a href="syllabus_vault.php" class="nav-link text-white p-3 rounded hover-effect text-decoration-none d-block">
                    Syllabus Vault
                </a>

                <div class="sidebar-header-sm text-white-50 small fw-bold mb-1 ps-3 mt-4">SYSTEM</div>
                <a href="profile.php" class="nav-link text-white active-nav-link p-3 rounded text-decoration-none d-block">
                    Profile
                </a>
                <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5 text-decoration-none d-block">
                    Logout
                </a>
            </nav>
        </div>

        <div class="main-content flex-grow-1 p-5" style="margin-left: 260px;">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <h3 class="text-orange font-serif fw-bold mb-0">
                    <?php echo $edit_mode ? 'Edit my Profile' : 'My Profile'; ?>
                </h3>
                <div class="notification-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                        class="bi bi-bell" viewBox="0 0 16 16">
                        <path
                            d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zM8 1.918l-.797.161A4.002 4.002 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4.002 4.002 0 0 0-3.203-3.92L8 1.917zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5.002 5.002 0 0 1 13 6c0 .88.32 4.2 1.22 6z" />
                    </svg>
                    <span class="notification-badge-dot"></span>
                </div>
            </div>

            <div class="card premium-card shadow-sm p-5 bg-white mx-auto" style="max-width: 800px;">
                <p class="text-center text-muted small mb-4">Update your personal information</p>

                <form action="process_profile.php" method="POST">
                    
                    <!-- Name Fields -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="firstName" class="form-label fw-bold small">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="firstName" name="first_name" 
                                   value="<?php echo htmlspecialchars($profile['first_name']); ?>" 
                                   <?php echo !$edit_mode ? 'readonly' : 'required'; ?>>
                        </div>
                        <div class="col-md-4">
                            <label for="middleName" class="form-label fw-bold small">Middle Name</label>
                            <input type="text" class="form-control" id="middleName" name="middle_name" 
                                   value="<?php echo htmlspecialchars($profile['middle_name']); ?>" 
                                   <?php echo !$edit_mode ? 'readonly' : ''; ?>>
                        </div>
                        <div class="col-md-4">
                            <label for="lastName" class="form-label fw-bold small">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="lastName" name="last_name" 
                                   value="<?php echo htmlspecialchars($profile['last_name']); ?>" 
                                   <?php echo !$edit_mode ? 'readonly' : 'required'; ?>>
                        </div>
                    </div>

                    <!-- Birthdate and Sex -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="birthdate" class="form-label fw-bold small">Birthdate <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="birthdate" name="birthdate" 
                                   value="<?php echo htmlspecialchars($profile['birthdate']); ?>" 
                                   <?php echo !$edit_mode ? 'readonly' : 'required'; ?>>
                        </div>
                        <div class="col-md-6">
                            <label for="sex" class="form-label fw-bold small">Sex <span class="text-danger">*</span></label>
                            <select class="form-select" id="sex" name="sex" 
                                    <?php echo !$edit_mode ? 'disabled' : 'required'; ?>>
                                <option value="Male" <?php echo $profile['sex'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo $profile['sex'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>
                    </div>

                    <!-- College and Department -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="college" class="form-label fw-bold small">College</label>
                            <input type="text" class="form-control" id="college" name="college" 
                                   value="<?php echo htmlspecialchars($profile['college']); ?>" 
                                   <?php echo !$edit_mode ? 'readonly' : ''; ?>>
                        </div>
                        <div class="col-md-6">
                            <label for="department" class="form-label fw-bold small">Department</label>
                            <input type="text" class="form-control" id="department" name="department" 
                                   value="<?php echo htmlspecialchars($profile['department']); ?>" 
                                   <?php echo !$edit_mode ? 'readonly' : ''; ?>>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="mb-4">
                        <label for="email" class="form-label fw-bold small">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($profile['email']); ?>" readonly>
                    </div>

                    <!-- Buttons -->
                    <div class="d-grid">
                        <?php if ($edit_mode): ?>
                            <button type="submit" class="btn btn-login btn-lg fw-bold mb-2">Update my Profile</button>
                            <a href="profile.php" class="btn btn-outline-secondary btn-lg">Cancel</a>
                        <?php else: ?>
                            <a href="profile.php?edit=true" class="btn btn-login btn-lg fw-bold">Edit my profile</a>
                        <?php endif; ?>
                    </div>


                    <div class="mt-4 p-3 bg-light rounded">
                        <small class="text-muted">
                            <strong class="text-orange">Note:</strong> To request changes, please wait for the approval of the Department Head.
                        </small>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>