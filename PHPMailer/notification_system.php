<?php
/**
 * ========================================
 * COMPLETE NOTIFICATION SYSTEM
 * Enhanced notification & email system
 * ========================================
 */

// ============================================
// 1. NOTIFICATION CREATION FUNCTIONS
// ============================================

/**
 * Create notification for user with email
 */
function create_notification_with_email($user_id, $title, $message, $type = 'info', $related_id = null, $related_type = null)
{
    global $conn;

    // Create in-app notification
    $notification_id = create_notification($user_id, $title, $message, $type, $related_id, $related_type);

    // Get user email
    $user_query = mysqli_query($conn, "SELECT email, full_name, username FROM users WHERE user_id = $user_id");
    if ($user_data = mysqli_fetch_assoc($user_query)) {
        if (!empty($user_data['email'])) {
            $user_name = $user_data['full_name'] ?: $user_data['username'];
            send_notification_email($user_data['email'], $user_name, $title, $message, $type);
        }
    }

    return $notification_id;
}

/**
 * Send notification email
 */
function send_notification_email($to_email, $to_name, $subject, $message, $type = 'info')
{
    $from_email = 'eggvelascogmail@gmail.com';
    $from_name = 'SCC Syllabus Management System';

    // Get icon and color based on type
    $icon_map = [
        'success' => ['icon' => '✓', 'color' => '#16A34A', 'bg' => '#D1FAE5'],
        'danger' => ['icon' => '✗', 'color' => '#E11D48', 'bg' => '#FEE2E2'],
        'warning' => ['icon' => '⚠', 'color' => '#F59E0B', 'bg' => '#FEF3C7'],
        'info' => ['icon' => 'ℹ', 'color' => '#3B82F6', 'bg' => '#DBEAFE']
    ];

    $style = $icon_map[$type] ?? $icon_map['info'];

    // Build HTML email
    $html_message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>$subject</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Arial, sans-serif; background: #f9fafb;'>
        <table width='100%' cellpadding='0' cellspacing='0' style='background: #f9fafb; padding: 40px 20px;'>
            <tr>
                <td align='center'>
                    <table width='600' cellpadding='0' cellspacing='0' style='background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);'>
                        <!-- Header -->
                        <tr>
                            <td style='background: linear-gradient(135deg, #FF6F00, #FFA040); padding: 32px; text-align: center;'>
                                <h1 style='margin: 0; color: white; font-size: 28px; font-weight: 700;'>
                                    <span style='font-size: 32px;'>🔬</span> CSM Laboratory
                                </h1>
                                <p style='margin: 8px 0 0 0; color: rgba(255,255,255,0.9); font-size: 14px;'>Apparatus Borrowing System</p>
                            </td>
                        </tr>
                        
                        <!-- Content -->
                        <tr>
                            <td style='padding: 40px 32px;'>
                                <p style='margin: 0 0 24px 0; font-size: 16px; color: #374151;'>Dear <strong>" . htmlspecialchars($to_name) . "</strong>,</p>
                                
                                <!-- Notification Box -->
                                <table width='100%' cellpadding='0' cellspacing='0' style='background: {$style['bg']}; border-left: 4px solid {$style['color']}; border-radius: 8px; margin-bottom: 24px;'>
                                    <tr>
                                        <td style='padding: 20px;'>
                                            <div style='display: flex; align-items: start; gap: 12px;'>
                                                <span style='font-size: 24px; color: {$style['color']}; line-height: 1;'>{$style['icon']}</span>
                                                <div>
                                                    <h2 style='margin: 0 0 12px 0; color: {$style['color']}; font-size: 18px; font-weight: 700;'>$subject</h2>
                                                    <p style='margin: 0; color: #374151; font-size: 15px; line-height: 1.6;'>$message</p>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                                
                                <!-- Action Button -->
                                <table width='100%' cellpadding='0' cellspacing='0'>
                                    <tr>
                                        <td align='center' style='padding: 20px 0;'>
                                            <a href='" . $_SERVER['HTTP_HOST'] . "/user_notifications.php' 
                                               style='display: inline-block; background: linear-gradient(135deg, #FF6F00, #FFA040); color: white; text-decoration: none; padding: 14px 32px; border-radius: 10px; font-weight: 600; font-size: 15px; box-shadow: 0 4px 12px rgba(255, 111, 0, 0.3);'>
                                                View All Notifications
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                                
                                <p style='margin: 24px 0 0 0; font-size: 14px; color: #6b7280; line-height: 1.6;'>
                                    If you have any questions or concerns, please visit the laboratory administration office or contact your instructor.
                                </p>
                            </td>
                        </tr>
                        
                        <!-- Footer -->
                        <tr>
                            <td style='background: #f9fafb; padding: 24px 32px; text-align: center; border-top: 1px solid #e5e7eb;'>
                                <p style='margin: 0 0 8px 0; font-size: 13px; color: #6b7280;'>
                                    This is an automated message from CSM Laboratory System.
                                </p>
                                <p style='margin: 0; font-size: 13px; color: #9ca3af;'>
                                    Please do not reply to this email.
                                </p>
                                <p style='margin: 16px 0 0 0; font-size: 12px; color: #9ca3af;'>
                                    &copy; " . date('Y') . " College of Science and Mathematics. All rights reserved.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>
    ";

    // Email headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: $from_name <$from_email>" . "\r\n";
    $headers .= "Reply-To: $from_email" . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    // Send email
    $success = @mail($to_email, $subject, $html_message, $headers);

    // Log email attempt
    if (!$success) {
        error_log("Failed to send email to: $to_email - Subject: $subject");
    }

    return $success;
}

