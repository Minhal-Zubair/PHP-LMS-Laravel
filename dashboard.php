<?php
require_once __DIR__ . '/auth.php';   // already redirects to login.php if not authenticated
require_once __DIR__ . '/helpers.php';

// Get User Info from Session
$user_id = (int) $_SESSION['user_id'];
$fullname = $_SESSION['fullname'];

function normalize_schedule_time($value)
{
    if (empty($value)) {
        return null;
    }

    $value = trim($value);
    if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $value, $m)) {
        return sprintf('%02d:%02d:00', (int)$m[1], (int)$m[2]);
    }

    $ts = strtotime($value);
    return $ts ? date('H:i:s', $ts) : null;
}

function format_schedule_time($value)
{
    $normalized = normalize_schedule_time($value);
    if (!$normalized) {
        return '';
    }

    $ts = strtotime('2000-01-01 ' . $normalized);
    return $ts ? date('h:i A', $ts) : '';
}

// Determine which page/tab to display (whitelist to avoid surprises)
$allowed_pages = ['dashboard', 'courses', 'schedule', 'calendar'];
$page = isset($_GET['page']) && in_array($_GET['page'], $allowed_pages, true) ? $_GET['page'] : 'dashboard';

// ---------------------------------------------------------------------
// ADD TASK
// ---------------------------------------------------------------------
/**
 * Validates and normalizes task fields submitted from either the Add Task
 * or Edit Task forms. Returns a clean array ready for binding — shared so
 * both handlers apply exactly the same rules (course ownership check,
 * date format, priority whitelist).
 */
function parse_task_input(mysqli $conn, int $userId, array $post): array
{
    $task_course_id = !empty($post['course_id']) ? (int) $post['course_id'] : null;
    $task_due_date = !empty($post['due_date']) ? $post['due_date'] : null;
    $task_due_time = !empty($post['due_time']) ? $post['due_time'] : null;
    $task_priority = in_array($post['priority'] ?? '', ['low', 'medium', 'high'], true) ? $post['priority'] : 'medium';
    $task_hours = (isset($post['estimated_hours']) && $post['estimated_hours'] !== '')
        ? round((float) $post['estimated_hours'], 2) : null;

    if ($task_due_date !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $task_due_date)) {
        $task_due_date = null;
    }
    // A time only makes sense alongside a date.
    if ($task_due_date === null) {
        $task_due_time = null;
    }
    if ($task_due_time !== null && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $task_due_time)) {
        $task_due_time = null;
    }

    // Confirm the selected course actually belongs to this user.
    if ($task_course_id !== null) {
        $check = $conn->prepare("SELECT id FROM courses WHERE id = ? AND user_id = ?");
        $check->bind_param('ii', $task_course_id, $userId);
        $check->execute();
        if (!$check->get_result()->fetch_assoc()) {
            $task_course_id = null;
        }
        $check->close();
    }

    return [
        'course_id' => $task_course_id,
        'due_date' => $task_due_date,
        'due_time' => $task_due_time,
        'priority' => $task_priority,
        'estimated_hours' => $task_hours,
    ];
}

if (isset($_POST['add_task'])) {
    csrf_verify();
    $task_name = trim($_POST['task_name'] ?? '');
    $fields = parse_task_input($conn, $user_id, $_POST);

    if ($task_name !== '') {
        $stmt = $conn->prepare(
            "INSERT INTO tasks (user_id, task_name, status, course_id, due_date, due_time, priority, estimated_hours)
             VALUES (?, ?, 'Pending', ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'isisssd',
            $user_id,
            $task_name,
            $fields['course_id'],
            $fields['due_date'],
            $fields['due_time'],
            $fields['priority'],
            $fields['estimated_hours']
        );
        $stmt->execute();
        $stmt->close();

        if ($fields['course_id'] !== null) {
            recalc_course_progress($conn, $fields['course_id']);
        }
    }
    header("Location: dashboard.php?page=dashboard");
    exit();
}

// EDIT TASK
if (isset($_POST['update_task'])) {
    csrf_verify();
    $edit_task_id = (int) ($_POST['task_id'] ?? 0);
    $task_name = trim($_POST['task_name'] ?? '');

    // Confirm ownership and grab the OLD course_id first — if the course
    // link changes, both the old and new course's progress need
    // recalculating (the old one because it just lost a task, the new one
    // because it just gained one).
    $lookup = $conn->prepare("SELECT course_id FROM tasks WHERE id = ? AND user_id = ?");
    $lookup->bind_param('ii', $edit_task_id, $user_id);
    $lookup->execute();
    $existing = $lookup->get_result()->fetch_assoc();
    $lookup->close();

    if ($existing && $task_name !== '') {
        $fields = parse_task_input($conn, $user_id, $_POST);
        $old_course_id = $existing['course_id'] !== null ? (int) $existing['course_id'] : null;

        $stmt = $conn->prepare(
            "UPDATE tasks SET task_name = ?, course_id = ?, due_date = ?, due_time = ?, priority = ?, estimated_hours = ?
             WHERE id = ? AND user_id = ?"
        );
        $stmt->bind_param(
            'sisssdii',
            $task_name,
            $fields['course_id'],
            $fields['due_date'],
            $fields['due_time'],
            $fields['priority'],
            $fields['estimated_hours'],
            $edit_task_id,
            $user_id
        );
        $stmt->execute();
        $stmt->close();

        if ($old_course_id !== null) {
            recalc_course_progress($conn, $old_course_id);
        }
        if ($fields['course_id'] !== null && $fields['course_id'] !== $old_course_id) {
            recalc_course_progress($conn, $fields['course_id']);
        }
    }

    // Return to wherever the edit was opened from (calendar or dashboard).
    $return_page = in_array($_POST['return_page'] ?? '', ['dashboard', 'calendar'], true) ? $_POST['return_page'] : 'dashboard';
    header("Location: dashboard.php?page={$return_page}");
    exit();
}

// TOGGLE TASK STATUS
if (isset($_GET['toggle_id'])) {
    $tid = (int) $_GET['toggle_id'];
    $stmt = $conn->prepare("SELECT status, course_id FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $tid, $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($res) {
        $new_status = ($res['status'] == 'Pending') ? 'Completed' : 'Pending';
        if ($new_status === 'Completed') {
            $stmt = $conn->prepare("UPDATE tasks SET status = ?, completed_at = NOW() WHERE id = ? AND user_id = ?");
        } else {
            $stmt = $conn->prepare("UPDATE tasks SET status = ?, completed_at = NULL WHERE id = ? AND user_id = ?");
        }
        $stmt->bind_param('sii', $new_status, $tid, $user_id);
        $stmt->execute();
        $stmt->close();

        if ($res['course_id'] !== null) {
            recalc_course_progress($conn, (int) $res['course_id']);
        }
    }
    header("Location: dashboard.php?page=dashboard");
    exit();
}

// DELETE TASK
if (isset($_GET['delete_id'])) {
    $did = (int) $_GET['delete_id'];

    $lookup = $conn->prepare("SELECT course_id FROM tasks WHERE id = ? AND user_id = ?");
    $lookup->bind_param('ii', $did, $user_id);
    $lookup->execute();
    $deleted_task_course_id = $lookup->get_result()->fetch_assoc()['course_id'] ?? null;
    $lookup->close();

    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $did, $user_id);
    $stmt->execute();
    $stmt->close();

    if ($deleted_task_course_id !== null) {
        recalc_course_progress($conn, (int) $deleted_task_course_id);
    }

    header("Location: dashboard.php?page=dashboard");
    exit();
}

