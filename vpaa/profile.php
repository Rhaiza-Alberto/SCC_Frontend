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

// TODO: In a real application, fetch user profile data from database
// For now, using demo data
$profile = [
    'first_name' => 'John',
    'middle_name' => '',
    'last_name' => 'Doe',
    'birthdate' => '1990-12-1',
    'sex' => 'Male',
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
    <style>
        .btn-orange {
            background-color: #ff8800;
            color: white;
        }
        .btn-orange:hover {
            background-color: #e67e00;
            color: white;
        }
    </style>
</head>

<body class="bg-light">

    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar bg-black text-white p-3 min-vh-100 d-flex flex-column"
            style="width: 280px; position: fixed;">
            <div class="text-center mb-4 mt-3">
                <img src="../css/logo.png" alt="SCC Logo" class="rounded-circle mb-2" style="width: 80px; height: 80px;">
                <h4 class="font-serif fw-bold">VPAA Panel</h4>
            </div>

            <nav class="nav flex-column gap-2 mb-auto">
                <a href="vpaa_dashboard.php" class="nav-link text-white p-3 rounded hover-effect">
                    Dashboard
                </a>
                <a href="profile.php" class="nav-link text-white active-nav-link p-3 rounded">
                    Profile
                </a>
                <a href="../logout.php" class="nav-link text-white p-3 rounded hover-effect mt-5">
                    Logout
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-grow-1 p-5" style="margin-left: 280px;">
            <div class="card border-0 shadow-sm p-5" style="max-width: 700px; margin: 0 auto;">
                <h3 class="text-orange font-serif fw-bold mb-2 text-center">
                    <?php echo $edit_mode ? 'Edit my Profile' : 'My Profile'; ?>
                </h3>
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

                    <!-- Office -->
                    <div class="mb-3">
                        <label for="office" class="form-label fw-bold small">Office</label>
                        <input type="text" class="form-control" id="office" name="office" 
                               value="<?php echo htmlspecialchars($profile['office']); ?>" 
                               <?php echo !$edit_mode ? 'readonly' : ''; ?>>
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
                            <button type="submit" class="btn btn-orange btn-lg fw-bold">Update my Profile</button>
                            <a href="profile.php" class="btn btn-outline-secondary btn-lg fw-bold mt-2">Cancel</a>
                        <?php else: ?>
                            <a href="profile.php?edit=true" class="btn btn-orange btn-lg fw-bold">Edit Profile</a>
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