// ============================================
// 2. SPECIFIC NOTIFICATION FUNCTIONS
// ============================================

/**
 * Notify student: Request Approved
 */
function notify_request_approved($request_id)
{
    global $conn;

    $query = "SELECT br.*, u.user_id, u.email, u.full_name, u.username, a.name as apparatus_name
              FROM borrow_requests br
              JOIN users u ON br.student_id = u.user_id
              JOIN apparatus a ON br.apparatus_id = a.apparatus_id
              WHERE br.request_id = $request_id";

    $result = mysqli_query($conn, $query);
    if ($data = mysqli_fetch_assoc($result)) {
        $title = "Request Approved ✓";
        $message = "Your request #{$request_id} for {$data['apparatus_name']} (Qty: {$data['quantity']}) has been approved and reserved. Please proceed to the laboratory at your scheduled time.";

        create_notification_with_email(
            $data['user_id'],
            $title,
            $message,
            'success',
            $request_id,
            'borrow_request'
        );
    }
}

/**
 * Notify student: Request Rejected
 */
function notify_request_rejected($request_id)
{
    global $conn;

    $query = "SELECT br.*, u.user_id, u.email, u.full_name, u.username, a.name as apparatus_name
              FROM borrow_requests br
              JOIN users u ON br.student_id = u.user_id
              JOIN apparatus a ON br.apparatus_id = a.apparatus_id
              WHERE br.request_id = $request_id";

    $result = mysqli_query($conn, $query);
    if ($data = mysqli_fetch_assoc($result)) {
        $title = "Request Rejected ✗";
        $message = "Your request #{$request_id} for {$data['apparatus_name']} has been rejected. Please contact your instructor or the laboratory office for more information.";

        create_notification_with_email(
            $data['user_id'],
            $title,
            $message,
            'danger',
            $request_id,
            'borrow_request'
        );
    }
}

/**
 * Notify student: Ready for Pickup
 */
function notify_ready_for_pickup($request_id)
{
    global $conn;

    $query = "SELECT br.*, u.user_id, u.email, u.full_name, u.username, a.name as apparatus_name
              FROM borrow_requests br
              JOIN users u ON br.student_id = u.user_id
              JOIN apparatus a ON br.apparatus_id = a.apparatus_id
              WHERE br.request_id = $request_id";

    $result = mysqli_query($conn, $query);
    if ($data = mysqli_fetch_assoc($result)) {
        $title = "Apparatus Released - Ready for Pickup";
        $message = "Your {$data['apparatus_name']} (Qty: {$data['quantity']}) is now released and ready for pickup at the laboratory. Please claim within your scheduled time.";

        create_notification_with_email(
            $data['user_id'],
            $title,
            $message,
            'info',
            $request_id,
            'borrow_request'
        );
    }
}

/**
 * Notify student: Overdue Return
 */
function notify_overdue_return($request_id)
{
    global $conn;

    $query = "SELECT br.*, u.user_id, u.email, u.full_name, u.username, a.name as apparatus_name,
              DATEDIFF(CURDATE(), br.date_needed) as days_overdue
              FROM borrow_requests br
              JOIN users u ON br.student_id = u.user_id
              JOIN apparatus a ON br.apparatus_id = a.apparatus_id
              WHERE br.request_id = $request_id";

    $result = mysqli_query($conn, $query);
    if ($data = mysqli_fetch_assoc($result)) {
        $title = "⚠ Overdue Return Notice";
        $message = "Your borrowed {$data['apparatus_name']} is {$data['days_overdue']} day(s) overdue. Please return immediately to avoid additional penalties.";

        create_notification_with_email(
            $data['user_id'],
            $title,
            $message,
            'warning',
            $request_id,
            'borrow_request'
        );
    }
}

/**
 * Notify student: Returned Successfully
 */
function notify_return_confirmed($request_id)
{
    global $conn;

    $query = "SELECT br.*, u.user_id, u.email, u.full_name, u.username, a.name as apparatus_name
              FROM borrow_requests br
              JOIN users u ON br.student_id = u.user_id
              JOIN apparatus a ON br.apparatus_id = a.apparatus_id
              WHERE br.request_id = $request_id";

    $result = mysqli_query($conn, $query);
    if ($data = mysqli_fetch_assoc($result)) {
        $title = "Return Confirmed ✓";
        $message = "Your {$data['apparatus_name']} has been successfully returned. Thank you for using the CSM Laboratory services!";

        create_notification_with_email(
            $data['user_id'],
            $title,
            $message,
            'success',
            $request_id,
            'borrow_request'
        );
    }
}

