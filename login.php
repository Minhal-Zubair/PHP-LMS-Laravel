<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$message = "";
$message_type = "";

if (isset($_GET['registered'])) {
    $message = "Registration successful! You can now log in.";
    $message_type = "success";
}

// If a verified remember-me cookie is present but there's no session yet,
// log the user back in via the DB-checked token (never trust raw cookie
// values directly — see helpers.php::verify_remember_token()).
if (!isset($_SESSION['user_id'])) {
    $remembered = verify_remember_token($conn);
    if ($remembered) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $remembered['id'];
        $_SESSION['username'] = $remembered['username'];
        $_SESSION['fullname'] = $remembered['fullname'];
        header("Location: dashboard.php");
        exit();
    }
}

// Simple brute-force throttle: 5 failed attempts -> 60s cooldown.
// (Session-based, so it's per-browser, not a full rate limiter — but it
// stops the trivial case of hammering the login form.)
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_locked_until'] = 0;
}
$is_locked = time() < $_SESSION['login_locked_until'];

if (isset($_POST['login_btn']) && !$is_locked) {
    csrf_verify();

    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, username, fullname, password FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    // Deliberately vague message either way — don't reveal whether the
    // username exists (the old code said "Username not found" vs
    // "Incorrect password", which lets attackers enumerate valid usernames).
    if ($row && password_verify($password, $row['password'])) {
        $_SESSION['login_attempts'] = 0;
        session_regenerate_id(true);

        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['fullname'] = $row['fullname'];

        if (isset($_POST['remember'])) {
            issue_remember_token($conn, (int) $row['id']);
        }

        header("Location: index.php");
        exit();
    } else {
        $_SESSION['login_attempts']++;
        if ($_SESSION['login_attempts'] >= 5) {
            $_SESSION['login_locked_until'] = time() + 60;
            $_SESSION['login_attempts'] = 0;
        }
        $message = "Incorrect username or password.";
        $message_type = "error";
    }
} elseif ($is_locked) {
    $message = "Too many failed attempts. Please wait a minute and try again.";
    $message_type = "error";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | EduManage System</title>

    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/auth.css">
</head>

<body>

    <nav>
        <a href="index.php" class="logo"><i class="fas fa-graduation-cap"></i> EduManage</a>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="register.php">Register</a>
        </div>
    </nav>

    <section class="login-section">
        <div class="login-card">
            <i class="fas fa-user-circle main-icon"></i>
            <h2>Welcome Back</h2>
            <p>Enter your credentials to access your account</p>

            <!-- PHP Alert Message -->
            <?php if ($message != ""): ?>
                <div class="alert" <?php echo $message_type === 'success' ? 'style="background:#d4edda;color:#155724;border-color:#c3e6cb;"' : ''; ?>>
                    <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i> <?php echo e($message); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <?php echo csrf_field(); ?>
                <div class="input-group">
                    <label>Username</label>
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="Enter username" maxlength="50" required>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Enter password" required>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #666; font-weight: normal;">
                        <input type="checkbox" name="remember" id="remember" style="width: auto;"> Remember Me
                    </label>
                    <a href="forgot_password.php" style="font-size: 13px; color: var(--primary-color); text-decoration: none; font-weight: 600;">Forgot password?</a>
                </div>

                <button type="submit" name="login_btn" class="login-btn">
                    Login
                </button>
            </form>

            <p class="register-link">
                Don't have an account? <a href="register.php">Register Now</a>
            </p>
        </div>
    </section>

    <footer>
        &copy; <?php echo date("Y"); ?> EduManage | Secure Student Portal
    </footer>

</body>

</html>