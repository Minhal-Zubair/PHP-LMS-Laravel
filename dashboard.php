<?php
require_once('auth.php');
require_once('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get User Info from Session
$user_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'];

// Helper to check for column existence in a table
function column_exists($conn, $table, $column)
{
    $col = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `" . mysqli_real_escape_string($conn, $table) . "` LIKE '$col'");
    return ($res && mysqli_num_rows($res) > 0);
}

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

// Detect schema: whether schedule uses `time_slot` or start_time/end_time
$has_time_slot = column_exists($conn, 'schedule', 'time_slot');

// Determine which page/tab to display
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// ADD TASK
if (isset($_POST['add_task'])) {
    $task_name = mysqli_real_escape_string($conn, $_POST['task_name']);
    if (!empty($task_name)) {
        mysqli_query($conn, "INSERT INTO tasks (user_id, task_name, status) VALUES ('$user_id', '$task_name', 'Pending')");
        header("Location: dashboard.php?page=dashboard");
    }
}


// TOGGLE TASK STATUS 
if (isset($_GET['toggle_id'])) {
    $tid = $_GET['toggle_id'];
    // Verify the task belongs to the logged-in user
    $check = mysqli_query($conn, "SELECT status FROM tasks WHERE id='$tid' AND user_id='$user_id'");
    if (mysqli_num_rows($check) > 0) {
        $res = mysqli_fetch_assoc($check);
        $new_status = ($res['status'] == 'Pending') ? 'Completed' : 'Pending';
        mysqli_query($conn, "UPDATE tasks SET status='$new_status' WHERE id='$tid'");
    }
    header("Location: dashboard.php?page=dashboard");
}

// DELETE TASK
if (isset($_GET['delete_id'])) {
    $did = $_GET['delete_id'];
    mysqli_query($conn, "DELETE FROM tasks WHERE id='$did' AND user_id='$user_id'");
    header("Location: dashboard.php?page=dashboard");
}

$tasks_query = mysqli_query($conn, "SELECT * FROM tasks WHERE user_id='$user_id' ORDER BY status DESC, created_at DESC");

// Get Statistics for the Progress Bar
$stat_res = mysqli_query($conn, "SELECT COUNT(*) as total, SUM(status='Completed') as done FROM tasks WHERE user_id='$user_id'");
$stats = mysqli_fetch_assoc($stat_res);
$percent = ($stats['total'] > 0) ? round(($stats['done'] / $stats['total']) * 100) : 0;


if (isset($_POST['add_course'])) {
    $c_name = mysqli_real_escape_string($conn, $_POST['course_name']);
    $inst = mysqli_real_escape_string($conn, $_POST['instructor']);
    $cat = mysqli_real_escape_string($conn, $_POST['category']);
    $link = mysqli_real_escape_string($conn, $_POST['course_link']);

    $query = "INSERT INTO courses (user_id, course_name, instructor, category, course_link) 
              VALUES ('$user_id', '$c_name', '$inst', '$cat', '$link')";
    mysqli_query($conn, $query);
    header("Location: dashboard.php?page=courses");
}

if (isset($_POST['add_schedule']) || isset($_POST['do_update_schedule'])) {
    $day = mysqli_real_escape_string($conn, $_POST['day']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $start = mysqli_real_escape_string($conn, $_POST['start_time']);
    $end = mysqli_real_escape_string($conn, $_POST['end_time']);
    $room = mysqli_real_escape_string($conn, $_POST['room']);

    if (strtotime($start) < strtotime($end)) {
        // Convert to 24h SQL time format
        $start_sql = date('H:i:s', strtotime($start));
        $end_sql = date('H:i:s', strtotime($end));
        $time_range = $start . "|" . $end;

        if (isset($_POST['do_update_schedule'])) {
            $id = (int)$_POST['update_id'];
            if ($has_time_slot) {
                $query = "UPDATE schedule SET day='$day', subject='$subject', time_slot='$time_range', room='$room' WHERE id='$id' AND user_id='$user_id'";
            } else {
                $query = "UPDATE schedule SET day='$day', subject='$subject', start_time='$start_sql', end_time='$end_sql', room='$room' WHERE id='$id'";
            }
        } else {
            if ($has_time_slot) {
                $query = "INSERT INTO schedule (user_id, day, subject, time_slot, room, created_at) VALUES ('$user_id', '$day', '$subject', '$time_range', '$room', NOW())";
            } else {
                $query = "INSERT INTO schedule (user_id, day, subject, start_time, end_time, room, created_at) VALUES ('$user_id', '$day', '$subject', '$start_sql', '$end_sql', '$room', NOW())";
            }
        }

        mysqli_query($conn, $query);
        header("Location: dashboard.php?page=schedule");
        exit();
    } else {
        echo "<script>alert('Error: Start time must be before end time');</script>";
    }
}

if (isset($_GET['del_sch_id'])) {
    $sid = (int)$_GET['del_sch_id'];
    // Removed AND user_id='$user_id'
    mysqli_query($conn, "DELETE FROM schedule WHERE id='$sid'");
    header("Location: dashboard.php?page=schedule");
}

// --- Dynamic Backgrounds Logic ---
$bg_images = [
    'dashboard' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ9MnEtio_vgCW0Odfxh9JTPsL2jMdrUsaCl80rTBu3Q0JQIdntlwGNDg&s=10', // Office/Clean
    'courses'   => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS8SXk9X4H5cXJNPabhbhmqx2BaZ07XqM1vd6YquwVn8s6rhd0BMKBqq6yy&s=10', // Library
    'schedule'  => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSpm60wN47EiLr0GEXzJzHbHya_CgXqFH7iNKG6ZM7NrckP3jIgAtlKmOY&s=10'  // Calendar/Planner
];

// Fallback if page doesn't exist in array
$current_bg = isset($bg_images[$page]) ? $bg_images[$page] : $bg_images['dashboard'];

// Detect whether DB has a `time_slot` column (some installs use start_time/end_time instead)
$has_time_slot = false;
$col_check = mysqli_query($conn, "SHOW COLUMNS FROM schedule LIKE 'time_slot'");
if ($col_check && mysqli_num_rows($col_check) > 0) {
    $has_time_slot = true;
}

$edit_data = null;
if (isset($_GET['edit_sch_id'])) {
    $edit_id = (int)$_GET['edit_sch_id'];
    $edit_res = mysqli_query($conn, "SELECT * FROM schedule WHERE id='$edit_id'");
    $edit_data = mysqli_fetch_assoc($edit_res);

    // Populate edit start/end based on available columns
    if ($edit_data) {
        if ($has_time_slot && !empty($edit_data['time_slot']) && strpos($edit_data['time_slot'], '|') !== false) {
            $times = explode('|', $edit_data['time_slot']);
            $edit_start = $times[0];
            $edit_end = $times[1];
        } elseif (!empty($edit_data['start_time']) || !empty($edit_data['end_time'])) {
            $edit_start = !empty($edit_data['start_time']) ? date('h:i A', strtotime($edit_data['start_time'])) : '';
            $edit_end = !empty($edit_data['end_time']) ? date('h:i A', strtotime($edit_data['end_time'])) : '';
        } else {
            $edit_start = '';
            $edit_end = '';
        }
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
    <style>
        :root {
            --primary: #4e73df;
            --primary-dark: #2e59d9;
            --dark: #1a1c23;
            --bg: #f4f7fe;
            --success: #00ba94;
            --white: #ffffff;
            --text-main: #2d3748;
            --text-muted: #718096;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            display: flex;
            background-color: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
        }

        /* Sidebar Navigation */
        .sidebar {
            width: 260px;
            background: var(--dark);
            color: #fff;
            padding: 30px 20px;
            position: sticky;
            top: 0;
            height: 100vh;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
        }

        .sidebar .brand {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-link {
            color: #a0aec0;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 15px;
            border-radius: 12px;
            transition: 0.3s;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
        }

        .nav-link.active {
            background: var(--primary);
            box-shadow: 0 4px 12px rgba(78, 115, 223, 0.3);
        }

        .logout-link {
            margin-top: auto;
            color: #fc8181 !important;
            padding: 12px 15px;
        }

        /* Main Content */
        .main-content {
            flex-grow: 1;
            padding: 40px;
            overflow-y: auto;
        }

        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
        }

        .user-badge {
            background: var(--white);
            padding: 8px 18px;
            border-radius: 50px;
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
            font-size: 14px;
        }

        /* Dashboard Cards */
        .card {
            background: var(--white);
            border-radius: 18px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.03);
            margin-bottom: 25px;
        }

        .banner {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
        }

        /* Progress Bar */
        .progress-container {
            background: rgba(255, 255, 255, 0.2);
            height: 8px;
            border-radius: 10px;
            margin: 15px 0 5px;
        }

        .progress-fill {
            background: #fff;
            height: 100%;
            border-radius: 10px;
            transition: 0.8s ease;
        }

        .layout-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 25px;
        }

        /* Tasks */
        .task-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .task-item:last-child {
            border-bottom: none;
        }

        .task-item.completed h4 {
            text-decoration: line-through;
            color: var(--text-muted);
        }

        .check-icon {
            font-size: 20px;
            cursor: pointer;
            transition: 0.3s;
            color: #cbd5e0;
        }

        .task-item.completed .check-icon {
            color: var(--success);
        }

        /* Courses */
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .course-card {
            background: var(--white);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: 0.3s;
            border: 1px solid #eee;
        }

        .course-card:hover {
            transform: translateY(-8px);
        }

        .course-header {
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
        }

        .course-body {
            padding: 20px;
        }

        /* Form */
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            margin-bottom: 12px;
            outline: none;
        }

        .btn-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            width: 100%;
            cursor: pointer;
            font-weight: 600;
        }

        /* Update the main content to handle the background */
        .main-content {
            flex-grow: 1;
            padding: 40px;
            overflow-y: auto;
            background: linear-gradient(rgba(105, 117, 145, 0.85), rgba(67, 77, 106, 0.43)),
                url('<?php echo $current_bg; ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            transition: background 0.5s ease-in-out;
        }

        /* Timetable Table Styles */
        .timetable-container {
            width: 100%;
            overflow-x: auto;
            margin-bottom: 30px;
        }

        .styled-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .styled-table thead tr {
            background-color: var(--primary);
            color: #ffffff;
            text-align: left;
            font-weight: 600;
        }

        .styled-table th,
        .styled-table td {
            padding: 15px 20px;
        }

        .styled-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: 0.2s;
        }

        .styled-table tbody tr:hover {
            background-color: #f8faff;
        }

        .day-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            background: #eef2ff;
            color: var(--primary);
        }

        .time-text {
            font-weight: 600;
            color: var(--text-main);
            font-size: 13px;
        }



        .timetable-grid-wrapper {
            overflow-x: auto;
            /* Allows scrolling on smaller screens */
            background: white;
            padding: 10px;
            /* Reduced padding */
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .grid-table {
            width: max-content;
            table-layout: auto;
            /* This forces columns to respect widths and colspans */
            border-collapse: collapse;
            
            /* Ensures it doesn't get too squashed */
        }


        .grid-table th {
            background: #f8faff;
            color: var(--primary);
            font-size: 13px;
            padding: 15px 5px;
            border: 1px solid #edf2f7;
            width: 150px;
            /* Define the base width of one hour here */
        }

        .grid-table td {
            height: 130px;
            vertical-align: top;
            padding: 0;
            border: 1px solid #edf2f7;
            overflow: hidden;
            
        }

        .slot-card {
            width: 100%;
            min-height: 130px;
            box-sizing: border-box;
            background: #e9f2ff;
            border-left: 6px solid var(--primary);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            text-align: center;
            padding: 10px;
        }

        .slot-subject {
            font-size: 16px;
            font-weight: 700;
            color: #1b3d9d;
            line-height: 1.4;
            white-space: normal;
            word-break: break-word;
        }

        .slot-time {
            margin-top: 8px;
            font-size: 13px;
            color: #4e73df;
            font-weight: 600;
        }

        .slot-room {
            margin-top: 8px;
            font-size: 12px;
            color: #666;
        }

        /* Premium Timetable Styling */
        .schedule-container {
            background: var(--white);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .timetable {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
            /* Gap between rows */
        }

        .timetable th {
            background: #f8f9fc;
            color: var(--text-muted);
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid #edf2f7;
        }

        .timetable td {
            padding: 20px 15px;
            background: #ffffff;
            border-top: 1px solid #f1f5f9;
            border-bottom: 1px solid #f1f5f9;
        }

        .timetable tr td:first-child {
            border-left: 1px solid #f1f5f9;
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
            font-weight: 700;
            color: var(--primary);
        }

        .timetable tr td:last-child {
            border-right: 1px solid #f1f5f9;
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
        }

        .timetable tr:hover td {
            background: #fbfcfe;
            border-color: var(--primary);
        }

        .subject-name {
            font-size: 15px;
            font-weight: 600;
            color: var(--dark);
            display: block;
        }

        .room-tag {
            background: #fff5f5;
            color: #f56565;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }


        @media (max-width: 992px) {
            .layout-grid {
                grid-template-columns: 1fr;
            }

            .sidebar {
                width: 70px;
                padding: 20px 10px;
            }

            .sidebar span {
                display: none;
            }
        }
    </style>
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
                <h1 style="font-size: 24px;">Welcome, <?php echo explode(' ', $fullname)[0]; ?>!</h1>
                <p style="color: var(--text-muted); font-size: 14px; color: black;"><?php echo date('l, d F Y'); ?></p>
            </div>
            <div class="user-badge">
                <i class="fas fa-user-graduate"></i> Student Account
            </div>
        </div>

        <?php if ($page == 'dashboard'): ?>
            <!-- DASHBOARD TAB CONTENT -->
            <div class="card banner">
                <h3>Current Goal Completion: <?php echo $percent; ?>%</h3>
                <div class="progress-container">
                    <div class="progress-fill" style="width: <?php echo $percent; ?>%"></div>
                </div>
                <p style="font-size: 13px; margin-top: 10px; opacity: 0.9;">
                    You have finished <?php echo ($stats['done'] ? $stats['done'] : 0); ?> of <?php echo $stats['total']; ?> tasks today.
                </p>
            </div>

            <div class="layout-grid">
                <!-- Add Task -->
                <div class="card">
                    <h4 style="margin-bottom: 15px;">Quick Add Task</h4>
                    <form action="dashboard.php?page=dashboard" method="POST">
                        <input type="text" name="task_name" placeholder="What's next on your list?" required>
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
                                            <h4 style="font-size: 14px;"><?php echo $task['task_name']; ?></h4>
                                            <span style="font-size: 11px; color: var(--text-muted);">
                                                Added at <?php echo date('h:i A', strtotime($task['created_at'])); ?>
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
            <div class="course-grid">

                <!-- Card 1 -->
                <div class="course-card">
                    <div class="course-header" style="background: #4e73df;"><i class="fas fa-laptop-code"></i></div>
                    <div class="course-body">
                        <span class="course-tag">Programming</span>
                        <h4 style="margin: 10px 0 5px;">Full Stack Web Engineering</h4>
                        <p style="font-size: 12px; color: var(--text-muted);">Instructor: Dr. Sarah Smith</p>
                        <div class="progress-container" style="background: #edf2f7;">
                            <div class="progress-fill" style="width: 80%; background: var(--success);"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                            <small style="font-weight: 700; font-size: 11px;">80% Mastery</small>
                            <a href="course_portal.php" style="font-size:12px; color:var(--primary); text-decoration:none; font-weight:600;">Open Portal</a>
                        </div>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="course-card">
                    <div class="course-header" style="background: #1cc88a;"><i class="fas fa-database"></i></div>
                    <div class="course-info course-body">
                        <span class="course-tag">Database</span>
                        <h4 style="margin: 10px 0 5px;">Relational Databases (SQL)</h4>
                        <p style="font-size: 12px; color: var(--text-muted);">Instructor: Prof. Alan Turing</p>
                        <div class="progress-container" style="background: #edf2f7;">
                            <div class="progress-fill" style="width: 45%; background: var(--success);"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                            <small style="font-weight: 700; font-size: 11px;">45% Mastery</small>
                            <a href="course_portal.php" style="font-size:12px; color:var(--primary); text-decoration:none; font-weight:600;">Open Portal</a>
                        </div>
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="course-card">
                    <div class="course-header" style="background: #f6c23e;"><i class="fas fa-brain"></i></div>
                    <div class="course-info course-body">
                        <span class="course-tag">Intelligence</span>
                        <h4 style="margin: 10px 0 5px;">Artificial Intelligence Basics</h4>
                        <p style="font-size: 12px; color: var(--text-muted);">Instructor: Dr. Emily Stone</p>
                        <div class="progress-container" style="background: #edf2f7;">
                            <div class="progress-fill" style="width: 15%; background: var(--success);"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                            <small style="font-weight: 700; font-size: 11px;">15% Mastery</small>
                            <a href="course_portal.php" style="font-size:12px; color:var(--primary); text-decoration:none; font-weight:600;">Open Portal</a>
                        </div>
                    </div>
                </div>

            </div>
            <div style="display: grid; grid-template-columns: 1fr 2.5fr; gap: 25px;">

                <div class="card">
                    <h4 style="margin-bottom:15px;">Add New Course</h4>
                    <form method="POST">
                        <input type="text" name="course_name" placeholder="Course Title (e.g. Java Programming)" required>
                        <input type="text" name="instructor" placeholder="Instructor Name">
                        <input type="text" name="category" placeholder="Category (e.g. Coding)">
                        <input type="text" name="course_link" placeholder="Paste Material Link (YouTube/Drive/PDF)">
                        <button type="submit" name="add_course" class="btn-btn">Enroll Course</button>
                    </form>
                </div>


                <div class="course-grid">
                    <?php
                    $courses = mysqli_query($conn, "SELECT * FROM courses WHERE user_id='$user_id' ORDER BY id DESC");
                    if (mysqli_num_rows($courses) > 0):
                        while ($c = mysqli_fetch_assoc($courses)): ?>
                            <div class="course-card">
                                <div class="course-header" style="background: var(--primary);"><i class="fas fa-graduation-cap"></i></div>
                                <div class="course-info" style="padding: 20px;">
                                    <span class="course-tag"><?php echo $c['category']; ?></span>
                                    <h4 style="margin: 10px 0 5px;"><?php echo $c['course_name']; ?></h4>
                                    <p style="font-size: 12px; color: var(--text-muted);">By <?php echo $c['instructor']; ?></p>

                                    <div class="progress-bar-container" style="background: #edf2f7; height: 6px; margin: 15px 0 5px;">
                                        <div class="progress-bar-fill" style="width: <?php echo $c['progress']; ?>%; background: var(--success); height: 100%; border-radius: 10px;"></div>
                                    </div>

                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                                        <small style="font-weight: 700; font-size: 11px;"><?php echo $c['progress']; ?>% Done</small>
                                        <!-- THE LINK -->
                                        <a href="<?php echo $c['course_link']; ?>" target="_blank" style="font-size: 12px; color: var(--primary); text-decoration: none; font-weight: 600;">
                                            Open Material <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </div>
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
                <h2 style="font-size: 20px;"><i class="fas fa-th"></i> University Master Schedule</h2>
            </div>

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
                        $days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
                        // 24-hour keys to match the DB
                        $time_slots = ["08:00:00", "09:00:00", "10:00:00", "11:00:00", "12:00:00", "13:00:00", "14:00:00", "15:00:00", "16:00:00", "17:00:00"];

                        $dummy_schedule_items = [
                            ["day" => "Monday", "start_time" => "08:00:00", "end_time" => "10:00:00", "subject" => "Mathematics", "room" => "A-101"],
                            ["day" => "Monday", "start_time" => "10:00:00", "end_time" => "11:00:00", "subject" => "Statistics", "room" => "B-204"],
                            ["day" => "Monday", "start_time" => "11:00:00", "end_time" => "14:00:00", "subject" => "Data Structures", "room" => "A-101"], // 14:00 is 2PM
                            ["day" => "Monday", "start_time" => "14:00:00", "end_time" => "17:00:00", "subject" => "AI", "room" => "B-204"], // 17:00 is 5PM
                            ["day" => "Tuesday", "start_time" => "08:00:00", "end_time" => "09:00:00", "subject" => "English Literature", "room" => "C-310"],
                            ["day" => "Tuesday", "start_time" => "09:00:00", "end_time" => "11:00:00", "subject" => "Business", "room" => "C-310"],
                            ["day" => "Tuesday", "start_time" => "11:00:00", "end_time" => "13:00:00", "subject" => "Database Systems", "room" => "C-310"],
                            ["day" => "Tuesday", "start_time" => "13:00:00", "end_time" => "15:00:00", "subject" => "Web Development", "room" => "C-310"],
                            ["day" => "Tuesday", "start_time" => "15:00:00", "end_time" => "17:00:00", "subject" => "Calculus", "room" => "C-310"],
                            ["day" => "Wednesday", "start_time" => "09:00:00", "end_time" => "12:00:00", "subject" => "Programming Lab", "room" => "Lab-2"],
                            ["day" => "Wednesday", "start_time" => "12:00:00", "end_time" => "13:00:00", "subject" => "OOP", "room" => "Lab-2"],
                            ["day" => "Wednesday", "start_time" => "13:00:00", "end_time" => "15:00:00", "subject" => "PakStudies", "room" => "Lab-2"],
                            ["day" => "Wednesday", "start_time" => "15:00:00", "end_time" => "17:00:00", "subject" => "Ethics", "room" => "Lab-2"],
                            ["day" => "Thursday", "start_time" => "10:00:00", "end_time" => "12:00:00", "subject" => "Java Prog.", "room" => "D-115"],
                            ["day" => "Thursday", "start_time" => "12:00:00", "end_time" => "15:00:00", "subject" => "Operating Systems", "room" => "D-115"],
                            ["day" => "Thursday", "start_time" => "15:00:00", "end_time" => "17:00:00", "subject" => "Data Structures", "room" => "D-115"],
                            ["day" => "Friday", "start_time" => "09:00:00", "end_time" => "12:00:00", "subject" => "OS Lab", "room" => "D-115"]
                        ];
                        $schedule_lookup = [];
                        $schedule_result = mysqli_query($conn, "SELECT * FROM schedule ORDER BY day, start_time");
                        if ($schedule_result && mysqli_num_rows($schedule_result) > 0) {
                            while ($row = mysqli_fetch_assoc($schedule_result)) {
                                // Ensure keys match HH:MM:SS format
                                $key_time = date("H:i:s", strtotime($row['start_time']));
                                $schedule_lookup[$row['day']][$key_time] = $row;
                            }
                        } else {
                            // 2. Only if the entire table is empty, show Dummy Data
                            foreach ($dummy_schedule_items as $item) {
                                $key_time = date("H:i:s", strtotime($item['start_time']));
                                $schedule_lookup[$item['day']][$key_time] = $item;
                            }
                        }
                        // } else {
                        //     foreach ($dummy_schedule_items as $item) {
                        //         $schedule_lookup[$item['day']][$item['start_time']] = $item;
                        //     }


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

                                    echo "Start: {$class['start_time']} | End: {$class['end_time']} | Colspan: $colspan | Skip: $skip <br>";
                                    echo "Current Slot: $current_slot | Index: $i <br>";

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
                        <?php if ($edit_data): ?>
                            <input type="hidden" name="update_id" value="<?php echo $edit_data['id']; ?>">
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
                        <input type="text" name="subject" placeholder="e.g. Data Structures" value="<?php echo $edit_data ? $edit_data['subject'] : ''; ?>" required>

                        <label style="font-size: 11px; font-weight: 700;">ROOM / LINK</label>
                        <input type="text" name="room" placeholder="e.g. Hall 02" value="<?php echo $edit_data ? $edit_data['room'] : ''; ?>" required>

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
        <?php endif; ?>

    </div>
</body>

</html>