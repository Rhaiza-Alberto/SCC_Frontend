<?php
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

$email = $_GET['email'] ?? '';
$message = '';
$error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $message = "Please enter your email address.";
        $error = true;
    } else {
        try {
            $conn = get_db();
            $stmt = $conn->prepare("SELECT id, first_name, email_verified, is_deleted FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || $user['is_deleted']) {
                $message = "This email address is not registered in our system.";
                $error = true;
            } elseif ($user['email_verified'] == 1) {
                $message = "Your email is already verified. Please try logging in.";
                $error = false;
            } else {
                // Generate new 6-digit code
                $new_code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
                $conn->prepare("UPDATE users SET verification_token = ? WHERE id = ?")
                    ->execute([$new_code, $user['id']]);

                // Send Email
                $verify_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/verify.php";
                $subject = "Your New Verification Code - SCC Syllabus Portal";
                $body = "
                    <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 30px; border: 1px solid #f0f0f0; border-radius: 12px; background: #ffffff;'>
                        <div style='text-align: center; margin-bottom: 20px;'>
                            <h1 style='color: #ff8800; margin: 0; font-size: 24px;'>SCC Syllabus Portal</h1>
                        </div>
                        <h2 style='color: #333; margin-top: 0;'>New Verification Code</h2>
                        <p style='color: #555; line-height: 1.6;'>Hello {$user['first_name']},</p>
                        <p style='color: #555; line-height: 1.6;'>You requested a new verification code. Please use the following 6-digit code to complete your registration:</p>
                        
                        <div style='text-align: center; margin: 30px 0; padding: 20px; background: #fff8f0; border-radius: 8px; border: 2px dashed #ff8800;'>
                            <span style='font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #ff8800;'>{$new_code}</span>
                        </div>
                        
                        <p style='color: #555; line-height: 1.6;'>Enter this code on the verification page. If you have already closed the page, you can access it here:</p>
                        <div style='text-align: center; margin: 25px 0;'>
                            <a href='{$verify_link}' style='background-color: #ff8800; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>Verify My Account</a>
                        </div>
                    </div>
                ";
                send_system_email($email, $subject, $body);

                header("Location: verify.php?email=" . urlencode($email) . "&sent=true");
                exit();
            }
        } catch (PDOException $e) {
            $message = "A system error occurred. Please try again later.";
            $error = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resend Verification — SCC Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/design-system.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .custom-label {
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--text);
            opacity: 0.85;
            margin-bottom: 0.35rem;
            display: block;
        }
    </style>
</head>

<body>
    <div class="auth-wrapper">
        <div class="auth-form-side">
            <div class="auth-form-container animate-in" style="max-width:420px">
                
                <a href="login.php" class="d-inline-flex align-items-center text-decoration-none mb-4" style="color:var(--text-secondary);font-size:0.85rem">
                    <i class="bi bi-arrow-left me-2"></i> Back to Login
                </a>

                <div class="mb-3">
                    <div style="width:56px;height:56px;border-radius:var(--radius-md);background:rgba(255,136,0,0.1);display:flex;align-items:center;justify-content:center">
                        <i class="bi bi-envelope-arrow-up" style="font-size:1.5rem;color:var(--primary)"></i>
                    </div>
                </div>

                <h1 style="font-size:1.5rem">Resend Link</h1>
                <p class="subtitle">Enter your email address to receive a new verification link.</p>

                <?php if ($message): ?>
                    <div class="alert <?= $error ? 'alert-danger' : 'alert-success' ?> alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" style="font-size:0.85rem">
                        <i class="bi <?= $error ? 'bi-exclamation-circle' : 'bi-check-circle' ?> me-2"></i>
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-4">
                        <label class="custom-label">Email Address</label>
                        <input type="email" name="email" class="auth-input" placeholder="name@gmail.com"
                               value="<?= htmlspecialchars($email) ?>" required autofocus>
                    </div>

                    <button type="submit" class="btn btn-primary-scc w-100 py-3 fw-bold mb-4 shadow-sm" style="font-size:1rem; border-radius: 12px;">
                        Send New Link <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </form>

                <div class="text-center pt-2">
                    <a href="login.php" class="text-decoration-none small fw-bold" style="color:var(--primary)">
                        Return to Login
                    </a>
                </div>
            </div>
        </div>

        <div class="auth-illustration-side" style="background: linear-gradient(135deg, #1a1a1a 0%, #333 100%);">
            <div class="text-center text-white px-5" style="position:relative;z-index:2;max-width:400px">
                <div class="mb-4 d-inline-block p-4 rounded-circle" style="background: rgba(255,136,0,0.1); border: 1px solid rgba(255,136,0,0.2);">
                    <i class="bi bi-shield-lock-fill" style="font-size:3.5rem; color:var(--primary)"></i>
                </div>
                <h3 class="fw-bold" style="font-family:var(--font-serif)">Secure Request</h3>
                <p style="color:rgba(255,255,255,0.6); font-size:0.95rem; line-height:1.6">Verifying your professional email preserves academic workflow transparency and securely registers your syllabus submission configurations.</p>
            </div>
            <div style="position:absolute; bottom:-50px; left:-50px; width:200px; height:200px; border-radius:50%; background:radial-gradient(circle, rgba(255,136,0,0.1) 0%, transparent 70%);"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/theme.js"></script>
</body>

</html>