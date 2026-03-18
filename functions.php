<?php
/**
 * functions.php
 * Helper + Workflow Engine
 * Workflow: faculty → dean → vpaa  (department_head step removed)
 */

require_once __DIR__ . '/database.php';

/* ============================
   DATABASE HELPER
============================ */

function get_db() {
    static $conn = null;
    if ($conn === null) {
        $db   = new Database();
        $conn = $db->connect();
    }
    return $conn;
}

/* ============================
   NOTIFICATIONS
============================ */

function notify_user($user_id, $message, $syllabus_id = null) {
    try {
        $conn = get_db();
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, syllabus_id, message, is_read, created_at)
            VALUES (?, ?, ?, 0, NOW())
        ");
        $stmt->execute([$user_id, $syllabus_id, $message]);
        return true;
    } catch (PDOException $e) {
        error_log("Notify Error: " . $e->getMessage());
        return false;
    }
}

function get_notifications($user_id, $limit = 10) {
    $conn  = get_db();
    $limit = (int) $limit;
    $stmt  = $conn->prepare("
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

function count_unread_notifications($user_id) {
    $conn = get_db();
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0
    ");
    $stmt->execute([$user_id]);
    return (int) $stmt->fetchColumn();
}

function mark_notification_read($notification_id) {
    $conn = get_db();
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->execute([$notification_id]);
}

function mark_all_notifications_read($user_id) {
    $conn = get_db();
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
}

/* ============================
   ROLE HELPERS
============================ */

function get_role_name($role_id) {
    $conn = get_db();
    $stmt = $conn->prepare("SELECT role_name FROM roles WHERE id = ?");
    $stmt->execute([$role_id]);
    return $stmt->fetchColumn();
}

function get_role_id($role_name) {
    $conn = get_db();
    $stmt = $conn->prepare("SELECT id FROM roles WHERE role_name = ?");
    $stmt->execute([$role_name]);
    return $stmt->fetchColumn();
}

/* ============================
   USER FETCHERS
============================ */

function get_user_by_id($user_id) {
    $conn = get_db();
    $stmt = $conn->prepare("
        SELECT u.*, r.role_name, d.department_name, c.college_name
        FROM users u
        LEFT JOIN roles r       ON u.role_id       = r.id
        LEFT JOIN departments d ON u.department_id = d.id
        LEFT JOIN colleges c    ON d.college_id    = c.id
        WHERE u.id = ? AND u.is_deleted = 0
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_dean($department_id = null) {
    $conn = get_db();
    if ($department_id) {
        $stmt = $conn->prepare("
            SELECT u.* FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE r.role_name = 'dean'
              AND u.department_id = ?
              AND u.is_deleted = 0
            LIMIT 1
        ");
        $stmt->execute([$department_id]);
    } else {
        $stmt = $conn->prepare("
            SELECT u.* FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE r.role_name = 'dean'
              AND u.is_deleted = 0
            LIMIT 1
        ");
        $stmt->execute();
    }
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_vpaa() {
    $conn = get_db();
    $stmt = $conn->prepare("
        SELECT u.* FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE r.role_name = 'vpaa'
          AND u.is_deleted = 0
        LIMIT 1
    ");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ============================
   SYLLABUS FETCHERS
============================ */

function get_syllabus_details($syllabus_id) {
    $conn = get_db();
    $stmt = $conn->prepare("
        SELECT s.*,
               u.first_name, u.last_name, u.email,
               r.role_name AS uploader_role,
               COALESCE(NULLIF(s.course_code,  ''), c.course_code)  AS course_code,
               COALESCE(NULLIF(s.course_title, ''), c.course_title) AS course_title,
               c.department_id,
               d.department_name,
               col.college_name
        FROM syllabus s
        LEFT JOIN users u       ON s.uploaded_by  = u.id
        LEFT JOIN roles r       ON u.role_id       = r.id
        LEFT JOIN courses c     ON s.course_id     = c.id
        LEFT JOIN departments d ON c.department_id = d.id
        LEFT JOIN colleges col  ON d.college_id    = col.id
        WHERE s.id = ?
    ");
    $stmt->execute([$syllabus_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_syllabus_details_with_dept($syllabus_id) {
    $conn = get_db();
    $stmt = $conn->prepare("
        SELECT s.*,
               u.first_name, u.last_name, u.email,
               r.role_name AS uploader_role,
               COALESCE(NULLIF(s.course_code,  ''), c.course_code)  AS course_code,
               COALESCE(NULLIF(s.course_title, ''), c.course_title) AS course_title,
               COALESCE(c.department_id, u.department_id)            AS department_id,
               d.department_name,
               col.college_name
        FROM syllabus s
        LEFT JOIN users u       ON s.uploaded_by                     = u.id
        LEFT JOIN roles r       ON u.role_id                         = r.id
        LEFT JOIN courses c     ON s.course_id                       = c.id
        LEFT JOIN departments d ON COALESCE(c.department_id, u.department_id) = d.id
        LEFT JOIN colleges col  ON d.college_id                      = col.id
        WHERE s.id = ?
    ");
    $stmt->execute([$syllabus_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_workflow_history($syllabus_id) {
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

function get_faculty_submissions($user_id) {
    $conn = get_db();
    $stmt = $conn->prepare("
        SELECT s.*,
               COALESCE(NULLIF(s.course_code,  ''), c.course_code)  AS course_code,
               COALESCE(NULLIF(s.course_title, ''), c.course_title) AS course_title,
               d.department_name,
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
               ) AS rejecting_role
        FROM syllabus s
        LEFT JOIN courses c     ON s.course_id      = c.id
        LEFT JOIN users u       ON s.uploaded_by    = u.id
        LEFT JOIN departments d ON COALESCE(c.department_id, u.department_id) = d.id
        WHERE s.uploaded_by = ?
        ORDER BY s.submitted_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// get_shared_syllabi returns EMPTY.
// After VPAA approves, syllabi only appear in faculty My Submissions — not shared.
function get_shared_syllabi($department_id = null) {
    return [];
}

function get_courses($department_id = null) {
    $conn = get_db();
    if ($department_id) {
        $stmt = $conn->prepare("SELECT * FROM courses WHERE department_id = ? ORDER BY course_code");
        $stmt->execute([$department_id]);
    } else {
        $stmt = $conn->prepare("SELECT * FROM courses ORDER BY course_code");
        $stmt->execute();
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_departments() {
    $conn = get_db();
    $stmt = $conn->prepare("SELECT * FROM departments ORDER BY department_name");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_colleges() {
    $conn = get_db();
    $stmt = $conn->prepare("SELECT * FROM colleges ORDER BY college_name");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ============================
   WORKFLOW RULES
   Flow: faculty/dean upload → dean approves (step 1) → vpaa final (step 2)
============================ */

function get_step_order($role_name) {
    return match ($role_name) {
        'dean' => 1,
        'vpaa' => 2,
        default => 99
    };
}

function determine_next_role($current_role) {
    return match ($current_role) {
        'faculty' => 'dean',
        'dean'    => 'vpaa',
        'vpaa'    => null,
        default   => null
    };
}

function init_syllabus_workflow($syllabus_id, $uploader_role = 'faculty') {
    $conn = get_db();

    $exists = $conn->prepare("SELECT COUNT(*) FROM syllabus_workflow WHERE syllabus_id = ?");
    $exists->execute([$syllabus_id]);
    if ((int) $exists->fetchColumn() > 0) return;

    $is_dean = in_array($uploader_role, ['dean', 'admin']);

    if ($is_dean) {
        $role_id = get_role_id('vpaa');
        if (!$role_id) { error_log("init_syllabus_workflow: 'vpaa' role not found"); return; }
        $conn->prepare("
            INSERT INTO syllabus_workflow (syllabus_id, step_order, role_id, action)
            VALUES (?, 2, ?, 'Pending')
        ")->execute([$syllabus_id, $role_id]);
        notify_next_reviewer($syllabus_id, 'vpaa');
    } else {
        $role_id = get_role_id('dean');
        if (!$role_id) { error_log("init_syllabus_workflow: 'dean' role not found"); return; }
        $conn->prepare("
            INSERT INTO syllabus_workflow (syllabus_id, step_order, role_id, action)
            VALUES (?, 1, ?, 'Pending')
        ")->execute([$syllabus_id, $role_id]);
        notify_next_reviewer($syllabus_id, 'dean');
    }
}

/* ============================
   WORKFLOW NOTIFICATIONS
============================ */

function notify_next_reviewer($syllabus_id, $next_role) {
    $syllabus = get_syllabus_details_with_dept($syllabus_id);
    if (!$syllabus) return;

    $department_id = $syllabus['department_id'] ?? null;
    $user = ($next_role === 'dean') ? get_dean($department_id) : get_vpaa();

    if ($user) {
        notify_user(
            $user['id'],
            "New syllabus awaiting your approval: " . $syllabus['course_code'],
            $syllabus_id
        );
    }
}

function notify_rejection($syllabus_id, $by_role) {
    $syllabus = get_syllabus_details_with_dept($syllabus_id);
    if (!$syllabus) return;
    notify_user(
        $syllabus['uploaded_by'],
        "Your syllabus (" . $syllabus['course_code'] . ") was rejected by the "
            . ucfirst(str_replace('_', ' ', $by_role)),
        $syllabus_id
    );
}

function notify_on_vpaa_approval($syllabus_id) {
    $syllabus = get_syllabus_details_with_dept($syllabus_id);
    if (!$syllabus) return;
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

function process_syllabus_action($syllabus_id, $action, $comment = null) {
    if (session_status() === PHP_SESSION_NONE) session_start();

    $conn    = get_db();
    $user_id = $_SESSION['user_id'];
    $role_id = $_SESSION['role_id'];
    $role    = get_role_name($role_id);

    try {
        $conn->beginTransaction();

        // STEP 1: Update ONLY the correct pending row for this reviewer's role
        $stmt = $conn->prepare("
            UPDATE syllabus_workflow
            SET action = ?, comment = ?, reviewer_id = ?, action_at = NOW()
            WHERE syllabus_id = ? AND role_id = ? AND action = 'Pending'
        ");
        $stmt->execute([$action, $comment, $user_id, $syllabus_id, $role_id]);

        if ($stmt->rowCount() === 0) {
            throw new Exception("No pending step found. Already processed or invalid.");
        }

        // IF REJECTED → update status, notify, stop
        if ($action === 'Rejected') {
            $conn->prepare("UPDATE syllabus SET status = 'Rejected' WHERE id = ?")
                 ->execute([$syllabus_id]);
            notify_rejection($syllabus_id, $role);
            $conn->commit();
            return true;
        }

        // STEP 2: Determine next role in the chain
        $next_role = determine_next_role($role);

        if ($next_role === null) {
            // FINAL STEP — VPAA approved, mark fully Approved and notify faculty
            $conn->prepare("UPDATE syllabus SET status = 'Approved' WHERE id = ?")
                 ->execute([$syllabus_id]);
            notify_on_vpaa_approval($syllabus_id);
        } else {
            // Intermediate approval (dean) — keep Pending, queue next step for VPAA
            $next_role_id = get_role_id($next_role);
            $conn->prepare("
                INSERT INTO syllabus_workflow (syllabus_id, step_order, role_id, action)
                VALUES (?, ?, ?, 'Pending')
            ")->execute([
                $syllabus_id,
                get_step_order($next_role),
                $next_role_id
            ]);
            $conn->prepare("UPDATE syllabus SET status = 'Pending' WHERE id = ?")
                 ->execute([$syllabus_id]);
            notify_next_reviewer($syllabus_id, $next_role);
        }

        $conn->commit();
        return true;

    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Workflow Error: " . $e->getMessage());
        return false;
    }
}

/* ============================
   SCHOOL YEAR HELPER
============================ */

function get_current_school_year() {
    $year  = (int) date('Y');
    $month = (int) date('n');
    $start = ($month < 6) ? $year - 1 : $year;
    return $start . '–' . ($start + 1);
}

/* ============================
   SESSION SAFETY HELPER
============================ */

function ensure_role_in_session() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['role_id']) && isset($_SESSION['role'])) {
        $_SESSION['role_id'] = get_role_id($_SESSION['role']);
    }
}

/* ============================
   CURRENT USER HELPER
============================ */

function current_user() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) return null;
    return get_user_by_id($_SESSION['user_id']);
}

/* ============================
   STATUS BADGE HELPER
============================ */

function format_syllabus_status($status, $current_stage_role = null, $rejecting_role = null) {
    return match ($status) {
        'Approved' => '<span class="badge bg-success bg-opacity-25 text-success border border-success rounded-pill px-3" style="font-size:.75rem;">Approved</span>',
        'Rejected' => '<span class="badge bg-danger bg-opacity-25 text-danger border border-danger rounded-pill px-3" style="font-size:.75rem;">Rejected</span>',
        default    => '<span class="badge bg-warning text-dark bg-opacity-25 border border-warning rounded-pill px-3" style="font-size:.75rem;">Pending</span>',
    };
}