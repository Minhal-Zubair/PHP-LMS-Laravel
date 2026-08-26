<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

// Ensure fullname exists to avoid explode errors
$fullname = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : 'Student';
$user_id = (int) $_SESSION['user_id'];

// 1. Check if ID is in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: No course ID provided in the URL. Use course_portal.php?id=YOUR_ID");
}

$course_id = (int) $_GET['id'];

// 2. Fetch the course (ownership check kept — was already correct here)
$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $course_id, $user_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$course) {
    die("Error: Course not found or you do not have permission to view it.");
}

// 3. Fallback for progress if column is missing
$progress = isset($course['progress']) ? $course['progress'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['course_name']); ?> | EduPro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/course_portal.css">
</head>
<body>

    <div class="header">
        <div class="logo"><i class="fas fa-graduation-cap"></i> EduPro</div>
        <a href="dashboard.php?page=courses" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Courses</a>
    </div>

    <div class="container">
        <div class="banner">
            <div>
                <h1><?php echo htmlspecialchars($course['course_name']); ?></h1>
                <p>Instructor: <b><?php echo htmlspecialchars($course['instructor']); ?></b></p>
                <p>Category: <b><?php echo htmlspecialchars($course['category']); ?></b></p>
                <p>Welcome, <b><?php echo htmlspecialchars(explode(' ', $fullname)[0]); ?></b></p>
            </div>
            <i class="fas fa-book-open" style="font-size: 80px; opacity: 0.2;"></i>
        </div>

        <div class="grid">
            <div>
                <div class="card">
                    <h2><i class="fas fa-circle-info"></i> Course Overview</h2>
                    <p style="color: #555; line-height: 1.6;">Welcome to your personal course portal. This page contains learning materials, progress tracking, and resources for this semester.</p>
                    
                    <div style="margin-top: 25px;">
                        <h3>Progress</h3>
                        <div class="progress" style="margin: 15px 0;">
                            <div class="progress-fill" style="width:<?php echo $progress; ?>%;"></div>
                        </div>
                        <p><?php echo $progress; ?>% Completed</p>
                    </div>

                    <a class="button" href="<?php echo e(safe_url($course['course_link'])); ?>" target="_blank" rel="noopener noreferrer">
                        <i class="fas fa-book"></i> Open Course Material
                    </a>
                </div>

                <div class="card">
                    <h2><i class="fas fa-user"></i> Detailed Info</h2>
                    <div class="info">
                        <div class="info-box"><span>Instructor</span><h3><?php echo htmlspecialchars($course['instructor']); ?></h3></div>
                        <div class="info-box"><span>Category</span><h3><?php echo htmlspecialchars($course['category']); ?></h3></div>
                        <div class="info-box"><span>Status</span><h3>Active</h3></div>
                        <div class="info-box"><span>Progress</span><h3><?php echo $progress; ?>%</h3></div>
                    </div>
                </div>
            </div>

            <div>
                <div class="sidebar-card">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    <a class="quick-btn" href="<?php echo e(safe_url($course['course_link'])); ?>" target="_blank" rel="noopener noreferrer">Open Material</a>
                    <a class="quick-btn" href="dashboard.php?page=schedule">View Schedule</a>
                </div>

                <div class="sidebar-card">
                    <h3><i class="fas fa-info-circle"></i> Course Card</h3>
                    <ul class="list">
                        <li><strong>Course:</strong> <?php echo htmlspecialchars($course['course_name']); ?></li>
                        <li><strong>Status:</strong> <span class="badge">Active</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>