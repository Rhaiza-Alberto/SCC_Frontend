 <?php
/**
 * function.php
 * Helper + Workflow Engine for Syllabus Normalized System
 * SAFE TO REPLACE EXISTING FILE
 */

require_once __DIR__ . '/database.php';

/* ============================
   DATABASE HELPER
============================ */

function get_db() {
    $db = new Database();
    return $db->connect();
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
        LEFT JOIN roles r ON u.role_id = r.id
        LEFT JOIN departments d ON u.department_id = d.id
        LEFT JOIN colleges c ON d.college_id = c.id
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
               r.role_name AS uploader_role,
               c.course_code, c.course_title,
               d.department_id, d.department_name,
               col.college_name
        FROM syllabus s
        LEFT JOIN users u ON s.uploaded_by = u.id
        LEFT JOIN roles r ON u.role_id = r.id
        LEFT JOIN courses c ON s.course_id = c.id
        LEFT JOIN departments d ON c.department_id = d.id
        LEFT JOIN colleges col ON d.college_id = col.id
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
        LEFT JOIN roles r ON sw.role_id = r.id
        LEFT JOIN users u ON sw.reviewer_id = u.id
        WHERE sw.syllabus_id = ?
        ORDER BY sw.step_order ASC
    ");
    $stmt->execute([$syllabus_id]);
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
        'instructor'      => 'department_head',
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
    $syllabus = get_syllabus_details($syllabus_id);
    if (!$syllabus) return;

    if ($next_role === 'department_head') {
        $user = get_department_head($syllabus['department_id']);
    } elseif ($next_role === 'dean') {
        $user = get_dean($syllabus['department_id']);
    } else {
        $user = get_vpaa();
    }

    if ($user) {
        notify_user(
            $user['id'],
            "📄 New syllabus awaiting your approval",
            $syllabus_id
        );
    }
}

function notify_rejection($syllabus_id, $by_role) {
    $syllabus = get_syllabus_details($syllabus_id);
    if (!$syllabus) return;

    notify_user(
        $syllabus['uploaded_by'],
        "❌ Your syllabus was rejected by the " . ucfirst(str_replace('_', ' ', $by_role)),
        $syllabus_id
    );
}

function notify_on_vpaa_approval($syllabus_id) {
    $syllabus = get_syllabus_details($syllabus_id);
    if (!$syllabus) return;

    notify_user(
        $syllabus['uploaded_by'],
        "✅ Your syllabus has been fully approved by VPAA",
        $syllabus_id
    );
}

/* ============================
   MAIN WORKFLOW ENGINE
============================ */

function process_syllabus_action($syllabus_id, $action, $comment = null) {
    session_start();
    $conn = get_db();

    $user_id = $_SESSION['user_id'];
    $role_id = $_SESSION['role_id'];
    $role    = get_role_name($role_id);

    // Update workflow step
    $stmt = $conn->prepare("
        UPDATE syllabus_workflow
        SET action = ?, comment = ?, reviewer_id = ?, action_at = NOW()
        WHERE syllabus_id = ?
          AND role_id = ?
          AND action = 'Pending'
    ");
    $stmt->execute([
        $action,
        $comment,
        $user_id,
        $syllabus_id,
        $role_id
    ]);

    // Rejected
    if ($action === 'Rejected') {
        $conn->prepare("UPDATE syllabus SET status='Rejected' WHERE id=?")
             ->execute([$syllabus_id]);

        notify_rejection($syllabus_id, $role);
        return;
    }

    // Approved → next role
    $next_role = determine_next_role($role);

    // Final approval
    if ($next_role === null) {
        $conn->prepare("UPDATE syllabus SET status='Approved' WHERE id=?")
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
        get_role_id($next_role)
    ]);

    notify_next_reviewer($syllabus_id, $next_role);
}
/* ============================
   SCHOOL YEAR HELPER
============================ */

/**
 * Get current school year in format YYYY–YYYY
 * Example: 2025–2026
 */
function get_current_school_year() {
    $year = date('Y');
    $next = $year + 1;
    return $year . '–' . $next;
}
/* ============================
   SESSION SAFETY HELPER
============================ */

/**
 * Ensure role_id exists in session based on role_name
 */
function ensure_role_in_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['role_id']) && isset($_SESSION['role_name'])) {
        $_SESSION['role_id'] = get_role_id($_SESSION['role_name']);
    }
}

/* ============================
   CURRENT USER HELPER
============================ */

/**
 * Get current logged-in user info
 */
function current_user() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    return get_user_by_id($_SESSION['user_id']);
}
