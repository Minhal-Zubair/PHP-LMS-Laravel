<?php
require_once('auth.php');
require_once('db.php');

// Ensure fullname exists to avoid explode errors
$fullname = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : 'Student';
$user_id = $_SESSION['user_id'];

// 1. Check if ID is in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: No course ID provided in the URL. Use course_portal.php?id=YOUR_ID");
}

$course_id = (int)$_GET['id'];

// 2. Fetch the course
$query = mysqli_query($conn, "SELECT * FROM courses WHERE id='$course_id' AND user_id='$user_id'");

if (mysqli_num_rows($query) == 0) {
    die("Error: Course not found or you do not have permission to view it.");
}

$course = mysqli_fetch_assoc($query);

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
    <style>
        /* Keep your existing CSS here */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #eef3fb; }
        .header { background: #4e73df; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; color: white; box-shadow: 0 4px 15px rgba(0,0,0,.15); }
        .logo { font-size: 25px; font-weight: 700; }
        .back-btn { text-decoration: none; color: white; padding: 10px 18px; background: rgba(255,255,255,.15); border-radius: 8px; transition: .3s; }
        .back-btn:hover { background: white; color: #4e73df; }
        .container { width: 90%; margin: 40px auto; }
        .banner { background: linear-gradient(135deg, #4e73df, #224abe); padding: 40px; border-radius: 20px; display: flex; justify-content: space-between; align-items: center; color: white; margin-bottom: 30px; }
        .banner h1 { font-size: 36px; margin-bottom: 15px; }
        .grid { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; }
        .card { background: white; padding: 25px; border-radius: 18px; box-shadow: 0 5px 18px rgba(0,0,0,.08); margin-bottom: 25px; }
        .info { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .info-box { background: #f8f9fd; padding: 20px; border-radius: 12px; }
        .progress { height: 12px; background: #ddd; border-radius: 20px; overflow: hidden; }
        .progress-fill { height: 100%; background: #00ba94; transition: 0.5s; }
        .button { display: inline-block; padding: 12px 22px; background: #4e73df; color: white; text-decoration: none; border-radius: 8px; margin-top: 20px; font-weight: 600; }
        .sidebar-card { background: white; padding: 25px; border-radius: 18px; box-shadow: 0 5px 18px rgba(0,0,0,.08); margin-bottom: 25px; }
        .list { list-style: none; }
        .list li { padding: 12px 0; border-bottom: 1px solid #eee; }
        .badge { display: inline-block; padding: 6px 14px; background: #4e73df; color: white; font-size: 12px; border-radius: 30px; }
        .quick-btn { display: block; text-align: center; padding: 12px; background: #4e73df; color: white; text-decoration: none; margin-top: 12px; border-radius: 10px; font-weight: 600; }
    </style>
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

                    <a class="button" href="<?php echo htmlspecialchars($course['course_link']); ?>" target="_blank">
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
                    <a class="quick-btn" href="<?php echo htmlspecialchars($course['course_link']); ?>" target="_blank">Open Material</a>
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