$stmt = $conn->prepare(
    "SELECT t.*, c.course_name
     FROM tasks t
     LEFT JOIN courses c ON c.id = t.course_id
     WHERE t.user_id = ?
     ORDER BY t.status DESC, t.due_date IS NULL, t.due_date ASC, t.created_at DESC"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$tasks_query = $stmt->get_result();
$stmt->close();

// Get Statistics for the Progress Bar
$stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(status='Completed') as done FROM tasks WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
$percent = ($stats['total'] > 0) ? round(($stats['done'] / $stats['total']) * 100) : 0;

// ---------------------------------------------------------------------
// NOTIFICATIONS & REMINDERS
// Runs on every page load (the bell icon lives in the shared header, not
// just the dashboard tab). "Due" here means: the date has passed, or the
// date is today and either no specific time was set or that time has
// already passed. This is checked fresh on every request — there's no
// background process or push notification, so a reminder becomes visible
// the next time any page is loaded or refreshed at or after its due
// moment, not the instant it arrives while you're away.
// ---------------------------------------------------------------------
$stmt = $conn->prepare(
    "SELECT t.id, t.task_name, t.due_date, t.due_time, t.priority, c.course_name
     FROM tasks t
     LEFT JOIN courses c ON c.id = t.course_id
     WHERE t.user_id = ? AND t.status = 'Pending' AND t.due_date IS NOT NULL
     AND (t.due_date < CURDATE() OR (t.due_date = CURDATE() AND (t.due_time IS NULL OR t.due_time <= CURTIME())))
     ORDER BY t.due_date ASC, t.due_time ASC
     LIMIT 20"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$reminders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ---------------------------------------------------------------------
// STUDENT INSIGHTS (dashboard tab only — skip the extra queries on other
// tabs where none of this is shown)
//
// Every number here is either a direct count/sum from the DB or a simple,
// documented weighted average — nothing here is "AI" or a black box.
// ---------------------------------------------------------------------
$insights = null;
if ($page === 'dashboard') {
    // Average course progress (0 if no courses yet)
    $stmt = $conn->prepare("SELECT AVG(progress) AS avg_progress, COUNT(*) AS course_count FROM courses WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $course_stats = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $avg_course_progress = $course_stats['avg_progress'] !== null ? (float) $course_stats['avg_progress'] : 0;

    // Task completion rate — all-time completed / all-time total (same
    // numbers already shown in the progress bar above, reused here).
    $task_completion_rate = $percent;

    // Academic Health = 50% average course progress + 50% task completion
    // rate. Simple, explainable, and both halves are numbers already
    // computed elsewhere on this page — not a hidden formula.
    $academic_health = ($course_stats['course_count'] > 0)
        ? round(($avg_course_progress * 0.5) + ($task_completion_rate * 0.5))
        : $task_completion_rate; // no courses yet: fall back to task completion alone

    // At-risk courses: progress under 40%, OR 2+ pending tasks due within
    // the next 3 days that are linked to that course.
    $stmt = $conn->prepare(
        "SELECT c.id, c.course_name, c.progress,
                COUNT(t.id) AS urgent_task_count
         FROM courses c
         LEFT JOIN tasks t ON t.course_id = c.id
                AND t.status = 'Pending'
                AND t.due_date IS NOT NULL
                AND t.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
         WHERE c.user_id = ?
         GROUP BY c.id, c.course_name, c.progress
         HAVING c.progress < 40 OR urgent_task_count >= 2
         ORDER BY c.progress ASC
         LIMIT 5"
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $at_risk_courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Tasks due in the next 7 days (pending only)
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS due_count FROM tasks
         WHERE user_id = ? AND status = 'Pending' AND due_date IS NOT NULL
         AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)"
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $tasks_due_week = (int) $stmt->get_result()->fetch_assoc()['due_count'];
    $stmt->close();

    // Weekly workload: sum of estimated_hours for pending tasks due in the
    // next 7 days. Only counts tasks where hours were actually entered —
    // we don't invent a number for tasks with no estimate.
    $stmt = $conn->prepare(
        "SELECT SUM(estimated_hours) AS total_hours,
                SUM(CASE WHEN estimated_hours IS NULL THEN 1 ELSE 0 END) AS missing_estimate_count
         FROM tasks
         WHERE user_id = ? AND status = 'Pending' AND due_date IS NOT NULL
         AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)"
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $workload_row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $weekly_workload_hours = $workload_row['total_hours'] !== null ? (float) $workload_row['total_hours'] : 0;
    $weekly_workload_missing = (int) ($workload_row['missing_estimate_count'] ?? 0);

    // Today's Priority: up to 3 pending tasks due today/tomorrow, highest
    // priority first, soonest due date first. Includes course context
    // (name + progress) when the task is linked to a course.
    $stmt = $conn->prepare(
        "SELECT t.id, t.task_name, t.due_date, t.priority, c.course_name, c.progress AS course_progress
         FROM tasks t
         LEFT JOIN courses c ON c.id = t.course_id
         WHERE t.user_id = ? AND t.status = 'Pending' AND t.due_date IS NOT NULL
         AND t.due_date <= DATE_ADD(CURDATE(), INTERVAL 1 DAY)
         ORDER BY FIELD(t.priority, 'high', 'medium', 'low'), t.due_date ASC
         LIMIT 3"
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $priority_tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // User's own courses, for the "add task" form's course dropdown.
    $stmt = $conn->prepare("SELECT id, course_name FROM courses WHERE user_id = ? ORDER BY course_name ASC");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user_courses_for_dropdown = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $insights = [
        'academic_health' => $academic_health,
        'at_risk_courses' => $at_risk_courses,
        'tasks_due_week' => $tasks_due_week,
        'weekly_workload_hours' => $weekly_workload_hours,
        'weekly_workload_missing' => $weekly_workload_missing,
        'priority_tasks' => $priority_tasks,
    ];
}

// ---------------------------------------------------------------------
// ADD COURSE
// ---------------------------------------------------------------------
if (isset($_POST['add_course'])) {
    csrf_verify();
    $c_name = trim($_POST['course_name'] ?? '');
    $inst = trim($_POST['instructor'] ?? '');
    $cat = trim($_POST['category'] ?? '');
    $link = trim($_POST['course_link'] ?? '');

    if ($c_name !== '') {
        $stmt = $conn->prepare(
            "INSERT INTO courses (user_id, course_name, instructor, category, course_link) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('issss', $user_id, $c_name, $inst, $cat, $link);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: dashboard.php?page=courses");
    exit();
}

// ---------------------------------------------------------------------
// MANUAL COURSE PROGRESS UPDATE
// Only allowed for courses with zero linked tasks — once a course has
// tasks linked to it, progress is auto-calculated from task completion
// (see recalc_course_progress()) and manual edits would just get
// overwritten the next time a linked task changes, which would be
// confusing. This keeps there being exactly one source of truth at a time.
// ---------------------------------------------------------------------
if (isset($_POST['update_progress'])) {
    csrf_verify();
    $progress_course_id = (int) ($_POST['course_id'] ?? 0);
    $new_progress = max(0, min(100, (int) ($_POST['progress'] ?? 0)));

    // Ownership check + reject if this course actually has linked tasks
    // (guards against someone POSTing directly to bypass the auto-calc).
    $stmt = $conn->prepare("SELECT id FROM courses WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $progress_course_id, $user_id);
    $stmt->execute();
    $owns_course = (bool) $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($owns_course && !course_has_linked_tasks($conn, $progress_course_id)) {
        $stmt = $conn->prepare("UPDATE courses SET progress = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param('iii', $new_progress, $progress_course_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: dashboard.php?page=courses");
    exit();
}

// ---------------------------------------------------------------------
// ADD / UPDATE SCHEDULE
// ---------------------------------------------------------------------
if (isset($_POST['add_schedule']) || isset($_POST['do_update_schedule'])) {
    csrf_verify();
    $day = trim($_POST['day'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $start = trim($_POST['start_time'] ?? '');
    $end = trim($_POST['end_time'] ?? '');
    $room = trim($_POST['room'] ?? '');

    $allowed_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    if (!in_array($day, $allowed_days, true) || $subject === '' || $start === '' || $end === '') {
        echo "<script>alert('Please fill in all schedule fields correctly.'); window.history.back();</script>";
        exit();
    }

    if (strtotime($start) !== false && strtotime($end) !== false && strtotime($start) < strtotime($end)) {
        $start_sql = date('H:i:s', strtotime($start));
        $end_sql = date('H:i:s', strtotime($end));

        if (isset($_POST['do_update_schedule'])) {
            $id = (int) $_POST['update_id'];
            $stmt = $conn->prepare(
                "UPDATE schedule SET day=?, subject=?, start_time=?, end_time=?, room=? WHERE id=? AND user_id=?"
            );
            $stmt->bind_param('sssssii', $day, $subject, $start_sql, $end_sql, $room, $id, $user_id);
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO schedule (user_id, day, subject, start_time, end_time, room, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->bind_param('isssss', $user_id, $day, $subject, $start_sql, $end_sql, $room);
        }

        $stmt->execute();
        $stmt->close();
        header("Location: dashboard.php?page=schedule");
        exit();
    } else {
        echo "<script>alert('Error: Start time must be before end time'); window.history.back();</script>";
        exit();
    }
}

// DELETE SCHEDULE — ownership check restored (this was previously removed,
// letting any logged-in user delete anyone else's schedule entries)
if (isset($_GET['del_sch_id'])) {
    $sid = (int) $_GET['del_sch_id'];
    $stmt = $conn->prepare("DELETE FROM schedule WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $sid, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: dashboard.php?page=schedule");
    exit();
}

// EDIT SCHEDULE — also scoped to the current user, same reasoning as delete above.
$edit_data = null;
$edit_start = '';
$edit_end = '';
if (isset($_GET['edit_sch_id'])) {
    $edit_id = (int) $_GET['edit_sch_id'];
    $stmt = $conn->prepare("SELECT * FROM schedule WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $edit_id, $user_id);
    $stmt->execute();
    $edit_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($edit_data) {
        $edit_start = !empty($edit_data['start_time']) ? date('h:i A', strtotime($edit_data['start_time'])) : '';
        $edit_end = !empty($edit_data['end_time']) ? date('h:i A', strtotime($edit_data['end_time'])) : '';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduPro | <?php echo ucfirst($page); ?></title>
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/dashboard.css">
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand"><i class="fas fa-graduation-cap"></i> <span>EduPro</span></div>
        <nav class="nav-menu" style="flex-grow: 1;">
            <a href="dashboard.php?page=dashboard" class="nav-link <?php echo ($page == 'dashboard') ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> <span>Dashboard</span>
            </a>
            <a href="dashboard.php?page=courses" class="nav-link <?php echo ($page == 'courses') ? 'active' : ''; ?>">
                <i class="fas fa-book"></i> <span>My Courses</span>
            </a>
            <a href="dashboard.php?page=schedule" class="nav-link <?php echo ($page == 'schedule') ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i> <span>Schedule</span>
            </a>
            <a href="dashboard.php?page=calendar" class="nav-link <?php echo ($page == 'calendar') ? 'active' : ''; ?>">
                <i class="fas fa-calendar-days"></i> <span>Calendar</span>
            </a>
            <a href="settings-app/public/index.php/settings" class="nav-link"><i class="fas fa-cog"></i> <span>Settings</span></a>
        </nav>
        <a href="logout.php" class="nav-link logout-link">
            <i class="fas fa-sign-out-alt"></i> <span>Sign Out</span>
        </a>
    </div>

    <!-- Main Content -->
    <div class="main-content">

        <!-- Top Bar -->
        <div class="top-header">
            <div>
                <h1 style="font-size: 24px;">Welcome, <?php echo e(explode(' ', $fullname)[0]); ?>!</h1>
                <p style="color: var(--text-muted); font-size: 14px; color: black;"><?php echo date('l, d F Y'); ?></p>
            </div>
            <div style="display: flex; align-items: center; gap: 16px;">
                <details class="notif-dropdown">
                    <summary class="notif-bell">
                        <i class="fas fa-bell"></i>
                        <?php if (count($reminders) > 0): ?>
                            <span class="notif-badge"><?php echo count($reminders) > 9 ? '9+' : count($reminders); ?></span>
                        <?php endif; ?>
                    </summary>
                    <div class="notif-panel">
                        <div class="notif-panel-title">Notifications &amp; Reminders</div>
                        <?php if (empty($reminders)): ?>
                            <p class="notif-empty">Nothing due right now — you're all caught up.</p>
                        <?php else: ?>
                            <?php foreach ($reminders as $r): ?>
                                <?php
                                $is_overdue = $r['due_date'] < date('Y-m-d');
                                $time_label = $r['due_time'] ? date('g:i A', strtotime($r['due_time'])) : '';
                                ?>
                                <div class="notif-item">
                                    <span class="priority-dot priority-<?php echo e($r['priority']); ?>"></span>
                                    <div style="flex: 1;">
                                        <div class="notif-item-title"><?php echo e($r['task_name']); ?></div>
                                        <div class="notif-item-meta">
                                            <?php if ($is_overdue): ?>
                                                <span style="color: #e74a3b; font-weight: 600;">Overdue</span> &middot; <?php echo date('M j', strtotime($r['due_date'])); ?>
                                            <?php else: ?>
                                                Due today<?php echo $time_label ? " at $time_label" : ''; ?>
                                            <?php endif; ?>
                                            <?php if ($r['course_name']): ?>
                                                &middot; <?php echo e($r['course_name']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <a href="dashboard.php?toggle_id=<?php echo (int) $r['id']; ?>" class="notif-item-action" title="Mark complete"><i class="fas fa-check"></i></a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </details>
                <div class="user-badge">
                    <i class="fas fa-user-graduate"></i> Student Account
                </div>
            </div>
        </div>

        <?php if ($page == 'dashboard'): ?>
            <!-- DASHBOARD TAB CONTENT — Student Insights -->

            <div class="insights-grid">
                <div class="insight-card">
                    <div class="insight-label">Academic Health</div>
                    <div class="insight-value"><?php echo $insights['academic_health']; ?><span style="font-size:16px; color:var(--text-muted);"> / 100</span></div>
                    <div class="insight-sub <?php echo $insights['academic_health'] >= 70 ? 'good' : ($insights['academic_health'] >= 40 ? 'warn' : 'bad'); ?>">
                        <?php if ($insights['academic_health'] >= 70): ?>
                            <i class="fas fa-circle-check"></i> On Track
                        <?php elseif ($insights['academic_health'] >= 40): ?>
                            <i class="fas fa-triangle-exclamation"></i> Needs Attention
                        <?php else: ?>
                            <i class="fas fa-circle-exclamation"></i> At Risk
                        <?php endif; ?>
                    </div>
                </div>
                <div class="insight-card">
                    <div class="insight-label">Courses at Risk</div>
                    <div class="insight-value"><?php echo count($insights['at_risk_courses']); ?></div>
                    <div class="insight-sub" style="color: var(--text-muted);">out of <?php echo $course_stats['course_count']; ?> total</div>
                </div>
                <div class="insight-card">
                    <div class="insight-label">Tasks Due (7 days)</div>
                    <div class="insight-value"><?php echo $insights['tasks_due_week']; ?></div>
                    <div class="insight-sub" style="color: var(--text-muted);">pending, with a due date set</div>
                </div>
                <div class="insight-card">
                    <div class="insight-label">Weekly Workload</div>
                    <div class="insight-value"><?php echo rtrim(rtrim(number_format($insights['weekly_workload_hours'], 1), '0'), '.'); ?><span style="font-size:16px; color:var(--text-muted);"> hrs</span></div>
                    <div class="insight-sub" style="color: var(--text-muted);">
                        <?php if ($insights['weekly_workload_missing'] > 0): ?>
                            + <?php echo $insights['weekly_workload_missing']; ?> task<?php echo $insights['weekly_workload_missing'] > 1 ? 's' : ''; ?> with no estimate
                        <?php else: ?>
                            based on estimated hours
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($insights['at_risk_courses'])): ?>
                <div class="card" style="margin-bottom: 20px; border-left: 4px solid #f6c23e;">
                    <h4 style="margin-bottom: 12px;"><i class="fas fa-triangle-exclamation" style="color: #f6c23e;"></i> Courses Needing Attention</h4>
                    <?php foreach ($insights['at_risk_courses'] as $rc): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                            <div>
                                <strong style="font-size: 14px;"><?php echo e($rc['course_name']); ?></strong>
                                <div style="font-size: 12px; color: var(--text-muted);">
                                    <?php echo (int) $rc['progress']; ?>% progress
                                    <?php if ($rc['urgent_task_count'] > 0): ?>
                                        &middot; <?php echo (int) $rc['urgent_task_count']; ?> task<?php echo $rc['urgent_task_count'] > 1 ? 's' : ''; ?> due soon
                                    <?php endif; ?>
                                </div>
                            </div>
                            <a href="dashboard.php?page=courses" style="font-size: 12px; color: var(--primary); font-weight: 600; text-decoration: none;">View <i class="fas fa-arrow-right"></i></a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($insights['priority_tasks'])): ?>
                <div class="card" style="margin-bottom: 20px;">
                    <h4 style="margin-bottom: 12px;"><i class="fas fa-bolt" style="color: var(--primary);"></i> Today's Priority</h4>
                    <?php foreach ($insights['priority_tasks'] as $i => $pt): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; <?php echo $i < count($insights['priority_tasks']) - 1 ? 'border-bottom: 1px solid #f0f0f0;' : ''; ?>">
                            <div>
                                <span class="priority-dot priority-<?php echo e($pt['priority']); ?>"></span>
                                <strong style="font-size: 14px;"><?php echo e($pt['task_name']); ?></strong>
                                <?php if ($pt['due_date'] === date('Y-m-d')): ?>
                                    <span style="font-size: 11px; color: #e02424; font-weight: 600;"> &middot; Due today</span>
                                <?php else: ?>
                                    <span style="font-size: 11px; color: var(--text-muted);"> &middot; Due <?php echo date('M j', strtotime($pt['due_date'])); ?></span>
                                <?php endif; ?>
                                <?php if ($pt['course_name']): ?>
                                    <div style="font-size: 12px; color: var(--text-muted);"><?php echo e($pt['course_name']); ?> — <?php echo (int) $pt['course_progress']; ?>% course progress</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="layout-grid">
                <!-- Add Task -->
                <div class="card">
                    <h4 style="margin-bottom: 15px;">Quick Add Task</h4>
                    <form action="dashboard.php?page=dashboard" method="POST">
                        <?php echo csrf_field(); ?>
                        <input type="text" name="task_name" placeholder="What's next on your list?" required>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 10px 0;">
                            <select name="course_id" style="padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                                <option value="">No course</option>
                                <?php foreach ($user_courses_for_dropdown as $uc): ?>
                                    <option value="<?php echo (int) $uc['id']; ?>"><?php echo e($uc['course_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="priority" style="padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                                <option value="low">Low priority</option>
                                <option value="medium" selected>Medium priority</option>
                                <option value="high">High priority</option>
                            </select>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                            <input type="date" name="due_date" style="padding: 10px; border-radius: 8px; border: 1px solid #ddd;" min="<?php echo date('Y-m-d'); ?>">
                            <input type="number" name="estimated_hours" placeholder="Est. hours" step="0.5" min="0" max="99" style="padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                        </div>

                        <button type="submit" name="add_task" class="btn-btn">Add to List</button>
                    </form>
                </div>

                <!-- Task List -->
                <div class="card">
                    <h4 style="margin-bottom: 20px;">Personal Reminders</h4>
                    <div class="task-container">
                        <?php if (mysqli_num_rows($tasks_query) > 0): ?>
                            <?php while ($task = mysqli_fetch_assoc($tasks_query)): ?>
                                <div class="task-item <?php echo ($task['status'] == 'Completed') ? 'completed' : ''; ?>">
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <a href="dashboard.php?toggle_id=<?php echo $task['id']; ?>" class="check-icon">
                                            <i class="fa<?php echo ($task['status'] == 'Completed') ? 's' : 'r'; ?> fa-check-circle"></i>
                                        </a>
                                        <div>
                                            <h4 style="font-size: 14px;">
                                                <span class="priority-dot priority-<?php echo e($task['priority'] ?? 'medium'); ?>"></span>
                                                <?php echo e($task['task_name']); ?>
                                            </h4>
                                            <span style="font-size: 11px; color: var(--text-muted);">
                                                <?php if (!empty($task['course_name'])): ?>
                                                    <?php echo e($task['course_name']); ?> &middot;
                                                <?php endif; ?>
                                                <?php if (!empty($task['due_date'])): ?>
                                                    Due <?php echo date('M j', strtotime($task['due_date'])); ?> &middot;
                                                <?php endif; ?>
                                                Added <?php echo date('h:i A', strtotime($task['created_at'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <a href="dashboard.php?delete_id=<?php echo $task['id']; ?>" style="color: #ff8a8a;" onclick="return confirm('Delete this task?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="color: var(--text-muted); text-align: center; padding: 20px;">No tasks yet. Enjoy your day!</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php elseif ($page == 'courses'): ?>
            <!-- COURSES TAB CONTENT -->
            <h3 style="margin-bottom: 20px;">Academic Enrollment</h3>

            <div style="display: grid; grid-template-columns: 1fr 2.5fr; gap: 25px;">

                <div class="card">
                    <h4 style="margin-bottom:15px;">Add New Course</h4>
                    <form method="POST">
                        <?php echo csrf_field(); ?>
                        <input type="text" name="course_name" placeholder="Course Title (e.g. Java Programming)" required>
                        <input type="text" name="instructor" placeholder="Instructor Name">
                        <input type="text" name="category" placeholder="Category (e.g. Coding)">
                        <input type="text" name="course_link" placeholder="Paste Material Link (YouTube/Drive/PDF)">
                        <button type="submit" name="add_course" class="btn-btn">Enroll Course</button>
                    </form>
                </div>


                <div class="course-grid">
                    <?php
                    // Single query: course + how many tasks are linked to it
                    // and how many are completed. Avoids an N+1 query per
                    // course card just to know whether progress is
                    // auto-tracked or manually set.
                    $stmt = $conn->prepare(
                        "SELECT c.*,
                                COUNT(t.id) AS linked_task_count,
                                SUM(t.status = 'Completed') AS linked_task_done
                         FROM courses c
                         LEFT JOIN tasks t ON t.course_id = c.id
                         WHERE c.user_id = ?
                         GROUP BY c.id
                         ORDER BY c.id DESC"
                    );
                    $stmt->bind_param('i', $user_id);
                    $stmt->execute();
                    $courses = $stmt->get_result();
                    $stmt->close();
                    if ($courses->num_rows > 0):
                        while ($c = $courses->fetch_assoc()):
                            $is_auto_tracked = (int) $c['linked_task_count'] > 0;
                            ?>
                            <div class="course-card">
                                <div class="course-header" style="background: var(--primary);"><i class="fas fa-graduation-cap"></i></div>
                                <div class="course-info" style="padding: 20px;">
                                    <span class="course-tag"><?php echo e($c['category']); ?></span>
                                    <h4 style="margin: 10px 0 5px;"><?php echo e($c['course_name']); ?></h4>
                                    <p style="font-size: 12px; color: var(--text-muted);">By <?php echo e($c['instructor']); ?></p>

                                    <div class="progress-bar-container" style="background: #edf2f7; height: 6px; margin: 15px 0 5px;">
                                        <div class="progress-bar-fill" style="width: <?php echo (int) $c['progress']; ?>%; background: var(--success); height: 100%; border-radius: 10px;"></div>
                                    </div>

                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                                        <small style="font-weight: 700; font-size: 11px;"><?php echo (int) $c['progress']; ?>% Done</small>
                                        <!-- THE LINK -->
                                        <a href="<?php echo e(safe_url($c['course_link'])); ?>" target="_blank" rel="noopener noreferrer" style="font-size: 12px; color: var(--primary); text-decoration: none; font-weight: 600;">
                                            Open Material <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </div>

                                    <?php if ($is_auto_tracked): ?>
                                        <div class="progress-source-badge auto">
                                            <i class="fas fa-link"></i> Auto-tracked &middot; <?php echo (int) $c['linked_task_done']; ?>/<?php echo (int) $c['linked_task_count']; ?> tasks done
                                        </div>
                                    <?php else: ?>
                                        <form method="POST" class="progress-source-badge manual" style="display:flex; align-items:center; gap:8px;">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="course_id" value="<?php echo (int) $c['id']; ?>">
                                            <i class="fas fa-sliders-h" style="color: var(--text-muted);"></i>
                                            <input type="range" name="progress" min="0" max="100" step="5" value="<?php echo (int) $c['progress']; ?>"
                                                   oninput="this.nextElementSibling.textContent = this.value + '%'" style="flex: 1;">
                                            <span style="font-size: 11px; min-width: 32px;"><?php echo (int) $c['progress']; ?>%</span>
                                            <button type="submit" name="update_progress" style="font-size: 11px; padding: 4px 10px; border: none; border-radius: 6px; background: var(--primary); color: white; cursor: pointer;">Set</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile;
                    else: ?>
                        <div class="card" style="grid-column: span 2; text-align: center; color: var(--text-muted);">
                            No courses added yet. Use the form on the left to start!
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($page == 'schedule'): ?>
            <div class="top-header">
                <h2 style="font-size: 20px;"><i class="fas fa-th"></i> My Schedule</h2>
            </div>

            <?php
            $days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
            $time_slots = ["08:00:00", "09:00:00", "10:00:00", "11:00:00", "12:00:00", "13:00:00", "14:00:00", "15:00:00", "16:00:00", "17:00:00"];

            // Scoped to the current user — this used to show every user's
            // classes on one shared grid even though add/edit/delete were
            // already per-user, which was inconsistent. Each student now
            // sees only their own schedule.
            $schedule_lookup = [];
            $has_any_schedule = false;
            $stmt = $conn->prepare("SELECT * FROM schedule WHERE user_id = ? ORDER BY day, start_time");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $schedule_result = $stmt->get_result();
            while ($row = $schedule_result->fetch_assoc()) {
                $has_any_schedule = true;
                $key_time = date("H:i:s", strtotime($row['start_time']));
                $schedule_lookup[$row['day']][$key_time] = $row;
            }
            $stmt->close();

            // ---------------------------------------------------------
            // RECOMMENDED STUDY WINDOW
            // Rule-based, not AI — two simple steps, both explainable:
            //  1. Find a free gap in TODAY's schedule (08:00–21:00).
            //  2. Pick the most urgent "at-risk" course (same rule as the
            //     dashboard's At-Risk Courses card: progress < 40%, OR a
            //     pending task due within 3 days) and, if it has an
            //     urgent linked task, name that task as the activity.
            // If either step comes up empty, no recommendation is shown —
            // this never fabricates a suggestion just to have something
            // to display.
            // ---------------------------------------------------------
            $study_window = null;
            $today_name = date('l');
            $now_minutes = ((int) date('H')) * 60 + (int) date('i');
            $window_start = 8 * 60;
            $window_end = 21 * 60;

            $today_classes = [];
            if (isset($schedule_lookup[$today_name])) {
                foreach ($schedule_lookup[$today_name] as $class) {
                    $s = (int) date('H', strtotime($class['start_time'])) * 60 + (int) date('i', strtotime($class['start_time']));
                    $e = (int) date('H', strtotime($class['end_time'])) * 60 + (int) date('i', strtotime($class['end_time']));
                    $today_classes[] = [$s, $e];
                }
            }

            $chosen_gap = find_free_gap($today_classes, $window_start, $window_end, $now_minutes);

            if ($chosen_gap !== null) {
                // Step 2: most urgent at-risk course, same criteria as the
                // dashboard's At-Risk Courses card.
                $stmt = $conn->prepare(
                    "SELECT c.id, c.course_name, c.progress,
                            t.id AS urgent_task_id, t.task_name AS urgent_task_name, t.due_date AS urgent_due_date
                     FROM courses c
                     LEFT JOIN tasks t ON t.course_id = c.id
                            AND t.status = 'Pending'
                            AND t.due_date IS NOT NULL
                            AND t.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                     WHERE c.user_id = ? AND (c.progress < 40 OR t.id IS NOT NULL)
                     ORDER BY (t.id IS NULL) ASC, t.due_date ASC, c.progress ASC
                     LIMIT 1"
                );
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $target_course = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($target_course) {
                    $reasons = [];
                    if ($target_course['urgent_task_id']) {
                        $due_label = $target_course['urgent_due_date'] === date('Y-m-d')
                            ? 'due today' : 'due ' . date('M j', strtotime($target_course['urgent_due_date']));
                        $reasons[] = "\"{$target_course['urgent_task_name']}\" is {$due_label}";
                    }
                    if ((int) $target_course['progress'] < 40) {
                        $reasons[] = "progress is only {$target_course['progress']}%";
                    }

                    $study_window = [
                        'start' => $chosen_gap[0],
                        'end' => $chosen_gap[1],
                        'course_name' => $target_course['course_name'],
                        'reason' => implode(' and ', $reasons),
                        'activity' => $target_course['urgent_task_name'] ?? ('Review ' . $target_course['course_name']),
                    ];
                }
            }
            ?>

            <?php if ($study_window): ?>
                <div class="card study-window-card">
                    <h4><i class="fas fa-bolt"></i> Recommended Study Window</h4>
                    <div class="study-window-time">
                        <?php echo date('g:i A', mktime(0, $study_window['start'])); ?> – <?php echo date('g:i A', mktime(0, $study_window['end'])); ?>
                    </div>
                    <p style="font-size: 13px; color: var(--text-muted); margin: 6px 0 10px;">
                        <strong><?php echo e($study_window['course_name']); ?></strong> —
                        <?php echo e(ucfirst($study_window['reason'])); ?>.
                    </p>
                    <div class="study-window-activity">
                        Recommended activity: <strong><?php echo e($study_window['activity']); ?></strong>
                    </div>
                </div>
            <?php endif; ?>

            <?php
            // ---------------------------------------------------------
            // PLAN MY WEEK
            // Same rule-based approach as the Study Window card above,
            // extended across the remaining days of this week (today
            // through Saturday — the schedule only models Mon-Sat, and
            // "this week" means what's actually still ahead, not days
            // that have already passed).
            //
            // For each remaining day: find one free gap (reusing
            // find_free_gap(), same function as today's card), then
            // assign it to the next course in a queue of "needs
            // attention" courses (progress < 40%, or a task due within
            // 7 days), cycling through them so the week doesn't just
            // repeat the single most urgent course six times.
            //
            // Only computed when the button is actually pressed — no
            // extra queries on a normal page load.
            // ---------------------------------------------------------
            $weekly_plan = null;
            if (isset($_GET['generate_plan'])) {
                $today_index = array_search($today_name, $days, true);
                // If today isn't in the Mon-Sat list (i.e. it's Sunday),
                // the current week is over — plan the upcoming Mon-Sat instead.
                $remaining_days = ($today_index !== false)
                    ? array_slice($days, $today_index)
                    : $days;

                // Build the "needs attention" queue: courses with low
                // progress or a task due within the next 7 days, each
                // paired with their single most urgent linked task (if any).
                $stmt = $conn->prepare(
                    "SELECT c.id, c.course_name, c.progress,
                            t.id AS urgent_task_id, t.task_name AS urgent_task_name, t.due_date AS urgent_due_date
                     FROM courses c
                     LEFT JOIN (
                         SELECT t1.*
                         FROM tasks t1
                         INNER JOIN (
                             SELECT course_id, MIN(due_date) AS min_due
                             FROM tasks
                             WHERE status = 'Pending' AND due_date IS NOT NULL
                                   AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                             GROUP BY course_id
                         ) t2 ON t1.course_id = t2.course_id AND t1.due_date = t2.min_due
                     ) t ON t.course_id = c.id
                     WHERE c.user_id = ? AND (c.progress < 40 OR t.id IS NOT NULL)
                     ORDER BY (t.id IS NULL) ASC, t.due_date ASC, c.progress ASC"
                );
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $needs_attention = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();

                $weekly_plan = [];
                if (!empty($needs_attention)) {
                    $queue_index = 0;
                    foreach ($remaining_days as $day) {
                        $day_classes = [];
                        if (isset($schedule_lookup[$day])) {
                            foreach ($schedule_lookup[$day] as $class) {
                                $s = (int) date('H', strtotime($class['start_time'])) * 60 + (int) date('i', strtotime($class['start_time']));
                                $e = (int) date('H', strtotime($class['end_time'])) * 60 + (int) date('i', strtotime($class['end_time']));
                                $day_classes[] = [$s, $e];
                            }
                        }

                        $earliest = ($day === $today_name) ? $now_minutes : $window_start;
                        $gap = find_free_gap($day_classes, $window_start, $window_end, $earliest);

                        if ($gap !== null) {
                            $course = $needs_attention[$queue_index % count($needs_attention)];
                            $queue_index++;

                            $weekly_plan[] = [
                                'day' => $day,
                                'start' => $gap[0],
                                'end' => $gap[1],
                                'course_name' => $course['course_name'],
                                'activity' => $course['urgent_task_name'] ?? ('Review ' . $course['course_name']),
                            ];
                        }
                    }
                }
            }
            ?>

            <a href="dashboard.php?page=schedule&generate_plan=1#weekly-plan" class="btn-btn" style="display:inline-block; width:auto; text-decoration:none; margin-bottom: 20px;">
                <i class="fas fa-wand-magic-sparkles"></i> Generate My Study Plan
            </a>

            <?php if ($weekly_plan !== null): ?>
                <div class="card" id="weekly-plan" style="margin-bottom: 20px;">
                    <h4 style="margin-bottom: 4px;">Your Weekly Plan</h4>
                    <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 15px;">
                        Based on your current courses, progress, and upcoming deadlines — updates every time you generate it.
                    </p>

                    <?php if (empty($weekly_plan)): ?>
                        <p style="text-align: center; color: var(--text-muted); padding: 20px;">
                            <?php echo empty($needs_attention)
                                ? "You're on track — no courses need extra attention this week."
                                : "No free study slots found in your remaining schedule this week."; ?>
                        </p>
                    <?php else: ?>
                        <?php foreach ($weekly_plan as $item): ?>
                            <div class="plan-day-row">
                                <div class="plan-day-name"><?php echo strtoupper(substr($item['day'], 0, 3)); ?></div>
                                <div>
                                    <div class="plan-time"><?php echo date('g:i A', mktime(0, $item['start'])); ?> – <?php echo date('g:i A', mktime(0, $item['end'])); ?></div>
                                    <div class="plan-course"><?php echo e($item['course_name']); ?></div>
                                    <div class="plan-activity">→ <?php echo e($item['activity']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!$has_any_schedule): ?>
                <div class="card" style="text-align: center; padding: 40px 20px; margin-bottom: 20px; color: var(--text-muted);">
                    <i class="fas fa-calendar-xmark" style="font-size: 32px; color: #cbd5e1; margin-bottom: 10px; display: block;"></i>
                    No classes scheduled yet. Use the form below to add your first one.
                </div>
            <?php endif; ?>

            <div class="timetable-grid-wrapper">
                <table class="grid-table">
                    <thead>
                        <tr>
                            <th style="width: 100px; background: var(--primary); color: white;">DAY / TIME</th>
                            <?php
                            // Define standard hourly headers
                            $time_headers = ["08:00 AM", "09:00 AM", "10:00 AM", "11:00 AM", "12:00 PM", "01:00 PM", "02:00 PM", "03:00 PM", "04:00 PM", "05:00 PM"];
                            foreach ($time_headers as $time) echo "<th>$time</th>";
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($days as $day):
                            echo "<tr>";
                            echo "<td class='day-column' style='font-weight:bold; vertical-align:middle; background:#f8faff; align:center;'>" . strtoupper(substr($day, 0, 3)) . "</td>";

                            $skip = 0;

                            for ($i = 0; $i < count($time_slots); $i++) {
                                if ($skip > 0) {
                                    $skip--;
                                    continue;
                                }

                                $current_slot = $time_slots[$i];
                                $class = isset($schedule_lookup[$day][$current_slot]) ? $schedule_lookup[$day][$current_slot] : null;
                                if ($class) {
                                    // --- ROBUST COLSPAN CALCULATION ---
                                    $start_ts = strtotime($class['start_time']);
                                    $end_ts   = strtotime($class['end_time']);

                                    if ($end_ts <= $start_ts) {
                                        $end_ts += 43200;
                                    }

                                    $colspan = (int)round(($end_ts - $start_ts) / 3600) + 1;

                                    // Safety checks:
                                    if ($colspan < 1) $colspan = 1;

                                    // Prevent the card from stretching past the last column (05:00 PM)
                                    $remaining_columns = count($time_slots) - $i;
                                    if ($colspan > $remaining_columns) {
                                        $colspan = $remaining_columns;
                                    }

                                    $skip = $colspan - 1;

                                    $sub = htmlspecialchars($class['subject']);
                                    $rm  = htmlspecialchars($class['room']);
                                    $st_disp = date("h:i A", $start_ts);
                                    $en_disp = date("h:i A", $end_ts);
                                    $sch_id  = isset($class['id']) ? $class['id'] : 0;

                                    echo "<td colspan='$colspan' style='padding: 5px;'>
                        <div class='slot-card' style='height:100%; border-left: 5px solid var(--primary); background:#eef2ff;'>
                            <strong style='display:block; color:var(--primary-dark); font-size:13px;'>$sub</strong>
                            <div style='font-size:10px; color:var(--primary); margin:3px 0;'>$st_disp - $en_disp</div>
                            <span style='font-size:10px; color:#666;'><i class='fas fa-location-dot'></i> $rm</span>";

                                    if ($sch_id > 0) {
                                        echo "<div style='margin-top:5px; display:flex; gap:10px; justify-content:center;'>
                            <a href='dashboard.php?page=schedule&edit_sch_id=$sch_id' style='color:var(--primary);'><i class='fas fa-edit'></i></a>
                            <a href='dashboard.php?page=schedule&del_sch_id=$sch_id' style='color:red;' onclick='return confirm(\"Delete?\")'><i class='fas fa-trash-alt'></i></a>
                          </div>";
                                    }
                                    echo "</div></td>";
                                } else {
                                    // Draw empty cell
                                    echo "<td style='background: #fafafa; opacity: 0.5;'></td>";
                                }
                            }
                            echo "</tr>";
                        endforeach;
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- UPDATE FORM SECTION -->
            <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 25px;">
                <div class="card">
                    <h4 style="margin-bottom: 20px; color: var(--primary);">
                        <i class="fas <?php echo $edit_data ? 'fa-edit' : 'fa-calendar-plus'; ?>"></i>
                        <?php echo $edit_data ? 'Edit Lecture' : 'Schedule New Lecture'; ?>
                    </h4>

                    <form method="POST" action="dashboard.php?page=schedule">
                        <?php echo csrf_field(); ?>
                        <?php if ($edit_data): ?>
                            <input type="hidden" name="update_id" value="<?php echo (int) $edit_data['id']; ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label style="font-size: 11px; font-weight: 700;">DAY</label>
                            <select name="day" class="form-control" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd; margin-bottom:15px;" required>
                                <?php
                                foreach ($days as $d) {
                                    $sel = ($edit_data && $edit_data['day'] == $d) ? 'selected' : '';
                                    echo "<option $sel>$d</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div>
                                <label style="font-size: 11px; font-weight: 700;">START TIME</label>
                                <select name="start_time" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd;" required>
                                    <?php
                                    foreach ($time_headers as $t) {
                                        $sel = ($edit_data && $edit_start == $t) ? 'selected' : '';
                                        echo "<option $sel>$t</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div>
                                <label style="font-size: 11px; font-weight: 700;">END TIME</label>
                                <select name="end_time" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ddd;" required>
                                    <?php
                                    $end_headers = array_merge($time_headers, ["06:00 PM"]);
                                    foreach ($end_headers as $t) {
                                        $sel = ($edit_data && $edit_end == $t) ? 'selected' : '';
                                        echo "<option $sel>$t</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <label style="font-size: 11px; font-weight: 700;">SUBJECT</label>
                        <input type="text" name="subject" placeholder="e.g. Data Structures" value="<?php echo $edit_data ? e($edit_data['subject']) : ''; ?>" required>

                        <label style="font-size: 11px; font-weight: 700;">ROOM / LINK</label>
                        <input type="text" name="room" placeholder="e.g. Hall 02" value="<?php echo $edit_data ? e($edit_data['room']) : ''; ?>" required>

                        <?php if ($edit_data): ?>
                            <button type="submit" name="do_update_schedule" class="btn-btn" style="background: #f6c23e; color: #333;">Update Existing Lecture</button>
                            <a href="dashboard.php?page=schedule" style="display:block; text-align:center; margin-top:10px; font-size:12px; color:gray; text-decoration:none;">Cancel Edit</a>
                        <?php else: ?>
                            <button type="submit" name="add_schedule" class="btn-btn">Add to Schedule Grid</button>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="card" style="background: linear-gradient(to right, #4e73df, #224abe); color: white;">
                    <h4><i class="fas fa-info-circle"></i> Understanding the Grid</h4>
                    <ul style="margin-top: 15px; font-size: 13px; line-height: 1.8; opacity: 0.9; list-style: none;">
                        <li><i class="fas fa-check-circle"></i> <strong>Automatic Spanning:</strong> The grid automatically calculates the duration and merges columns based on your Start and End times.</li>
                        <li style="margin-top:10px;"><i class="fas fa-edit"></i> <strong>Editing:</strong> Clicking "Edit" on a card will pull the details back into the form for quick adjustments.</li>
                        <li style="margin-top:10px;"><i class="fas fa-sync-alt"></i> <strong>Overwrite Logic:</strong> Using the "Update" button will modify the existing record in the database instead of creating a duplicate.</li>
                        <li style="margin-top:10px;"><i class="fas fa-trash-alt"></i> <strong>Removal:</strong> Deleting a lecture is permanent and will immediately free up those time slots on the grid.</li>
                    </ul>
                </div>
            </div>
        <?php elseif ($page == 'calendar'): ?>
            <?php
            // ---------------------------------------------------------
            // CALENDAR TAB
            // Monthly grid of the user's own tasks, plotted by due_date.
            // Month navigation via ?month=YYYY-MM (defaults to current
            // month). Clicking a task's edit icon opens the Edit Task
            // form pre-filled — this is the first place in the app tasks
            // can actually be edited, not just toggled/deleted.
            // ---------------------------------------------------------
            $month_param = $_GET['month'] ?? date('Y-m');
            if (!preg_match('/^\d{4}-\d{2}$/', $month_param)) {
                $month_param = date('Y-m');
            }
            $month_start = $month_param . '-01';
            $month_ts = strtotime($month_start);
            $days_in_month = (int) date('t', $month_ts);
            $first_weekday = (int) date('N', $month_ts); // 1 (Mon) - 7 (Sun)
            $prev_month = date('Y-m', strtotime('-1 month', $month_ts));
            $next_month = date('Y-m', strtotime('+1 month', $month_ts));

            // All of this user's tasks with a due_date in this month.
            $stmt = $conn->prepare(
                "SELECT t.*, c.course_name FROM tasks t
                 LEFT JOIN courses c ON c.id = t.course_id
                 WHERE t.user_id = ? AND t.due_date IS NOT NULL
                 AND DATE_FORMAT(t.due_date, '%Y-%m') = ?
                 ORDER BY t.due_date ASC, t.due_time ASC"
            );
            $stmt->bind_param('is', $user_id, $month_param);
            $stmt->execute();
            $month_tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $tasks_by_day = [];
            foreach ($month_tasks as $mt) {
                $day_num = (int) date('j', strtotime($mt['due_date']));
                $tasks_by_day[$day_num][] = $mt;
            }

            // If ?edit_task_id= is present, load that task for the edit form.
            $edit_task = null;
            if (isset($_GET['edit_task_id'])) {
                $etid = (int) $_GET['edit_task_id'];
                $stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
                $stmt->bind_param('ii', $etid, $user_id);
                $stmt->execute();
                $edit_task = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            }

            // User's courses, for the edit form's course dropdown.
            $stmt = $conn->prepare("SELECT id, course_name FROM courses WHERE user_id = ? ORDER BY course_name ASC");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $courses_for_edit_dropdown = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            ?>

            <div class="top-header">
                <h2 style="font-size: 20px;"><i class="fas fa-calendar-days"></i> Calendar</h2>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <a href="dashboard.php?page=calendar&month=<?php echo $prev_month; ?>" class="calendar-nav-btn"><i class="fas fa-chevron-left"></i></a>
                    <strong style="min-width: 140px; text-align: center; display: inline-block;"><?php echo date('F Y', $month_ts); ?></strong>
                    <a href="dashboard.php?page=calendar&month=<?php echo $next_month; ?>" class="calendar-nav-btn"><i class="fas fa-chevron-right"></i></a>
                </div>
            </div>

            <?php if ($edit_task): ?>
                <div class="card" style="border-left: 4px solid var(--primary); margin-bottom: 20px;">
                    <h4 style="margin-bottom: 15px;"><i class="fas fa-pen"></i> Edit Task</h4>
                    <form method="POST" action="dashboard.php?page=calendar">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="task_id" value="<?php echo (int) $edit_task['id']; ?>">
                        <input type="hidden" name="return_page" value="calendar">

                        <input type="text" name="task_name" value="<?php echo e($edit_task['task_name']); ?>" required style="margin-bottom: 10px;">

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                            <select name="course_id" style="padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                                <option value="">No course</option>
                                <?php foreach ($courses_for_edit_dropdown as $uc): ?>
                                    <option value="<?php echo (int) $uc['id']; ?>" <?php echo ((int) $edit_task['course_id'] === (int) $uc['id']) ? 'selected' : ''; ?>>
                                        <?php echo e($uc['course_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select name="priority" style="padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                                <option value="low" <?php echo $edit_task['priority'] === 'low' ? 'selected' : ''; ?>>Low priority</option>
                                <option value="medium" <?php echo $edit_task['priority'] === 'medium' ? 'selected' : ''; ?>>Medium priority</option>
                                <option value="high" <?php echo $edit_task['priority'] === 'high' ? 'selected' : ''; ?>>High priority</option>
                            </select>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                            <input type="date" name="due_date" value="<?php echo e($edit_task['due_date'] ?? ''); ?>" style="padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                            <input type="time" name="due_time" value="<?php echo $edit_task['due_time'] ? substr($edit_task['due_time'], 0, 5) : ''; ?>" style="padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                            <input type="number" name="estimated_hours" placeholder="Est. hours" step="0.5" min="0" max="99" value="<?php echo e($edit_task['estimated_hours'] ?? ''); ?>" style="padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                        </div>

                        <button type="submit" name="update_task" class="btn-btn">Save Changes</button>
                        <a href="dashboard.php?page=calendar&month=<?php echo $month_param; ?>" style="display:block; text-align:center; margin-top:10px; font-size:12px; color:gray; text-decoration:none;">Cancel</a>
                    </form>
                </div>
            <?php endif; ?>

            <div class="calendar-grid">
                <?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $wd): ?>
                    <div class="calendar-weekday"><?php echo $wd; ?></div>
                <?php endforeach; ?>

                <?php for ($blank = 1; $blank < $first_weekday; $blank++): ?>
                    <div class="calendar-cell empty"></div>
                <?php endfor; ?>

                <?php for ($d = 1; $d <= $days_in_month; $d++): ?>
                    <?php $is_today = ($month_param === date('Y-m') && $d === (int) date('j')); ?>
                    <div class="calendar-cell <?php echo $is_today ? 'today' : ''; ?>">
                        <div class="calendar-date"><?php echo $d; ?></div>
                        <?php if (!empty($tasks_by_day[$d])): ?>
                            <?php foreach ($tasks_by_day[$d] as $ct): ?>
                                <div class="calendar-task <?php echo $ct['status'] === 'Completed' ? 'done' : ''; ?>">
                                    <span class="priority-dot priority-<?php echo e($ct['priority']); ?>"></span>
                                    <span class="calendar-task-name"><?php echo e($ct['task_name']); ?></span>
                                    <a href="dashboard.php?page=calendar&month=<?php echo $month_param; ?>&edit_task_id=<?php echo (int) $ct['id']; ?>#top" class="calendar-task-edit" title="Edit">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

    </div>
</body>

</html>