// ============================================
// 3. ADMIN BULK EMAIL FUNCTIONS
// ============================================

/**
 * Send email to all students
 */
function send_email_to_all_students($subject, $message)
{
    global $conn;

    $students = mysqli_query($conn, "
        SELECT user_id, email, full_name, username 
        FROM users 
        WHERE role = 'student' AND email IS NOT NULL AND email != ''
    ");

    $sent_count = 0;
    while ($student = mysqli_fetch_assoc($students)) {
        $name = $student['full_name'] ?: $student['username'];
        if (send_notification_email($student['email'], $name, $subject, $message, 'info')) {
            create_notification($student['user_id'], $subject, $message, 'info');
            $sent_count++;
        }
    }

    return $sent_count;
}

/**
 * Send email to specific user
 */
function send_email_to_user($user_id, $subject, $message, $type = 'info')
{
    global $conn;

    $user_query = mysqli_query($conn, "
        SELECT email, full_name, username 
        FROM users 
        WHERE user_id = $user_id AND email IS NOT NULL AND email != ''
    ");

    if ($user = mysqli_fetch_assoc($user_query)) {
        $name = $user['full_name'] ?: $user['username'];
        if (send_notification_email($user['email'], $name, $subject, $message, $type)) {
            create_notification($user_id, $subject, $message, $type);
            return true;
        }
    }

    return false;
}

/**
 * Send email to users with overdue items
 */
function send_overdue_reminders()
{
    global $conn;

    $overdue = mysqli_query($conn, "
        SELECT br.request_id, br.student_id, br.date_needed,
               u.email, u.full_name, u.username,
               a.name as apparatus_name,
               DATEDIFF(CURDATE(), br.date_needed) as days_overdue
        FROM borrow_requests br
        JOIN users u ON br.student_id = u.user_id
        JOIN apparatus a ON br.apparatus_id = a.apparatus_id
        WHERE br.status IN ('approved', 'released')
        AND br.date_needed < CURDATE()
        AND u.email IS NOT NULL AND u.email != ''
    ");

    $sent_count = 0;
    while ($item = mysqli_fetch_assoc($overdue)) {
        notify_overdue_return($item['request_id']);
        $sent_count++;
    }

    return $sent_count;
}

// ============================================
// 4. NOTIFICATION FETCHING FUNCTIONS
// ============================================

/**
 * Get latest notifications for dropdown (20 items)
 */
function get_dropdown_notifications($user_id, $limit = 20)
{
    global $conn;

    $user_id = intval($user_id);
    $limit = intval($limit);

    $query = "SELECT * FROM user_notifications
              WHERE user_id = $user_id
              ORDER BY created_at DESC
              LIMIT $limit";

    $result = mysqli_query($conn, $query);
    $notifications = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }

    return $notifications;
}

/**
 * Get unread count
 */
function get_unread_count($user_id)
{
    global $conn;

    $user_id = intval($user_id);
    $query = "SELECT COUNT(*) as count FROM user_notifications 
              WHERE user_id = $user_id AND is_read = 0";

    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);

    return $row['count'];
}

/**
 * Mark notification as read
 */
function mark_as_read($notification_id, $user_id)
{
    global $conn;

    $notification_id = intval($notification_id);
    $user_id = intval($user_id);

    $query = "UPDATE user_notifications 
              SET is_read = 1 
              WHERE notification_id = $notification_id AND user_id = $user_id";

    return mysqli_query($conn, $query);
}

/**
 * Mark all as read
 */
function mark_all_as_read($user_id)
{
    global $conn;

    $user_id = intval($user_id);

    $query = "UPDATE user_notifications 
              SET is_read = 1 
              WHERE user_id = $user_id AND is_read = 0";

    return mysqli_query($conn, $query);
}

// ============================================
// 5. HELPER FUNCTIONS
// ============================================

/**
 * Format time ago (e.g., "2 hours ago")
 */
function time_ago($datetime)
{
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60)
        return 'Just now';
    if ($diff < 3600)
        return floor($diff / 60) . ' min ago';
    if ($diff < 86400)
        return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800)
        return floor($diff / 86400) . ' days ago';

    return date('M d, Y', $timestamp);
}

/**
 * Truncate text
 */
function truncate_text($text, $length = 100)
{
    if (strlen($text) <= $length)
        return $text;
    return substr($text, 0, $length) . '...';
}

/**
 * Get unread notification count (alias for header.php)
 */
function get_unread_notification_count($user_id)
{
    return get_unread_count($user_id);
}

/**
 * Get user notifications for header dropdown
 */
function get_user_notifications($user_id, $limit = 10)
{
    global $conn;

    $user_id = intval($user_id);
    $limit = intval($limit);

    $query = "SELECT notification_id, title, message, type, is_read, created_at, related_id, related_type
              FROM user_notifications
              WHERE user_id = $user_id
              ORDER BY created_at DESC
              LIMIT $limit";

    $result = mysqli_query($conn, $query);
    $notifications = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $notifications[] = $row;
        }
    }

    return $notifications;
}

