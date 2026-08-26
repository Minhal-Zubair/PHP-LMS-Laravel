<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

// Keep this page functional even when the helper is not available in helpers.php.
if (!function_exists('issue_password_reset_token')) {
    function issue_password_reset_token(mysqli $conn, int $user_id): string
    {
        $token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $token);
        $expires_at = date('Y-m-d H:i:s', time() + 3600);

        $stmt = $conn->prepare(
            'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)'
        );
        $stmt->bind_param('iss', $user_id, $token_hash, $expires_at);
        $stmt->execute();
        $stmt->close();

        return $token;
    }
}

// Keep the local/dev flow functional when no mail helper is configured.
if (!function_exists('send_password_reset_email')) {
    function send_password_reset_email(string $email, string $resetLink): bool
    {
        return false;
    }
}

$message = "";
$message_type = "";
$reset_link_for_dev = null;

if (isset($_POST['forgot_btn'])) {
    csrf_verify();
    $email = strtolower(trim($_POST['email'] ?? ''));

    // Always show the same message whether or not the email exists —
    // otherwise this endpoint becomes an account-enumeration tool
    // (attacker submits emails one at a time and watches which ones
    // say "sent" vs "not found").
    $message = "If an account exists for that email, a password reset link has been generated.";
    $message_type = "success";

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conn->prepare("SELECT id, email FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user) {
            $token = issue_password_reset_token($conn, (int) $user['id']);
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            // Build relative to this script's own directory rather than
            // assuming the app is served from the domain root — works the
            // same under `php -S` (root) and XAMPP/Apache subfolder setups
            // (e.g. localhost/practice/Project-1/).
            $base_dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            $resetLink = $scheme . '://' . $_SERVER['HTTP_HOST'] . $base_dir . '/reset_password.php?token=' . urlencode($token);

            $sent = send_password_reset_email($user['email'], $resetLink);

            if (!$sent) {
                // Dev/local fallback — no mail server configured (typical
                // on XAMPP/localhost). Show the link directly so the flow
                // is testable without setting up real email.
                $reset_link_for_dev = $resetLink;
            }
        }
        // If no user matches: do nothing, but still show the generic
        // success message above — this is intentional, not a bug.
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | EduManage System</title>

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
            <i class="fas fa-key main-icon"></i>
            <h2>Forgot Password</h2>
            <p>Enter your account email and we'll send you a reset link.</p>

            <?php if ($message != ""): ?>
                <div class="alert <?php echo $message_type === 'success' ? 'alert-success' : ''; ?>">
                    <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i> <?php echo e($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($reset_link_for_dev): ?>
                <div class="alert" style="word-break: break-all;">
                    <i class="fas fa-info-circle"></i>
                    <strong>Dev mode</strong> — no mail server is configured, so here's the link directly
                    (in production this would be emailed instead):<br><br>
                    <a href="<?php echo e($reset_link_for_dev); ?>"><?php echo e($reset_link_for_dev); ?></a>
                </div>
            <?php endif; ?>

            <?php if (!$reset_link_for_dev): ?>
                <form action="forgot_password.php" method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="input-group">
                        <label>Email Address</label>
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Enter your account email" maxlength="<?php echo MAX_EMAIL_LEN; ?>" required>
                    </div>

                    <button type="submit" name="forgot_btn" class="login-btn">
                        Send Reset Link
                    </button>
                </form>
            <?php endif; ?>

            <p class="register-link">
                Remembered your password? <a href="login.php">Back to Login</a>
            </p>
        </div>
    </section>

    <footer>
        &copy; <?php echo date("Y"); ?> EduManage | Secure Student Portal
    </footer>

</body>

</html>