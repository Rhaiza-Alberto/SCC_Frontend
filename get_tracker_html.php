<?php
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

if (!isset($_SESSION['user_id'])) {
    echo '<div class="alert alert-danger">Unauthorized access.</div>';
    exit();
}

$syllabus_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$syllabus_id) {
    echo '<div class="alert alert-danger">Invalid syllabus ID.</div>';
    exit();
}

$conn = get_db();

// Fetch syllabus details
$stmt = $conn->prepare("
    SELECT s.*, u.first_name, u.last_name, r.role_name as uploader_role
    FROM syllabus s
    JOIN users u ON s.uploaded_by = u.id
    JOIN roles r ON u.role_id = r.id
    WHERE s.id = ?
");
$stmt->execute([$syllabus_id]);
$syllabus = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$syllabus) {
    echo '<div class="alert alert-danger">Syllabus not found.</div>';
    exit();
}

// Fetch workflow history
$stmt = $conn->prepare("
    SELECT sw.*, r.role_name 
    FROM syllabus_workflow sw
    JOIN roles r ON sw.role_id = r.id
    WHERE sw.syllabus_id = ?
    ORDER BY sw.action_at ASC
");
$stmt->execute([$syllabus_id]);
$workflows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map workflow to stages
// Stages:
// 1. Submitted by Faculty
// 2. Pending Dean Review
// 3. Approved by Dean
// 4. Pending VPAA Review
// 5. Approved by VPAA
// 6. Archived / Stored in Vault

$stages = [
    1 => ['label' => 'Submitted by Faculty', 'icon' => 'bi-cloud-arrow-up', 'status' => 'completed', 'time' => $syllabus['submitted_at'], 'comment' => ''],
    2 => ['label' => 'Pending Dean Review', 'icon' => 'bi-hourglass-split', 'status' => 'pending', 'time' => null, 'comment' => ''],
    3 => ['label' => 'Approved by Dean', 'icon' => 'bi-check2-circle', 'status' => 'upcoming', 'time' => null, 'comment' => ''],
    4 => ['label' => 'Pending VPAA Review', 'icon' => 'bi-hourglass-split', 'status' => 'upcoming', 'time' => null, 'comment' => ''],
    5 => ['label' => 'Approved by VPAA', 'icon' => 'bi-shield-check', 'status' => 'upcoming', 'time' => null, 'comment' => ''],
    6 => ['label' => 'Archived in Vault', 'icon' => 'bi-archive', 'status' => 'upcoming', 'time' => null, 'comment' => ''],
];

$is_rejected = false;
$reject_stage = 0;

$is_dean_uploader = in_array($syllabus['uploader_role'], ['dean', 'admin']);

if ($is_dean_uploader) {
    $stages[1]['label'] = 'Submitted by Dean';
    $stages[2]['status'] = 'completed';
    $stages[2]['label'] = 'Dean Review (Bypassed)';
    $stages[2]['time'] = $syllabus['submitted_at'];
    $stages[3]['status'] = 'completed';
    $stages[3]['label'] = 'Auto-Approved (Uploader)';
    $stages[3]['time'] = $syllabus['submitted_at'];
    $stages[4]['status'] = 'current';
}

foreach ($workflows as $wf) {
    if ($wf['role_name'] === 'dean') {
        if ($wf['action'] === 'Pending') {
            $stages[2]['status'] = 'current';
            $stages[2]['time'] = $wf['action_at'];
        } elseif ($wf['action'] === 'Approved') {
            $stages[2]['status'] = 'completed';
            $stages[3]['status'] = 'completed';
            $stages[3]['time'] = $wf['action_at'];
            $stages[4]['status'] = 'current'; // Move to VPAA Pending
        } elseif ($wf['action'] === 'Rejected') {
            $stages[2]['status'] = 'completed';
            $stages[3]['label'] = 'Returned by Dean';
            $stages[3]['icon'] = 'bi-x-circle';
            $stages[3]['status'] = 'rejected';
            $stages[3]['time'] = $wf['action_at'];
            $stages[3]['comment'] = $wf['comment'];
            $is_rejected = true;
            $reject_stage = 3;
            break;
        }
    } elseif ($wf['role_name'] === 'vpaa') {
        if ($wf['action'] === 'Pending') {
            $stages[4]['status'] = 'current';
            $stages[4]['time'] = $wf['action_at'];
        } elseif ($wf['action'] === 'Approved') {
            $stages[4]['status'] = 'completed';
            $stages[5]['status'] = 'completed';
            $stages[5]['time'] = $wf['action_at'];
            $stages[6]['status'] = 'completed';
            $stages[6]['time'] = $wf['action_at'];
        } elseif ($wf['action'] === 'Rejected') {
            $stages[4]['status'] = 'completed';
            $stages[5]['label'] = 'Returned by VPAA';
            $stages[5]['icon'] = 'bi-x-circle';
            $stages[5]['status'] = 'rejected';
            $stages[5]['time'] = $wf['action_at'];
            $stages[5]['comment'] = $wf['comment'];
            $is_rejected = true;
            $reject_stage = 5;
            break;
        }
    }
}

// Adjust status based on current state if not explicitly matched
if (!$is_rejected) {
    if ($syllabus['status'] === 'Approved') {
        $stages[2]['status'] = 'completed';
        $stages[3]['status'] = 'completed';
        $stages[4]['status'] = 'completed';
        $stages[5]['status'] = 'completed';
        $stages[6]['status'] = 'completed';
    }
}

// Generate HTML
?>
<style>
.tracker-container {
    padding: 2rem 0;
    overflow-x: auto;
}
.tracker-timeline {
    display: flex;
    justify-content: space-between;
    position: relative;
    min-width: 800px; /* Ensure horizontal scroll on small devices */
    margin-bottom: 2rem;
}
.tracker-timeline::before {
    content: '';
    position: absolute;
    top: 24px;
    left: 40px;
    right: 40px;
    height: 4px;
    background: var(--border);
    z-index: 1;
    border-radius: 2px;
}
.tracker-step {
    position: relative;
    z-index: 2;
    text-align: center;
    width: 16.66%;
    padding: 0 10px;
}
.tracker-icon {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: var(--bg-card);
    border: 4px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px auto;
    font-size: 1.25rem;
    color: var(--text-muted);
    transition: all 0.3s ease;
}
.tracker-step.completed .tracker-icon {
    background: var(--primary);
    border-color: rgba(var(--primary-rgb), 0.2);
    color: white;
    box-shadow: 0 0 0 4px rgba(var(--primary-rgb), 0.1);
}
.tracker-step.current .tracker-icon {
    background: white;
    border-color: var(--warning);
    color: var(--warning);
    box-shadow: 0 0 0 6px rgba(var(--warning-rgb), 0.15);
    animation: pulse-ring 2s infinite;
}
.tracker-step.rejected .tracker-icon {
    background: var(--danger);
    border-color: rgba(var(--danger-rgb), 0.2);
    color: white;
    box-shadow: 0 0 0 4px rgba(var(--danger-rgb), 0.1);
}
.tracker-label {
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--text-muted);
    margin-bottom: 4px;
    line-height: 1.2;
}
.tracker-step.completed .tracker-label { color: var(--text); }
.tracker-step.current .tracker-label { color: var(--warning); }
.tracker-step.rejected .tracker-label { color: var(--danger); }
.tracker-time {
    font-size: 0.65rem;
    color: var(--text-secondary);
}

