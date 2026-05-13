<?php
// Start output buffering to prevent any accidental output from dependencies
ob_start();
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../functions.php';

// Handle AJAX/POST verification separately from the page display
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    // Clear any previous output (warnings, whitespace)
    ob_clean();
    header('Content-Type: application/json');

    $otp = trim($_POST['otp']);
    $target_email = trim($_POST['email'] ?? '');
    
    if (empty($otp) || empty($target_email)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing OTP or Email.']);
        exit();
    }

    try {
        $conn = get_db();
        // Check OTP against the verification_token column
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND verification_token = ? AND is_deleted = 0");
        $stmt->execute([$target_email, $otp]);
        $user = $stmt->fetch();

        if ($user) {
            // Update status
            $update = $conn->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?");
            $update->execute([$user['id']]);
            
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'The OTP code is incorrect or expired.']);
        }
    } catch (Exception $e) {
        error_log("Verification Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Database error occurred.']);
    }
    exit();
}

// Handle Resend OTP Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend'])) {
    ob_clean();
    header('Content-Type: application/json');
    $target_email = trim($_POST['email'] ?? '');

    if (empty($target_email)) {
        echo json_encode(['status' => 'error', 'message' => 'Email missing.']);
        exit();
    }

    try {
        $conn = get_db();
        $new_otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        
        $update = $conn->prepare("UPDATE users SET verification_token = ? WHERE email = ? AND is_deleted = 0");
        $update->execute([$new_otp, $target_email]);

        // Fetch user name for email
        $uStmt = $conn->prepare("SELECT first_name FROM users WHERE email = ?");
        $uStmt->execute([$target_email]);
        $uName = $uStmt->fetchColumn();

        // Send Email
        $subject = "Your New Verification Code - SCC Portal";
        $body = "Hello " . ($uName ?: 'User') . ", your new verification code is: {$new_otp}";
        send_system_email($target_email, $subject, $body);

        echo json_encode(['status' => 'success', 'message' => 'A new code has been sent to ' . $target_email]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to resend code.']);
    }
    exit();
}

// If not a POST request, proceed with page rendering
restrict_to_role('dean');

$email = $_GET['email'] ?? '';
$user_id_session = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Dean';

$unread_count = count_unread_notifications($user_id_session);
$notifications = get_notifications($user_id_session, 5);

// Ensure no output from includes leaked before the HTML
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Onboarding — Dean's Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php $active_page = 'add_user'; include '_sidebar.php'; ?>

    <main class="scc-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1" style="color:var(--text)">Final <span style="color:var(--primary)">Step</span></h4>
                <p style="font-size:0.85rem;color:var(--text-secondary);margin:0">Complete user onboarding by verifying their identity</p>
            </div>
        </div>

        <div class="scc-card animate-in" style="max-width: 500px; margin: 40px auto;">
            <div class="card-body p-4 p-md-5 text-center">
                <div class="mb-4">
                    <div class="bg-primary-light text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width:64px;height:64px">
                        <i class="bi bi-shield-check fs-2"></i>
                    </div>
                </div>
                <h5 class="fw-bold mb-2">Verification Required</h5>
                <p class="text-muted small mb-4">A 6-digit code has been sent to:<br><strong class="text-dark"><?= htmlspecialchars($email) ?></strong></p>

                <form id="onboardingForm">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                    <div class="text-start mb-3">
                        <label class="small fw-bold text-secondary ms-1">6-Digit Code</label>
                        <input type="text" name="otp" id="otpInput" maxlength="6" class="form-control text-center fw-bold fs-3" placeholder="000000" style="letter-spacing:8px; height:60px; border-radius:12px" required autofocus>
                    </div>
                    
                    <button type="submit" class="btn btn-primary-scc w-100 py-3 fw-bold rounded-pill shadow-sm">
                        Verify and Complete Registration
                    </button>
                </form>

                <div class="mt-4">
                    <button type="button" id="resendBtn" class="btn btn-link text-decoration-none small fw-bold text-orange">
                        <i class="bi bi-arrow-clockwise me-1"></i> Resend Code
                    </button>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <a href="manage_user.php" class="text-muted small text-decoration-none">Skip for now and return to list</a>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('onboardingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const otpInput = document.getElementById('otpInput');
            const otp = otpInput.value;
            const email = "<?= htmlspecialchars($email) ?>";

            if (otp.length !== 6) {
                Swal.fire('Error', 'Please enter a 6-digit code.', 'error');
                return;
            }

            // Step 2: Show loading popup
            Swal.fire({
                title: 'Verifying code...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // AJAX call to verify
            const formData = new FormData();
            formData.append('otp', otp);
            formData.append('email', email);

            fetch('verify_onboarding.php', {
                method: 'POST',
                body: formData
            })
            .then(async response => {
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Server response was not JSON:', text);
                    throw new Error('Invalid server response format.');
                }
            })
            .then(data => {
                if (data.status === 'success') {
                    // Step 2: Show success popup
                    Swal.fire({
                        icon: 'success',
                        title: 'Verification successful',
                        text: 'Registered Successfully',
                        showConfirmButton: false,
                        timer: 2000
                    }).then(() => {
                        // Step 3: Auto redirect
                        window.location.href = 'manage_user.php';
                    });
                } else {
                    Swal.fire('Failed', data.message || 'Incorrect OTP code.', 'error');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                Swal.fire('Error', error.message || 'An error occurred during verification.', 'error');
            });
        });

        document.getElementById('otpInput').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Handle Resend Button
        document.getElementById('resendBtn').addEventListener('click', function() {
            const email = "<?= htmlspecialchars($email) ?>";
            
            Swal.fire({
                title: 'Sending new code...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData();
            formData.append('resend', '1');
            formData.append('email', email);

            fetch('verify_onboarding.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Code Sent',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error', 'Failed to resend code.', 'error');
            });
        });
    </script>
</body>
</html>
