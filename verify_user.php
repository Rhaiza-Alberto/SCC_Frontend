<?php
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

$email = $_REQUEST['email'] ?? '';
$error = '';
$success = false;
$message = '';

// Handle OTP Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    $otp = trim($_POST['otp']);
    $user_email = trim($_POST['email'] ?? '');

    if (strlen($otp) !== 6) {
        $error = "Please enter the 6-digit code sent to your email.";
    } elseif (empty($user_email)) {
        $error = "Email address is missing. Please enter your email.";
    } else {
        try {
            $conn = get_db();
            // Check OTP
            $stmt = $conn->prepare("SELECT id, first_name FROM users WHERE email = ? AND verification_token = ? AND is_deleted = 0");
            $stmt->execute([$user_email, $otp]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Mark as verified
                $update = $conn->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?");
                $update->execute([$user['id']]);
                
                $success = true;
                $message = "Verification Successful! Your account is now active and verified.";
                
                // Notify Dean if it's a new faculty
                $dean = get_dean();
                if ($dean) {
                    notify_user($dean['id'], "User " . htmlspecialchars($user['first_name']) . " has verified their email.", null);
                }
            } else {
                $error = "The 6-digit code you entered is incorrect. Please check your email.";
            }
        } catch (PDOException $e) {
            $error = "System error. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account — SCC Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/design-system.css">
    <style>
        body { 
            background: #f1f5f9; 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-family: 'Inter', sans-serif;
            padding: 20px;
        }
        .verify-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 440px;
            width: 100%;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .otp-field {
            width: 100%;
            height: 60px;
            font-size: 32px;
            font-weight: 800;
            text-align: center;
            letter-spacing: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            margin: 20px 0;
            color: #ff8800;
            transition: all 0.2s;
        }
        .otp-field:focus {
            outline: none;
            border-color: #ff8800;
            box-shadow: 0 0 0 4px rgba(255, 136, 0, 0.1);
        }
        .btn-submit {
            background: #ff8800;
            color: white;
            border: none;
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            margin-top: 10px;
            transition: all 0.2s;
        }
        .btn-submit:hover {
            background: #e67a00;
            transform: translateY(-1px);
        }
        .icon-circle {
            width: 64px;
            height: 64px;
            background: #fff7ed;
            color: #ff8800;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 30px;
        }
    </style>
</head>
<body>
    <div class="verify-card animate-in">
        <?php if ($success): ?>
            <div class="icon-circle" style="background:#f0fdf4; color:#16a34a">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <h3 class="fw-bold mb-2">Verified!</h3>
            <p class="text-muted mb-4"><?= $message ?></p>
            <a href="login.php" class="btn btn-primary w-100 py-3 fw-bold" style="background:#ff8800; border:none; border-radius:12px">Login to Portal</a>
        <?php else: ?>
            <div class="icon-circle">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h3 class="fw-bold mb-2">Verification Required</h3>
            <p class="text-muted small mb-4">Please enter the 6-digit code sent to<br><strong><?= htmlspecialchars($email) ?></strong></p>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small border-0 mb-4" style="background:#fef2f2; color:#b91c1c; border-radius:8px">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                
                <div class="text-start">
                    <label class="small fw-bold text-muted ms-1">6-Digit OTP Code</label>
                    <input type="text" name="otp" maxlength="6" class="otp-field" placeholder="000000" pattern="\d*" inputmode="numeric" required autofocus autocomplete="one-time-code">
                </div>

                <button type="submit" class="btn-submit">Verify and Activate</button>
            </form>

            <div class="mt-4">
                <p class="small text-muted mb-1">Didn't receive the code?</p>
                <a href="resend_verification.php?email=<?= urlencode($email) ?>" class="text-decoration-none fw-bold" style="color:#ff8800">Resend Code</a>
            </div>

            <div class="mt-4 pt-3 border-top">
                <a href="login.php" class="text-decoration-none text-muted small"><i class="bi bi-arrow-left me-1"></i> Back to Login</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.querySelector('.otp-field').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>
