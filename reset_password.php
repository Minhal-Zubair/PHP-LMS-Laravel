<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$errors = [];
$message = "";
$message_type = "";
$done = false;

$user_id = verify_password_reset_token($conn, $token);

if (!$user_id) {
    $message = "This reset link is invalid or has expired. Please request a new one.";
    $message_type = "error";
} elseif (isset($_POST['reset_btn'])) {
    csrf_verify();
    $password = substr($_POST['password'] ?? '', 0, MAX_PASSWORD_LEN);
    $confirm_password = substr($_POST['confirm_password'] ?? '', 0, MAX_PASSWORD_LEN);

    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param('si', $hashed, $user_id);
        $stmt->execute();
        $stmt->close();

        // One-time use only.
        consume_password_reset_token($conn, $token);

        // A password reset should also kill any "remember me" sessions on
        // other devices — otherwise someone who had a stolen remember-me
        // cookie stays logged in even after the password changes.
        invalidate_all_remember_tokens($conn, $user_id);

        $done = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | EduManage System</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/auth.css">
</head>

<body>

    <nav>
        <a href="index.php" class="logo"><i class="fas fa-graduation-cap"></i> EduManage</a>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="login.php">Login</a>
        </div>
    </nav>

    <section class="login-section">
        <div class="login-card">
            <i class="fas fa-lock main-icon"></i>
            <h2>Reset Password</h2>

            <?php if ($done): ?>
                <p>Your password has been updated.</p>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> You can now log in with your new password.
                </div>
                <p class="register-link">
                    <a href="login.php">Go to Login</a>
                </p>

            <?php elseif (!$user_id): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo e($message); ?>
                </div>
                <p class="register-link">
                    <a href="forgot_password.php">Request a new reset link</a>
                </p>

            <?php else: ?>
                <p>Choose a new password for your account.</p>

                <?php if (!empty($errors['general'])): ?>
                    <div class="alert"><i class="fas fa-exclamation-circle"></i> <?php echo e($errors['general']); ?></div>
                <?php endif; ?>

                <form action="reset_password.php?token=<?php echo urlencode($token); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="token" value="<?php echo e($token); ?>">

                    <div class="input-group <?php echo isset($errors['password']) ? 'has-error' : ''; ?>">
                        <label>New Password</label>
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="pass" placeholder="••••••••" minlength="8" required>
                        <?php if (isset($errors['password'])): ?>
                            <span class="field-error"><?php echo e($errors['password']); ?></span>
                        <?php else: ?>
                            <span class="field-hint">At least 8 characters</span>
                        <?php endif; ?>
                    </div>

                    <div class="input-group <?php echo isset($errors['confirm_password']) ? 'has-error' : ''; ?>">
                        <label>Confirm New Password</label>
                        <i class="fas fa-check-circle"></i>
                        <input type="password" name="confirm_password" id="confirm_pass" placeholder="••••••••" minlength="8" required>
                        <span class="field-error" id="confirm-pass-live-error" style="display:none;">Passwords do not match</span>
                        <?php if (isset($errors['confirm_password'])): ?><span class="field-error"><?php echo e($errors['confirm_password']); ?></span><?php endif; ?>
                    </div>

                    <button type="submit" name="reset_btn" class="login-btn">
                        Update Password
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </section>

    <footer>
        &copy; <?php echo date("Y"); ?> EduManage | Secure Student Portal
    </footer>

    <script>
        (function () {
            var pass = document.getElementById('pass');
            var confirm = document.getElementById('confirm_pass');
            var liveError = document.getElementById('confirm-pass-live-error');
            if (!pass || !confirm || !liveError) return;

            function checkMatch() {
                if (confirm.value.length > 0 && confirm.value !== pass.value) {
                    liveError.style.display = 'block';
                } else {
                    liveError.style.display = 'none';
                }
            }
            pass.addEventListener('input', checkMatch);
            confirm.addEventListener('input', checkMatch);
        })();
    </script>

</body>

</html>