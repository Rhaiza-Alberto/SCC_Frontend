<?php
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

$email = $_GET['email'] ?? '';
$error = '';
$success = false;
$message = '';

// Handle Token-based verification (legacy/link click)
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    try {
        $conn = get_db();
        $stmt = $conn->prepare("SELECT id, first_name, email_verified FROM users WHERE verification_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            $update = $conn->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?");
            $update->execute([$user['id']]);
            $success = true;
            $message = "Your email has been verified and your account has been made! Please log in to continue.";
        } else {
            $error = "Invalid or expired verification link.";
        }
    } catch (PDOException $e) {
        $error = "A system error occurred.";
    }
}

// Handle Code-based verification (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
    $input_code = trim($_POST['verify_code']);
    $user_email = $_POST['email'] ?? '';

    if (strlen($input_code) !== 6) {
        $error = "Please enter a valid 6-digit code.";
    } else {
        try {
            $conn = get_db();
            $stmt = $conn->prepare("SELECT id, first_name, email_verified FROM users WHERE email = ? AND verification_token = ?");
            $stmt->execute([$user_email, $input_code]);
            $user = $stmt->fetch();

            if ($user) {
                $update = $conn->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?");
                $update->execute([$user['id']]);
                $success = true;
                $message = "Thank you, " . htmlspecialchars($user['first_name']) . "! Your account has been made. Your email is now verified. Please log in to proceed.";
                
                // Notify Dean
                $dean = get_dean();
                if ($dean) {
                    notify_user($dean['id'], "New faculty member (" . htmlspecialchars($user['first_name']) . ") verified their email. Ready for approval.", null);
                }
            } else {
                $error = "Incorrect verification code. Please check your email.";
            }
        } catch (PDOException $e) {
            $error = "A system error occurred. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Account — SCC Syllabus Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/design-system.css">
    <style>
        :root {
            --primary: #ff8800;
            --primary-dark: #e67a00;
            --bg-body: #f8fafc;
        }
        body { 
            background: var(--bg-body); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-family: 'Inter', sans-serif;
            color: #1e293b;
        }
        .verify-card { 
            background: white; 
            border-radius: 24px; 
            padding: 3rem 2.5rem; 
            text-align: center; 
            max-width: 480px; 
            width: 90%; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.06); 
            border: 1px solid rgba(0,0,0,0.05);
        }
        .code-input {
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: 0.8rem;
            text-align: center;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 1rem;
            width: 100%;
            margin: 2rem 0;
            color: var(--primary);
            transition: all 0.2s ease;
            background: #fdfdfd;
        }
        .code-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 136, 0, 0.1);
            background: #fff;
        }
        .btn-verify {
            background: var(--primary);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 700;
            width: 100%;
            font-size: 1.1rem;
            box-shadow: 0 4px 12px rgba(255, 136, 0, 0.2);
            transition: all 0.2s ease;
        }
        .btn-verify:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(255, 136, 0, 0.3);
        }
        .resend-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .resend-link:hover {
            text-decoration: underline;
        }
        .icon-box {
            width: 72px;
            height: 72px;
            background: #fff8f0;
            color: var(--primary);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
        }
        .success-box { background: #f0fdf4; color: #16a34a; }
    </style>
</head>
<body>
    <div class="verify-card animate-in">
        <?php if ($success): ?>
            <div class="icon-box success-box">
                <i class="bi bi-patch-check-fill"></i>
            </div>
            <h2 class="fw-bold mb-3">Verification Success</h2>
            <p class="text-secondary mb-5"><?= $message ?></p>
            <a href="login.php" class="btn-verify d-block text-decoration-none">Back to Login</a>
        <?php else: ?>
            <div class="icon-box">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h2 class="fw-bold mb-1">Enter Security Code</h2>
            <p class="text-secondary small mb-4">We've sent a 6-digit code to <br><strong class="text-dark"><?= htmlspecialchars($email) ?></strong></p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger border-0 small py-2" style="border-radius: 10px;">
                    <i class="bi bi-exclamation-circle me-1"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                <input type="text" name="verify_code" maxlength="6" class="code-input" placeholder="000000" pattern="\d*" inputmode="numeric" required autofocus>
                
                <button type="submit" class="btn-verify mb-4">Verify Account</button>
            </form>
            
            <div class="mt-2">
                <p class="small text-muted">Didn't receive the code? <br>
                    <a href="resend_verification.php?email=<?= urlencode($email) ?>" class="resend-link">Resend Code</a>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-focus and formatting for the code input
        const input = document.querySelector('.code-input');
        input.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>
