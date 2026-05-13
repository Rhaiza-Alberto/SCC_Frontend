<?php
require_once __DIR__ . '/database.php';

/* ============================
   DATABASE HELPER
============================ */



/* ============================
   NOTIFICATIONS
============================ */

function notify_user($user_id, $message, $syllabus_id = null)
{
    try {
        $conn = get_db();
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, syllabus_id, message, is_read, created_at)
            VALUES (?, ?, ?, 0, NOW())
        ");
        $stmt->execute([$user_id, $syllabus_id, $message]);

        // PHPMailer Integration
        // Get user email
        $userStmt = $conn->prepare("SELECT email, first_name, last_name FROM users WHERE id = ?");
        $userStmt->execute([$user_id]);
        $user = $userStmt->fetch();

        if ($user && !empty($user['email'])) {
            $to_name = trim($user['first_name'] . ' ' . $user['last_name']);
            $subject = "Syllabus Management System Notification";

            // Call the notification system email function if available
            // Assuming PHPMailer/notification_system.php is required somewhere or we can just include it
            $notif_sys_path = __DIR__ . '/PHPMailer/notification_system.php';
            if (file_exists($notif_sys_path)) {
                require_once $notif_sys_path;
                // Note: notify_user might be called multiple times, require_once is safe
                // We need to ensure send_notification_email is defined in that file
                if (function_exists('send_notification_email')) {
                    send_notification_email($user['email'], $to_name, $subject, $message);
                }
            }
        }

        return true;
    } catch (PDOException $e) {
        error_log("Notify Error: " . $e->getMessage());
        return false;
    }
}

function get_notifications($user_id, $limit = 10)
{
    $conn = get_db();
    $limit = (int) $limit;
    $stmt = $conn->prepare("
        SELECT n.*, s.file_path
        FROM notifications n
        LEFT JOIN syllabus s ON n.syllabus_id = s.id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT $limit
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function count_unread_notifications($user_id)
{
    $conn = get_db();
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0
    ");
    $stmt->execute([$user_id]);
    return (int) $stmt->fetchColumn();
}

function mark_notification_read($notification_id)
{
    $conn = get_db();
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->execute([$notification_id]);
}

function mark_single_notification_read($notification_id, $user_id)
{
    $conn = get_db();
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notification_id, $user_id]);
}

function mark_all_notifications_read($user_id)
{
    $conn = get_db();
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
}

/**
 * Returns Bootstrap color classes for a notification based on its message content.
 *
 * Usage in your notification template:
 *   $colors = get_notification_color($notification['message']);
 *   // $colors['bg']      — background class  e.g. 'bg-danger'
 *   // $colors['text']    — text class         e.g. 'text-danger'
 *   // $colors['border']  — border class       e.g. 'border-danger'
 *   // $colors['icon']    — a Unicode icon     e.g. '✕'
 */
function get_notification_color(string $message): array
{
    $msg = strtolower($message);

    // Fully approved by VPAA
    if (str_contains($msg, 'fully approved') || str_contains($msg, 'approved by vpaa')) {
        return [
            'bg' => 'bg-success',
            'text' => 'text-success',
            'border' => 'border-success',
            'icon' => '<i class="bi bi-check-circle-fill"></i>',
        ];
    }

    // Rejected
    if (str_contains($msg, 'rejected')) {
        return [
            'bg' => 'bg-danger',
            'text' => 'text-danger',
            'border' => 'border-danger',
            'icon' => '<i class="bi bi-x-circle-fill"></i>',
        ];
    }

    // Partially approved / awaiting next reviewer
    if (str_contains($msg, 'approved') || str_contains($msg, 'awaiting')) {
        return [
            'bg' => 'bg-warning',
            'text' => 'text-warning',
            'border' => 'border-warning',
            'icon' => '<i class="bi bi-clock-history"></i>',
        ];
    }

    // Default / informational (e.g. new submission, registration)
    return [
        'bg' => 'bg-secondary',
        'text' => 'text-secondary',
        'border' => 'border-secondary',
        'icon' => '<i class="bi bi-bell-fill"></i>',
    ];
}

/* ============================
   ROLE HELPERS
============================ */

function get_role_name($role_id)
{
    $conn = get_db();
    $stmt = $conn->prepare("SELECT role_name FROM roles WHERE id = ?");
    $stmt->execute([$role_id]);
    return $stmt->fetchColumn();
}

function get_role_id($role_name)
{
    $conn = get_db();
    $stmt = $conn->prepare("SELECT id FROM roles WHERE role_name = ?");
    $stmt->execute([$role_name]);
    return $stmt->fetchColumn();
}

