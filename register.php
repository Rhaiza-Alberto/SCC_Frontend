<?php
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if ($_SESSION['role'] == 'faculty')
        header('Location: faculty/faculty_dashboard.php');
    elseif ($_SESSION['role'] == 'admin')
        header('Location: admin/admin_dashboard.php');
    elseif ($_SESSION['role'] == 'dept_head')
        header('Location: dept_head/dept_dashboard.php');
    exit();
}

$errors = [];
$form_data = [
    'firstName' => '',
    'middleName' => '',
    'lastName' => '',
    'birthdate' => '',
    'sex' => 'male',
    'email' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data['firstName'] = trim($_POST['firstName'] ?? '');
    $form_data['middleName'] = trim($_POST['middleName'] ?? '');
    $form_data['lastName'] = trim($_POST['lastName'] ?? '');
    $form_data['birthdate'] = $_POST['birthdate'] ?? '';
    $form_data['sex'] = $_POST['sex'] ?? 'male';
    $form_data['email'] = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // Validation
    if (empty($form_data['firstName'])) $errors['firstName'] = 'First name is required.';
    if (empty($form_data['lastName'])) $errors['lastName'] = 'Last name is required.';
    if (empty($form_data['birthdate'])) {
        $errors['birthdate'] = 'Birthdate is required.';
    } elseif (strtotime($form_data['birthdate']) > time()) {
        $errors['birthdate'] = 'Birthdate cannot be in the future.';
    }
    
    if (empty($form_data['email'])) {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } elseif (!preg_match("/@gmail\.com$/i", $form_data['email'])) {
        $errors['email'] = 'Only @gmail.com addresses are accepted.';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters.';
    }

    if (empty($confirmPassword)) {
        $errors['confirmPassword'] = 'Please confirm your password.';
    } elseif ($password !== $confirmPassword) {
        $errors['confirmPassword'] = 'Passwords do not match.';
    }

    // Database check if no validation errors
    if (empty($errors)) {
        try {
            $conn = get_db();
            $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$form_data['email']]);
            if ($check->fetch()) {
                $errors['email'] = 'This email is already registered.';
            } else {
                // Proceed with registration
                $roleStmt = $conn->prepare("SELECT id FROM roles WHERE role_name = 'faculty'");
                $roleStmt->execute();
                $role = $roleStmt->fetch(PDO::FETCH_ASSOC);

                if (!$role) {
                    $_SESSION['register_error'] = 'System error: role not found. Please contact admin.';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $sex_normalized = ucfirst(strtolower($form_data['sex']));
                    $verification_token = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

                    $stmt = $conn->prepare("
                        INSERT INTO users
                            (first_name, middle_name, last_name, birthdate, sex,
                             email, password, role_id, college_id,
                             created_at, is_deleted, is_approved, reset_requested,
                             verification_token, email_verified)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 0, 0, 0, ?, 0)
                    ");
                    $stmt->execute([
                        $form_data['firstName'],
                        !empty($form_data['middleName']) ? $form_data['middleName'] : null,
                        $form_data['lastName'],
                        $form_data['birthdate'],
                        $sex_normalized,
                        $form_data['email'],
                        $hashed_password,
                        $role['id'],
                        1, // College of Computing Studies ID
                        $verification_token
                    ]);

                    // Send Verification Email
                    $verify_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/verify.php";
                    $subject = "Your Verification Code - SCC Syllabus Portal";
                    $body = "
                        <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 30px; border: 1px solid #f0f0f0; border-radius: 12px; background: #ffffff;'>
                            <div style='text-align: center; margin-bottom: 20px;'>
                                <h1 style='color: #ff8800; margin: 0; font-size: 24px;'>SCC Syllabus Portal</h1>
                            </div>
                            <h2 style='color: #333; margin-top: 0;'>Verify Your Identity</h2>
                            <p style='color: #555; line-height: 1.6;'>Hello {$form_data['firstName']},</p>
                            <p style='color: #555; line-height: 1.6;'>Thank you for registering. Please use the following 6-digit code to verify your email address and complete your application:</p>
                            
                            <div style='text-align: center; margin: 30px 0; padding: 20px; background: #fff8f0; border-radius: 8px; border: 2px dashed #ff8800;'>
                                <span style='font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #ff8800;'>{$verification_token}</span>
                            </div>
                            
                            <p style='color: #555; line-height: 1.6;'>Enter this code on the verification page. If you have already closed the page, you can access it here:</p>
                            <div style='text-align: center; margin: 25px 0;'>
                                <a href='{$verify_link}' style='background-color: #ff8800; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>Verify My Account</a>
                            </div>
                            
                            <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                            <p style='font-size: 0.8rem; color: #999;'>After verification, your account will be sent to the Dean's Office for final approval. You will receive another notification once approved.</p>
                        </div>
                    ";
                    send_system_email($form_data['email'], $subject, $body);

                    // Notify the Dean
                    $dean = get_dean();
                    if ($dean) {
                        notify_user(
                            $dean['id'],
                            "New faculty registration request from {$form_data['firstName']} {$form_data['lastName']}. (Pending Email Verification)",
                            null
                        );
                    }

                    header('Location: verify_user.php?email=' . urlencode($form_data['email']) . '&sent=true');
                    exit();
                }
            }
        } catch (PDOException $e) {
            error_log("Registration Error: " . $e->getMessage());
            $_SESSION['register_error'] = 'A database error occurred. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — SCC Syllabus Portal</title>
    <meta name="description" content="Create your account for the SCC-CCS Syllabus Management Portal">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/design-system.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <div class="auth-wrapper">
        <!-- Left: Form -->
        <div class="auth-form-side">
            <div class="auth-form-container animate-in" style="max-width:520px">
                <a href="login.php" class="d-inline-flex align-items-center text-decoration-none mb-4"
                    style="color:var(--text-secondary);font-size:0.85rem">
                    <i class="bi bi-arrow-left me-2"></i> Back to Login
                </a> 
                
                <?php if (isset($_GET['success']) && $_GET['success'] === 'true'): ?>
                    <!-- Success State -->
                    <div class="text-center py-5 animate-in">
                        <div class="mb-4 d-inline-block">
                            <div class="success-ring">
                                <i class="bi bi-envelope-check" style="font-size:3rem;color:var(--success)"></i>
                            </div>
                        </div>
                        <h2 class="fw-bold mb-2" style="color:var(--text)">Verify Your <span
                                class="text-success">Email</span></h2>
                        <p class="subtitle px-4">We've sent a verification link to<br><strong><?= htmlspecialchars($_GET['email'] ?? 'your email') ?></strong>.</p>

                        <div class="modern-info-card my-5 text-start">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="info-icon-sm bg-success-light text-success"><i class="bi bi-clock-history"></i>
                                </div>
                                <h6 class="fw-bold mb-0" style="font-size:0.9rem">Next Steps</h6>
                            </div>
                            <ol class="mb-0 text-muted small" style="line-height:1.8; padding-left: 1.2rem;">
                                <li>Click the link in your email to verify your address.</li>
                                <li>The Dean's Office will then review your application (24-48h).</li>
                                <li>You'll receive a final approval email once reviewed.</li>
                            </ol>
                        </div>

                        <div class="mb-4">
                            <p class="small text-muted mb-2">Didn't receive the email?</p>
                            <a href="resend_verification.php?email=<?= urlencode($_GET['email'] ?? '') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-4">Resend Verification Link</a>
                        </div>

                        <a href="login.php" class="btn btn-primary-scc w-100 py-3 fw-bold shadow-sm"
                            style="border-radius:var(--radius-md)">Return to Login</a>
                    </div>

                    <style>
                        .success-ring {
                            width: 100px;
                            height: 100px;
                            border-radius: 50%;
                            background: var(--success-light);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            position: relative;
                        }

                        .success-ring::after {
                            content: '';
                            position: absolute;
                            width: 120px;
                            height: 120px;
                            border: 2px dashed var(--success);
                            border-radius: 50%;
                            opacity: 0.3;
                            animation: spin 10s linear infinite;
                        }

                        .modern-info-card {
                            background: var(--bg-secondary);
                            border: 1px solid var(--border);
                            border-radius: var(--radius-lg);
                            padding: 1.5rem;
                            position: relative;
                            overflow: hidden;
                        }

                        .modern-info-card::before {
                            content: '';
                            position: absolute;
                            top: 0;
                            left: 0;
                            width: 4px;
                            height: 100%;
                            background: var(--success);
                        }

                        .info-icon-sm {
                            width: 32px;
                            height: 32px;
                            border-radius: 8px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        }

                        @keyframes spin {
                            from {
                                transform: rotate(0deg);
                            }

                            to {
                                transform: rotate(360deg);
                            }
                        }
                    </style>
                <?php else: ?>
                    <div class="mb-1"><img src="css/logo.png" alt="SCC Logo"
                            style="width:52px;height:52px;border-radius:12px;box-shadow: 0 4px 12px rgba(0,0,0,0.05);padding:2px;background:white">
                    </div>
                    <h1 class="fw-bold mt-3 mb-1" style="font-size:1.5rem; letter-spacing:-0.5px">Join the <span
                            class="text-orange">SCC Community</span></h1>
                    <p class="subtitle mb-4 small">Create your institutional account to start managing syllabi.</p>

                    <?php if (isset($_SESSION['register_error'])): ?>
                        <div class="alert alert-danger border-0 shadow-sm rounded-3 py-2 mb-3 d-flex align-items-center"
                            role="alert" style="font-size:0.8rem; background: rgba(239, 68, 68, 0.08); color: #b91c1c;">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div><?= htmlspecialchars($_SESSION['register_error']) ?></div>
                        </div>
                        <?php unset($_SESSION['register_error']); ?>
                    <?php endif; ?>

                    <form method="POST" action="register.php" class="registration-form" novalidate>
                        <div class="form-section-label mb-3">Personal Information</div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-5">
                                <label class="custom-label">First Name</label>
                                <input type="text" name="firstName" class="modern-input <?= isset($errors['firstName']) ? 'is-invalid' : '' ?>" 
                                       placeholder="First name" value="<?= htmlspecialchars($form_data['firstName']) ?>">
                                <?php if (isset($errors['firstName'])): ?>
                                    <div class="error-msg"><?= $errors['firstName'] ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-2">
                                <label class="custom-label">M.I.</label>
                                <input type="text" name="middleName" class="modern-input" placeholder="—" 
                                       value="<?= htmlspecialchars($form_data['middleName']) ?>">
                            </div>
                            <div class="col-md-5">
                                <label class="custom-label">Last Name</label>
                                <input type="text" name="lastName" class="modern-input <?= isset($errors['lastName']) ? 'is-invalid' : '' ?>" 
                                       placeholder="Last name" value="<?= htmlspecialchars($form_data['lastName']) ?>">
                                <?php if (isset($errors['lastName'])): ?>
                                    <div class="error-msg"><?= $errors['lastName'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="custom-label">Birthdate</label>
                                <input type="date" name="birthdate" class="modern-input <?= isset($errors['birthdate']) ? 'is-invalid' : '' ?>" 
                                       value="<?= htmlspecialchars($form_data['birthdate']) ?>" max="<?= date('Y-m-d') ?>">
                                <?php if (isset($errors['birthdate'])): ?>
                                    <div class="error-msg"><?= $errors['birthdate'] ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="custom-label">Sex</label>
                                <div class="d-flex gap-2">
                                    <input type="radio" class="btn-check" name="sex" id="sexMale" value="male" 
                                           <?= $form_data['sex'] === 'male' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-secondary w-100 py-1 border-0"
                                        style="background:var(--bg-secondary); font-size:0.75rem; border-radius:8px"
                                        for="sexMale">Male</label>

                                    <input type="radio" class="btn-check" name="sex" id="sexFemale" value="female"
                                           <?= $form_data['sex'] === 'female' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-secondary w-100 py-1 border-0"
                                        style="background:var(--bg-secondary); font-size:0.75rem; border-radius:8px"
                                        for="sexFemale">Female</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-section-label mb-3">Account Credentials</div>
                        <div class="mb-3">
                            <label class="custom-label">Email Address (@gmail.com)</label>
                            <div class="input-group-modern">
                                <i class="bi bi-envelope"></i>
                                <input type="email" name="email" class="<?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                       placeholder="name@gmail.com" value="<?= htmlspecialchars($form_data['email']) ?>">
                            </div>
                            <?php if (isset($errors['email'])): ?>
                                <div class="error-msg"><?= $errors['email'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="row g-2 mb-4">
                            <div class="col-md-6">
                                <label class="custom-label">Password</label>
                                <div class="input-group-modern">
                                    <i class="bi bi-shield-lock"></i>
                                    <input type="password" name="password" class="<?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                                           placeholder="••••••••">
                                </div>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="error-msg"><?= $errors['password'] ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="custom-label">Confirm</label>
                                <div class="input-group-modern">
                                    <i class="bi bi-check-circle"></i>
                                    <input type="password" name="confirmPassword" class="<?= isset($errors['confirmPassword']) ? 'is-invalid' : '' ?>" 
                                           placeholder="••••••••">
                                </div>
                                <?php if (isset($errors['confirmPassword'])): ?>
                                    <div class="error-msg"><?= $errors['confirmPassword'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="custom-label">Affiliated College</label>
                            <div class="modern-input bg-light d-flex align-items-center gap-2 py-2"
                                 style="cursor:not-allowed; opacity:0.8">
                                <i class="bi bi-building text-muted small"></i>
                                <span class="small fw-bold" style="font-size:0.75rem">College of Computing Studies</span>
                            </div>
                            <input type="hidden" name="college" value="College of Computing Studies">
                        </div>

                        <button type="submit" class="btn btn-primary-scc w-100 py-2 fw-bold shadow-sm"
                            style="border-radius:var(--radius-md); font-size:0.9rem;">Create Account</button>
                    </form>

                    <div class="text-center mt-4">
                        <p style="font-size:0.8rem; color:var(--text-secondary)">
                            Already part of SCC? <a href="login.php"
                                class="fw-bold text-orange text-decoration-none ms-1">Sign In here</a>
                        </p>
                    </div>

                    <style>
                        .form-section-label {
                            font-size: 0.6rem;
                            text-transform: uppercase;
                            letter-spacing: 1px;
                            font-weight: 800;
                            color: var(--primary);
                            display: flex;
                            align-items: center;
                            gap: 8px;
                        }

                        .form-section-label::after {
                            content: '';
                            flex-grow: 1;
                            height: 1px;
                            background: var(--border);
                            opacity: 0.5;
                        }

                        .custom-label {
                            font-size: 0.7rem;
                            font-weight: 700;
                            color: var(--text-dark);
                            margin-bottom: 0.35rem;
                            display: block;
                        }

                        .modern-input {
                            background: var(--bg-secondary);
                            border: 1px solid var(--border);
                            border-radius: 8px;
                            padding: 0.5rem 0.75rem;
                            font-size: 0.8rem;
                            width: 100%;
                            transition: all 0.2s ease;
                            color: var(--text);
                        }

                        .modern-input:focus, .input-group-modern input:focus {
                            border-color: var(--primary);
                            box-shadow: 0 0 0 3px var(--primary-light);
                            outline: none;
                        }
                        
                        .modern-input.is-invalid, .input-group-modern input.is-invalid {
                            border-color: #ef4444;
                            background-color: rgba(239, 68, 68, 0.02);
                        }

                        .error-msg {
                            color: #ef4444;
                            font-size: 0.7rem;
                            margin-top: 4px;
                            font-weight: 500;
                        }

                        .input-group-modern {
                            position: relative;
                        }

                        .input-group-modern i {
                            position: absolute;
                            left: 10px;
                            top: 50%;
                            transform: translateY(-50%);
                            color: var(--text-muted);
                            font-size: 0.9rem;
                        }

                        .input-group-modern input {
                            background: var(--bg-secondary);
                            border: 1px solid var(--border);
                            border-radius: 8px;
                            padding: 0.5rem 0.75rem 0.5rem 2.25rem;
                            font-size: 0.8rem;
                            width: 100%;
                            transition: all 0.2s ease;
                        }

                        .btn-check:checked+label {
                            background: var(--primary) !important;
                            color: white !important;
                            font-weight: 700;
                        }
                    </style>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Illustration -->
        <div class="auth-illustration-side">
            <div class="text-center text-white px-5" style="position:relative;z-index:2;max-width:500px">
                <svg width="260" height="200" viewBox="0 0 260 200" fill="none" xmlns="http://www.w3.org/2000/svg"
                    class="mb-4 animate-in">
                    <circle cx="130" cy="70" r="35" fill="rgba(255,136,0,0.15)" stroke="rgba(255,136,0,0.4)"
                        stroke-width="1.5" />
                    <path d="M118 70L126 78L142 62" stroke="rgba(255,136,0,0.7)" stroke-width="3" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <rect x="50" y="120" width="160" height="55" rx="10" fill="rgba(255,255,255,0.05)"
                        stroke="rgba(255,255,255,0.1)" stroke-width="1" />
                    <rect x="70" y="135" width="80" height="6" rx="3" fill="rgba(255,136,0,0.3)" />
                    <rect x="70" y="150" width="60" height="5" rx="2.5" fill="rgba(255,255,255,0.1)" />
                    <rect x="170" y="138" width="25" height="14" rx="4" fill="rgba(34,197,94,0.4)" />
                    <circle cx="50" cy="50" r="8" fill="rgba(59,130,246,0.15)" />
                    <circle cx="220" cy="30" r="10" fill="rgba(255,136,0,0.1)" />
                    <circle cx="40" cy="160" r="6" fill="rgba(34,197,94,0.15)" />
                </svg>

                <h2 class="fw-bold mb-3" style="font-family:var(--font-serif);font-size:1.6rem">
                    Join the<br><span style="color:var(--primary)">Academic Community</span>
                </h2>
                <p style="color:rgba(255,255,255,0.6);font-size:0.9rem;line-height:1.7">
                    Register to start submitting and managing your course syllabi with our streamlined approval
                    workflow.
                </p>
            </div>
            <div
                style="position:absolute;top:-100px;right:-100px;width:300px;height:300px;border-radius:50%;background:rgba(255,136,0,0.05)">
            </div>
            <div
                style="position:absolute;bottom:-80px;left:-80px;width:250px;height:250px;border-radius:50%;background:rgba(59,130,246,0.04)">
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Clear errors on input
        document.querySelectorAll('.modern-input, .input-group-modern input').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
                const parent = this.closest('.col-md-5, .col-md-6, .mb-3');
                if (parent) {
                    const error = parent.querySelector('.error-msg');
                    if (error) error.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>