/* Connectors */
.tracker-connector {
    position: absolute;
    top: 24px;
    left: calc(50% + 26px);
    width: calc(100% - 52px);
    height: 4px;
    background: var(--primary);
    z-index: 2;
    transform-origin: left;
    transition: width 0.5s ease;
}
.tracker-step.rejected .tracker-connector { background: var(--danger); }

@keyframes pulse-ring {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(var(--warning-rgb), 0.4); }
    70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(var(--warning-rgb), 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(var(--warning-rgb), 0); }
}

[data-theme="dark"] .tracker-step.current .tracker-icon {
    background: var(--bg-card);
}
</style>

<div class="tracker-container">
    <div class="tracker-timeline">
        <?php foreach ($stages as $i => $stage): ?>
            <div class="tracker-step <?= $stage['status'] ?>" <?= ($is_rejected && $i > $reject_stage) ? 'style="opacity:0.3"' : '' ?>>
                <div class="tracker-icon">
                    <i class="bi <?= $stage['icon'] ?>"></i>
                </div>
                <div class="tracker-label"><?= htmlspecialchars($stage['label']) ?></div>
                <div class="tracker-time">
                    <?= $stage['time'] ? date('M d, Y h:i A', strtotime($stage['time'])) : '—' ?>
                </div>
                <?php if ($stage['status'] === 'rejected' && $stage['comment']): ?>
                    <div class="mt-2 text-danger small fst-italic" style="font-size: 0.7rem; max-width: 120px; margin: 0 auto; line-height: 1.2;">
                        "<?= htmlspecialchars($stage['comment']) ?>"
                    </div>
                <?php endif; ?>
                
                <?php if ($i < 6 && $stage['status'] === 'completed'): ?>
                    <!-- Highlighted connector to next step if this step is completed -->
                    <div class="tracker-connector" style="width: 100%;"></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