/* ============================
   USER FETCHERS
============================ */

function get_user_by_id($user_id)
{
    $conn = get_db();
    $stmt = $conn->prepare("
        SELECT u.*, r.role_name, c.college_name
        FROM users u
        LEFT JOIN roles r       ON u.role_id    = r.id
        LEFT JOIN colleges c    ON u.college_id = c.id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function update_user($user_id, $first_name, $last_name, $email, $role_id, $college_id)
{
    try {
        $conn = get_db();
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, role_id = ?, college_id = ? WHERE id = ?");
        return $stmt->execute([$first_name, $last_name, $email, $role_id, $college_id, $user_id]);
    } catch (PDOException $e) {
        error_log("update_user error: " . $e->getMessage());
        return false;
    }
}

function get_dean($college_id = null)
{
    $conn = get_db();
    // If college_id is provided, try to find the dean for that specific college first
    if ($college_id) {
        $stmt = $conn->prepare("
            SELECT u.* FROM users u
            JOIN roles r ON u.role_id = r.id
               AND u.college_id = ?
            LIMIT 1
        ");
        $stmt->execute([$college_id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res)
            return $res;
    }

    // Fallback: Find any active user with the 'dean' role
    $stmt = $conn->prepare("
        SELECT u.* FROM users u
        JOIN roles r ON u.role_id = r.id
          AND r.role_name = 'dean'
        ORDER BY u.id ASC
        LIMIT 1
    ");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_vpaa()
{
    $conn = get_db();
    $stmt = $conn->prepare("
        SELECT u.* FROM users u
        JOIN roles r ON u.role_id = r.id
          AND r.role_name = 'vpaa'
        LIMIT 1
    ");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ============================
   SYLLABUS FETCHERS
============================ */

function get_syllabus_details($syllabus_id)
{
    $conn = get_db();
    $stmt = $conn->prepare("
        SELECT s.*,
               u.first_name, u.last_name, u.email,
               r.role_name AS uploader_role,
               COALESCE(NULLIF(s.course_code,  ''), c.course_code)  AS course_code,
               COALESCE(NULLIF(s.course_title, ''), c.course_title) AS course_title,
               COALESCE(u.college_id, c.college_id) AS college_id,
               col.college_name
        FROM syllabus s
        LEFT JOIN users u       ON s.uploaded_by   = u.id
        LEFT JOIN roles r       ON u.role_id       = r.id
        LEFT JOIN courses c     ON s.course_id     = c.id
        LEFT JOIN colleges col  ON COALESCE(u.college_id, c.college_id) = col.id
        WHERE s.id = ?
    ");
    $stmt->execute([$syllabus_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_syllabus_details_with_dept($syllabus_id)
{
    // Alias for get_syllabus_details since departments are removed
    return get_syllabus_details($syllabus_id);
}

function get_workflow_history($syllabus_id)
{
    $conn = get_db();
    $stmt = $conn->prepare("
        SELECT sw.*, r.role_name, u.first_name, u.last_name
        FROM syllabus_workflow sw
        LEFT JOIN roles r ON sw.role_id     = r.id
        LEFT JOIN users u ON sw.reviewer_id = u.id
        WHERE sw.syllabus_id = ?
        ORDER BY sw.step_order ASC
    ");
    $stmt->execute([$syllabus_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_faculty_submissions($user_id)
{
    $conn = get_db();
    $stmt = $conn->prepare("
        SELECT s.*,
               COALESCE(NULLIF(s.course_code,  ''), c.course_code)  AS course_code,
               COALESCE(NULLIF(s.course_title, ''), c.course_title) AS course_title,
               col.college_name,
               (
                   SELECT sw.comment
                   FROM syllabus_workflow sw
                   WHERE sw.syllabus_id = s.id
                     AND sw.action      = 'Rejected'
                   ORDER BY sw.action_at DESC
                   LIMIT 1
               ) AS reject_comment,
               (
                   SELECT CONCAT(u2.first_name, ' ', u2.last_name)
                   FROM syllabus_workflow sw2
                   JOIN users u2 ON sw2.reviewer_id = u2.id
                   WHERE sw2.syllabus_id = s.id
                     AND sw2.action      = 'Approved'
                   ORDER BY sw2.action_at DESC
                   LIMIT 1
               ) AS last_reviewer,
               (
                   SELECT r2.role_name
                   FROM syllabus_workflow sw3
                   JOIN roles r2 ON sw3.role_id = r2.id
                   WHERE sw3.syllabus_id = s.id
                     AND sw3.action      = 'Pending'
                   ORDER BY sw3.step_order ASC
                   LIMIT 1
               ) AS current_stage_role,
               (
                   SELECT r3.role_name
                   FROM syllabus_workflow sw4
                   JOIN roles r3 ON sw4.role_id = r3.id
                   WHERE sw4.syllabus_id = s.id
                     AND sw4.action      = 'Rejected'
                   ORDER BY sw4.action_at DESC
                   LIMIT 1
               ) AS rejecting_role,
               (
                   SELECT CONCAT(u3.first_name, ' ', u3.last_name)
                   FROM syllabus_workflow sw5
                   JOIN users u3 ON sw5.reviewer_id = u3.id
                   WHERE sw5.syllabus_id = s.id
                     AND sw5.action      = 'Rejected'
                   ORDER BY sw5.action_at DESC
                   LIMIT 1
               ) AS rejecting_name
        FROM syllabus s
        LEFT JOIN courses c     ON s.course_id      = c.id
        LEFT JOIN users u       ON s.uploaded_by    = u.id
        LEFT JOIN colleges col ON COALESCE(c.college_id, u.college_id) = col.id
        WHERE s.uploaded_by = ?
        ORDER BY s.submitted_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_shared_syllabi($college_id = null)
{
    $pdo = get_db();
    $sql = "
        SELECT
            s.id,
            s.course_code,
            s.course_title,
            s.subject_type,
            s.semester,
            s.school_year,
            s.file_path,
            s.submitted_at,
            CONCAT(u.first_name, ' ', u.last_name) AS faculty_name,
            u.first_name, u.last_name,
            u.email AS uploader_email,
            col.college_name
        FROM syllabus s
        JOIN users u           ON u.id        = s.uploaded_by
        LEFT JOIN colleges col ON col.id      = u.college_id
        WHERE s.status = 'Approved'
    ";
    $params = [];
    if ($college_id) {
        $sql .= " AND u.college_id = ?";
        $params[] = $college_id;
    }
    $sql .= " ORDER BY s.submitted_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_courses($college_id = null)
{
    $conn = get_db();
    if ($college_id) {
        $stmt = $conn->prepare("SELECT * FROM courses WHERE college_id = ? ORDER BY course_code");
        $stmt->execute([$college_id]);
    } else {
        $stmt = $conn->prepare("SELECT * FROM courses ORDER BY course_code");
        $stmt->execute();
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_colleges()
{
    $conn = get_db();
    $stmt = $conn->prepare("SELECT * FROM colleges ORDER BY college_name");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ============================
   WORKFLOW RULES
============================ */

function get_step_order($role_name)
{
    return match ($role_name) {
        'dean' => 1,
        'vpaa' => 2,
        default => 99
    };
}

function determine_next_role($current_role)
{
    return match ($current_role) {
        'faculty' => 'dean',
        'dean' => 'vpaa',
        'vpaa' => null,
        default => null
    };
}

function init_syllabus_workflow($syllabus_id, $uploader_role = 'faculty')
{
    $conn = get_db();

    $exists = $conn->prepare("SELECT COUNT(*) FROM syllabus_workflow WHERE syllabus_id = ? AND action = 'Pending'");
    $exists->execute([$syllabus_id]);
    if ((int) $exists->fetchColumn() > 0)
        return;

    $is_dean = in_array($uploader_role, ['dean', 'admin']);

    if ($is_dean) {
        $role_id = get_role_id('vpaa');
        $conn->prepare("
            INSERT INTO syllabus_workflow (syllabus_id, step_order, role_id, action)
            VALUES (?, 2, ?, 'Pending')
        ")->execute([$syllabus_id, $role_id]);
        notify_next_reviewer($syllabus_id, 'vpaa');
    } else {
        $role_id = get_role_id('dean');
        $conn->prepare("
            INSERT INTO syllabus_workflow (syllabus_id, step_order, role_id, action)
            VALUES (?, 1, ?, 'Pending')
        ")->execute([$syllabus_id, $role_id]);
        notify_next_reviewer($syllabus_id, 'dean');
    }
}

/**
 * Reset syllabus workflow after edit
 */
function reset_syllabus_workflow($syllabus_id, $uploader_role = 'faculty')
{
    $conn = get_db();
    // Wipe all existing steps for this submission
    $conn->prepare("DELETE FROM syllabus_workflow WHERE syllabus_id = ?")->execute([$syllabus_id]);
    // Ensure status is Pending
    $conn->prepare("UPDATE syllabus SET status = 'Pending' WHERE id = ?")->execute([$syllabus_id]);
    // Re-init
    init_syllabus_workflow($syllabus_id, $uploader_role);
}

/* ============================
   WORKFLOW NOTIFICATIONS
============================ */

function notify_next_reviewer($syllabus_id, $next_role)
{
    $syllabus = get_syllabus_details_with_dept($syllabus_id);
    if (!$syllabus)
        return;

    $college_id = $syllabus['college_id'] ?? null;
    $user = ($next_role === 'dean') ? get_dean($college_id) : get_vpaa();

    if ($user) {
        notify_user(
            $user['id'],
            "New syllabus awaiting your approval: " . $syllabus['course_code'],
            $syllabus_id
        );
    }
}

function notify_rejection($syllabus_id, $role)
{
    $syllabus = get_syllabus_details_with_dept($syllabus_id);
    if (!$syllabus)
        return;
    notify_user(
        $syllabus['uploaded_by'],
        "Your syllabus (" . $syllabus['course_code'] . ") was rejected by the "
        . ucfirst(str_replace('_', ' ', $role)),
        $syllabus_id
    );
}

function notify_on_vpaa_approval($syllabus_id)
{
    $syllabus = get_syllabus_details_with_dept($syllabus_id);
    if (!$syllabus)
        return;
    notify_user(
        $syllabus['uploaded_by'],
        "Your syllabus (" . $syllabus['course_code'] . ") has been fully approved by VPAA",
        $syllabus_id
    );
}

/* ============================
   MAIN WORKFLOW ENGINE
   Flow: faculty → dean (step 1) → vpaa (step 2, final)
   Dean approval keeps status 'Pending' until VPAA gives final approval.
============================ */

function process_syllabus_action($syllabus_id, $action, $comment = null)
{
    if (session_status() === PHP_SESSION_NONE)
        session_start();

    $conn = get_db();
    $user_id = $_SESSION['user_id'];

    $role_id = $_SESSION['role_id']
        ?? get_role_id($_SESSION['role_name'] ?? $_SESSION['role'] ?? '');

    if (!$role_id) {
        error_log("process_syllabus_action: could not resolve role_id from session");
        return;
    }

    $role = get_role_name($role_id); // 'dean' or 'vpaa'

    // Update the Pending workflow step for this reviewer's role
    $upd = $conn->prepare("
        UPDATE syllabus_workflow
        SET action      = ?,
            comment     = ?,
            reviewer_id = ?,
            action_at   = NOW()
        WHERE syllabus_id = ?
          AND role_id     = ?
          AND action      = 'Pending'
    ");
    $upd->execute([$action, $comment, $user_id, $syllabus_id, $role_id]);

    $rows_affected = $upd->rowCount();

    if ($rows_affected === 0) {
        error_log("process_syllabus_action: no Pending row found for syllabus_id={$syllabus_id} role_id={$role_id}. Upserting completed step.");
        $conn->prepare("
            INSERT INTO syllabus_workflow (syllabus_id, step_order, role_id, action, reviewer_id, action_at, comment)
            VALUES (?, ?, ?, ?, ?, NOW(), ?)
            ON DUPLICATE KEY UPDATE
                action      = VALUES(action),
                reviewer_id = VALUES(reviewer_id),
                action_at   = NOW(),
                comment     = VALUES(comment)
        ")->execute([
            $syllabus_id,
            get_step_order($role),
            $role_id,
            $action,
            $user_id,
            $comment
        ]);
    }

    // ── Rejected ─────────────────────────────────────────────────────────────
    if ($action === 'Rejected') {
        $conn->prepare("UPDATE syllabus SET status = 'Rejected' WHERE id = ?")
            ->execute([$syllabus_id]);
        notify_rejection($syllabus_id, $role);
        return true;
    }

    // STEP 2: Determine next role in the chain
    $next_role = determine_next_role($role);

    if ($next_role === null) {
        // VPAA is final — only NOW mark as fully Approved
        $conn->prepare("UPDATE syllabus SET status = 'Approved' WHERE id = ?")
            ->execute([$syllabus_id]);
        notify_on_vpaa_approval($syllabus_id);
        return true;
    }

    // Dean approved — keep syllabus as Pending until VPAA acts
    // Do NOT set status = 'Approved' here
    $conn->prepare("UPDATE syllabus SET status = 'Pending' WHERE id = ?")
        ->execute([$syllabus_id]);

    // Insert the next pending workflow step (skip if already exists)
    $next_role_id = get_role_id($next_role);
    $dup = $conn->prepare("
        SELECT COUNT(*) FROM syllabus_workflow
        WHERE syllabus_id = ? AND role_id = ? AND action = 'Pending'
    ");
    $dup->execute([$syllabus_id, $next_role_id]);
    if ((int) $dup->fetchColumn() === 0) {
        $conn->prepare("
            INSERT INTO syllabus_workflow (syllabus_id, step_order, role_id, action)
            VALUES (?, ?, ?, 'Pending')
        ")->execute([$syllabus_id, get_step_order($next_role), $next_role_id]);
    }

    notify_next_reviewer($syllabus_id, $next_role);
    return true;
}

/* ============================
   STATUS BADGE HELPER
============================ */

function format_syllabus_status($status, $current_stage_role = null, $rejecting_role = null, $rejecting_name = null)
{
    if ($status === 'Approved') {
        return '<span class="badge rounded-pill px-3 py-1" style="font-size:0.72rem; background:var(--success-light); color:var(--success); border:1px solid var(--success-light) !important">APPROVED BY VPAA</span>';
    }
    if ($status === 'Rejected') {
        $role_text = $rejecting_role ? strtoupper(str_replace('_', ' ', $rejecting_role)) : 'ADMIN';
        $name_text = $rejecting_name ? " — " . htmlspecialchars($rejecting_name) : "";
        return '<span class="badge rounded-pill px-3 py-1" style="font-size:0.72rem; background:var(--danger-light); color:var(--danger); border:1px solid var(--danger-light) !important">REJECTED BY ' . $role_text . $name_text . '</span>';
    }
    // Pending — show which stage
    if ($current_stage_role === 'vpaa') {
        return '<span class="badge rounded-pill px-3 py-1" style="font-size:0.72rem; background:var(--primary-light); color:var(--primary); border:1px solid var(--primary-light) !important">AWAITING VPAA APPROVAL</span>';
    }
    return '<span class="badge rounded-pill px-3 py-1" style="font-size:0.72rem; background:var(--warning-light); color:var(--warning); border:1px solid var(--warning-light) !important">AWAITING DEAN REVIEW</span>';
}

/* ============================
   SCHOOL YEAR HELPER
============================ */

function get_current_school_year()
{
    $year = (int) date('Y');
    $month = (int) date('n');
    $start = ($month < 6) ? $year - 1 : $year;
    return $start . '–' . ($start + 1);
}

/* ============================
   SESSION SAFETY HELPER
============================ */

function ensure_role_in_session()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: ../login.php');
        exit();
    }

    // Check if role_id is missing but role name exists in session
    if (!isset($_SESSION['role_id']) && isset($_SESSION['role'])) {
        $_SESSION['role_id'] = get_role_id($_SESSION['role']);
    }
}

/**
 * Restrict access to a specific role.
 * Uses SESSION role as the primary source of truth.
 * Also enforces server-side inactivity timeout (30 minutes).
 */
function restrict_to_role($allowed_role)
{
    ensure_role_in_session();

    // ── Backend Inactivity Guard ──────────────────────────────────────────────
    $session_timeout = 1800; // 30 minutes in seconds
    if (isset($_SESSION['last_activity'])) {
        if ((time() - $_SESSION['last_activity']) > $session_timeout) {
            // Session expired — destroy and redirect
            $_SESSION = [];
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 3600, '/', '', false, true);
            }
            session_destroy();
            header('Location: ../login.php?timeout=1');
            exit();
        }
    }
    // Refresh last activity timestamp on every protected page load
    $_SESSION['last_activity'] = time();

    $current_role = $_SESSION['role'] ?? '';

    if (strtolower($current_role) !== strtolower($allowed_role)) {
        // Log the unauthorized attempt
        error_log("Unauthorized access attempt by user ID " . ($_SESSION['user_id'] ?? 'unknown') . " (Role: $current_role) to $allowed_role restricted area.");

        // Redirect to appropriate dashboard based on their ACTUAL role
        switch (strtolower($current_role)) {
            case 'vpaa':
                header('Location: ../vpaa/vpaa_dashboard.php');
                break;
            case 'dean':
                header('Location: ../admin/admin_dashboard.php');
                break;
            case 'department_head':
                header('Location: ../dept_head/dept_dashboard.php');
                break;
            case 'faculty':
                header('Location: ../faculty/faculty_dashboard.php');
                break;
            default:
                header('Location: ../login.php');
                break;
        }
        exit();
    }
}

/* ============================
   CURRENT USER HELPER
============================ */

function current_user()
{
    if (session_status() === PHP_SESSION_NONE)
        session_start();
    if (!isset($_SESSION['user_id']))
        return null;
    return get_user_by_id($_SESSION['user_id']);
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

function send_system_email($to_email, $subject, $body)
{
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_USER, 'SCC Syllabus Portal');
        $mail->addAddress($to_email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}