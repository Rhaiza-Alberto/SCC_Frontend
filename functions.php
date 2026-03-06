 <?php
/**
 * functions.php
 * Helper + Workflow Engine for Syllabus Normalized System
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

/**
 * Get notifications for a user (most recent first).
 */
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

/**
 * Count unread notifications for a user.
 */
function count_unread_notifications($user_id) {
    $conn = get_db();
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0
    ");
    $stmt->execute([$user_id]);
    return (int) $stmt->fetchColumn();
}

/**
 * Mark a single notification as read.
 */
function mark_notification_read($notification_id) {
    $conn = get_db();
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->execute([$notification_id]);
}

/**
 * Mark all notifications as read for a user.
 */
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

function get_department_head($department_id) {
    $conn = get_db();
    $stmt = $conn->prepare("
        SELECT u.*
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE r.role_name = 'department_head'
          AND u.department_id = ?
          AND u.is_deleted = 0
        LIMIT 1
    ");
    $stmt->execute([$department_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_dean($department_id) {
    $conn = get_db();
    $stmt = $conn->prepare("
        SELECT u.*
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE r.role_name = 'dean'
          AND u.department_id = ?
          AND u.is_deleted = 0
        LIMIT 1
    ");
    $stmt->execute([$department_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_vpaa() {
    $conn = get_db();
    $stmt = $conn->prepare("
        SELECT u.*
        FROM users u
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
               r.role_name  AS uploader_role,
               -- Use syllabus own columns for display; fall back to joined course if code is empty
               COALESCE(NULLIF(s.course_code,  ''), c.course_code)  AS course_code,
               COALESCE(NULLIF(s.course_title, ''), c.course_title) AS course_title,
               c.department_id,
               d.department_name,
               col.college_name
        FROM syllabus s
        LEFT JOIN users u       ON s.uploaded_by   = u.id
        LEFT JOIN roles r       ON u.role_id        = r.id
        LEFT JOIN courses c     ON s.course_id      = c.id
        LEFT JOIN departments d ON c.department_id  = d.id
        LEFT JOIN colleges col  ON d.college_id     = col.id
        WHERE s.id = ?
    ");
    $stmt->execute([$syllabus_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Extended version that resolves department_id even when course_id is NULL,
 * by looking up the uploader's department.
 */
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
        LEFT JOIN roles r ON sw.role_id    = r.id
        LEFT JOIN users u ON sw.reviewer_id = u.id
        WHERE sw.syllabus_id = ?
        ORDER BY sw.step_order ASC
    ");
    $stmt->execute([$syllabus_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get all syllabi uploaded by a specific user.
 * Uses syllabus own columns (course_code, course_title) stored at upload time,
 * with fallback to joined courses table if course_id is set.
 */
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
               ) AS last_reviewer
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

/**
 * Get all approved syllabi for the shared repository.
 * Uses syllabus own columns with fallback to joined courses table.
 */
function get_shared_syllabi($department_id = null) {
    $conn = get_db();
    $sql  = "
        SELECT s.*,
               COALESCE(NULLIF(s.course_code,  ''), c.course_code)  AS course_code,
               COALESCE(NULLIF(s.course_title, ''), c.course_title) AS course_title,
               d.department_name,
               col.college_name,
               u.first_name, u.last_name, u.email
        FROM syllabus s
        LEFT JOIN courses c     ON s.course_id      = c.id
        LEFT JOIN users u       ON s.uploaded_by    = u.id
        LEFT JOIN departments d ON COALESCE(c.department_id, u.department_id) = d.id
        LEFT JOIN colleges col  ON d.college_id     = col.id
        WHERE s.status = 'Approved'
    ";
    $params = [];
    if ($department_id) {
        $sql    .= " AND COALESCE(c.department_id, u.department_id) = ?";
        $params[] = $department_id;
    }
    $sql .= " ORDER BY s.submitted_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get all courses, optionally filtered by department.
 */
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

/**
 * Get all departments.
 */
function get_departments() {
    $conn = get_db();
    $stmt = $conn->prepare("SELECT * FROM departments ORDER BY department_name");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get all colleges.
 */
function get_colleges() {
    $conn = get_db();
    $stmt = $conn->prepare("SELECT * FROM colleges ORDER BY college_name");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ============================
   WORKFLOW RULES
============================ */

function get_step_order($role_name) {
    return match ($role_name) {
        'department_head' => 1,
        'dean'            => 2,
        'vpaa'            => 3,
        default           => 99
    };
}

function determine_next_role($current_role) {
    return match ($current_role) {
        'faculty'         => 'department_head',
        'department_head' => 'dean',
        'dean'            => 'vpaa',
        'vpaa'            => null,
        default           => null
    };
}

/* ============================
   WORKFLOW NOTIFICATIONS
============================ */

function notify_next_reviewer($syllabus_id, $next_role) {
    // Use extended version that resolves dept even without course_id
    $syllabus = get_syllabus_details_with_dept($syllabus_id);
    if (!$syllabus) return;

    $department_id = $syllabus['department_id'] ?? null;

    if ($next_role === 'department_head') {
        $user = $department_id ? get_department_head($department_id) : null;
    } elseif ($next_role === 'dean') {
        $user = $department_id ? get_dean($department_id) : null;
    } else {
        $user = get_vpaa();
    }

    if ($user) {
        notify_user(
            $user['id'],
            "📄 New syllabus awaiting your approval: " . $syllabus['course_code'],
            $syllabus_id
        );
    }
}

function notify_rejection($syllabus_id, $by_role) {
    $syllabus = get_syllabus_details_with_dept($syllabus_id);
    if (!$syllabus) return;

    notify_user(
        $syllabus['uploaded_by'],
        "❌ Your syllabus (" . $syllabus['course_code'] . ") was rejected by the "
            . ucfirst(str_replace('_', ' ', $by_role)),
        $syllabus_id
    );
}

function notify_on_vpaa_approval($syllabus_id) {
    $syllabus = get_syllabus_details_with_dept($syllabus_id);
    if (!$syllabus) return;

    notify_user(
        $syllabus['uploaded_by'],
        "✅ Your syllabus (" . $syllabus['course_code'] . ") has been fully approved by VPAA",
        $syllabus_id
    );
}

/* ============================
   MAIN WORKFLOW ENGINE
============================ */

function process_syllabus_action($syllabus_id, $action, $comment = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $conn    = get_db();
    $user_id = $_SESSION['user_id'];
    $role_id = $_SESSION['role_id'];
    $role    = get_role_name($role_id);

    // Update the current pending workflow step for this reviewer's role
    $stmt = $conn->prepare("
        UPDATE syllabus_workflow
        SET action      = ?,
            comment     = ?,
            reviewer_id = ?,
            action_at   = NOW()
        WHERE syllabus_id = ?
          AND role_id     = ?
          AND action      = 'Pending'
    ");
    $stmt->execute([$action, $comment, $user_id, $syllabus_id, $role_id]);

    if ($action === 'Rejected') {
        $conn->prepare("UPDATE syllabus SET status = 'Rejected' WHERE id = ?")
             ->execute([$syllabus_id]);
        notify_rejection($syllabus_id, $role);
        return;
    }

    // Approved — determine next step
    $next_role = determine_next_role($role);

    if ($next_role === null) {
        // Final approval (VPAA)
        $conn->prepare("UPDATE syllabus SET status = 'Approved' WHERE id = ?")
             ->execute([$syllabus_id]);
        notify_on_vpaa_approval($syllabus_id);
        return;
    }

    // Insert next workflow step
    $conn->prepare("
        INSERT INTO syllabus_workflow (syllabus_id, step_order, role_id, action)
        VALUES (?, ?, ?, 'Pending')
    ")->execute([
        $syllabus_id,
        get_step_order($next_role),
        get_role_id($next_role),
    ]);

    notify_next_reviewer($syllabus_id, $next_role);
}

/* ============================
   SCHOOL YEAR HELPER
============================ */

function get_current_school_year() {
    $year  = (int) date('Y');
    $month = (int) date('n');
    // School year starts in June
    $start = ($month < 6) ? $year - 1 : $year;
    return $start . '–' . ($start + 1);
}

/* ============================
   SESSION SAFETY HELPER
============================ */

function ensure_role_in_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['role_id']) && isset($_SESSION['role'])) {
        $_SESSION['role_id'] = get_role_id($_SESSION['role']);
    }
}

/* ============================
   CURRENT USER HELPER
============================ */

function current_user() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    return get_user_by_id($_SESSION['user_id']);
}