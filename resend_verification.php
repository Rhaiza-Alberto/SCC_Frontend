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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/design-system.css">
    <style>
        body { background: var(--bg-secondary); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Inter', sans-serif; }
        .resend-card { background: white; border-radius: var(--radius-lg); padding: 3rem; max-width: 450px; width: 90%; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="resend-card animate-in">
        <div class="text-center mb-4">
            <div class="d-inline-block bg-primary-light text-primary rounded-circle p-3 mb-3">
                <i class="bi bi-envelope-arrow-up fs-2"></i>
            </div>
            <h2 class="fw-bold mb-1" style="color:var(--text)">Resend Link</h2>
            <p class="text-secondary small">Enter your email address to receive a new verification link.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert <?= $error ? 'alert-danger' : 'alert-success' ?> border-0 small py-2 mb-4">
                <i class="bi <?= $error ? 'bi-exclamation-triangle' : 'bi-check-circle' ?> me-2"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="form-label small fw-bold">Email Address</label>
                <input type="email" name="email" class="form-control py-2" placeholder="name@gmail.com" value="<?= htmlspecialchars($email) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary-scc w-100 py-2 fw-bold rounded-pill mb-3 shadow-sm">Send New Link</button>
            <div class="text-center">
                <a href="login.php" class="text-decoration-none text-muted small"><i class="bi bi-arrow-left me-1"></i> Back to Login</a>
            </div>
        </form>
    </div>
</body>
</html>
