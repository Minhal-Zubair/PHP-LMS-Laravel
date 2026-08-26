<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

// Fallbacks keep this page safe if the shared limits are unavailable.
defined('MAX_FULLNAME_LEN') || define('MAX_FULLNAME_LEN', 100);
defined('MAX_EMAIL_LEN') || define('MAX_EMAIL_LEN', 255);
defined('MAX_USERNAME_LEN') || define('MAX_USERNAME_LEN', 32);
defined('MAX_PHONE_LEN') || define('MAX_PHONE_LEN', 20);
defined('MAX_PASSWORD_LEN') || define('MAX_PASSWORD_LEN', 255);

// Field-keyed errors so each message can render right under its input,
// instead of one undifferentiated list at the top of the form.
$errors = [];
// Keep submitted values so the user doesn't have to retype everything
// on a validation error (but never re-populate password fields).
$old = [
    'fullname' => '',
    'email' => '',
    'username' => '',
    'phone' => '',
    'gender' => '',
    'address' => '',
];

if (isset($_POST['register_btn'])) {
    csrf_verify();

    // Data hygiene: trim everything, normalize email/username to lowercase
    // so "John@Example.com" and "john@example.com" aren't treated as two
    // different accounts, and cap lengths to match the `users` table
    // columns (see helpers.php MAX_*_LEN) so a too-long value fails with a
    // clear message here instead of a DB truncation/strict-mode error.
    $old['fullname'] = substr(trim($_POST['fullname'] ?? ''), 0, MAX_FULLNAME_LEN);
    $old['email'] = substr(strtolower(trim($_POST['email'] ?? '')), 0, MAX_EMAIL_LEN);
    $old['username'] = substr(strtolower(trim($_POST['username'] ?? '')), 0, MAX_USERNAME_LEN);
    $old['phone'] = substr(trim($_POST['phone'] ?? ''), 0, MAX_PHONE_LEN);
    $old['gender'] = $_POST['gender'] ?? '';
    $old['address'] = trim($_POST['address'] ?? '');
    $password = substr($_POST['password'] ?? '', 0, MAX_PASSWORD_LEN);
    $confirm_password = substr($_POST['confirm_password'] ?? '', 0, MAX_PASSWORD_LEN);

    if ($old['fullname'] === '') {
        $errors['fullname'] = 'Full name is required.';
    }

    if ($old['email'] === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if ($old['username'] === '') {
        $errors['username'] = 'Username is required.';
    } elseif (!preg_match('/^[a-z0-9_.]{3,32}$/', $old['username'])) {
        $errors['username'] = 'Username must be 3-32 characters (letters, numbers, dot, underscore only).';
    }

    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }
    if ($password !== '' && $password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    if (!empty($old['phone']) && !preg_match('/^[0-9+\-\s()]{7,20}$/', $old['phone'])) {
        $errors['phone'] = 'Please enter a valid phone number.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->bind_param('ss', $old['username'], $old['email']);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($exists) {
            // Attribute the error to whichever field actually collided,
            // instead of a generic "one of these is taken" message.
            if (strcasecmp($exists['username'], $old['username']) === 0) {
                $errors['username'] = 'That username is already taken.';
            } else {
                $errors['email'] = 'That email is already registered.';
            }
        }
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO users (fullname, email, username, password, gender, phone, address)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'sssssss',
            $old['fullname'],
            $old['email'],
            $old['username'],
            $hashed_password,
            $old['gender'],
            $old['phone'],
            $old['address']
        );

        if ($stmt->execute()) {
            $stmt->close();
            header('Location: login.php?registered=1');
            exit();
        }

        // Don't leak raw DB error text to the browser.
        error_log('Registration insert failed: ' . $stmt->error);
        $errors['general'] = 'Something went wrong creating your account. Please try again.';
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join EduManage | Student Registration</title>
    
    <!-- Google Fonts -->
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

<section class="register-section">
    <div class="register-container">
        <!-- Left Banner -->
        <div class="register-info">
            <i class="fas fa-user-plus fa-4x" style="margin-bottom: 20px;"></i>
            <h2>Join Our Community</h2>
            <p>Create an account to manage your academic profile, track your grades, and connect with faculty.</p>
        </div>

        <!-- Right Form -->
        <div class="register-form-box">
            <h2>Create Your Account</h2>
            <p>Please enter your details to register.</p>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert" style="grid-column: span 2;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo e($errors['general']); ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="registrationForm">
                <?php echo csrf_field(); ?>
                <div class="form-grid">
                    <!-- Full Name -->
                    <div class="input-group <?php echo isset($errors['fullname']) ? 'has-error' : ''; ?>">
                        <label>Full Name</label>
                        <i class="fas fa-user"></i>
                        <input type="text" name="fullname" placeholder="John Doe" maxlength="<?php echo MAX_FULLNAME_LEN; ?>" value="<?php echo e($old['fullname']); ?>" required>
                        <?php if (isset($errors['fullname'])): ?><span class="field-error"><?php echo e($errors['fullname']); ?></span><?php endif; ?>
                    </div>

                    <!-- Email -->
                    <div class="input-group <?php echo isset($errors['email']) ? 'has-error' : ''; ?>">
                        <label>Email Address</label>
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="name@example.com" maxlength="<?php echo MAX_EMAIL_LEN; ?>" value="<?php echo e($old['email']); ?>" required>
                        <?php if (isset($errors['email'])): ?><span class="field-error"><?php echo e($errors['email']); ?></span><?php endif; ?>
                    </div>

                    <!-- Username -->
                    <div class="input-group <?php echo isset($errors['username']) ? 'has-error' : ''; ?>">
                        <label>Username</label>
                        <i class="fas fa-at"></i>
                        <input type="text" name="username" placeholder="johndoe123" maxlength="<?php echo MAX_USERNAME_LEN; ?>" value="<?php echo e($old['username']); ?>" required>
                        <?php if (isset($errors['username'])): ?>
                            <span class="field-error"><?php echo e($errors['username']); ?></span>
                        <?php else: ?>
                            <span class="field-hint">3-32 characters, letters/numbers/dot/underscore only</span>
                        <?php endif; ?>
                    </div>

                    <!-- Phone -->
                    <div class="input-group <?php echo isset($errors['phone']) ? 'has-error' : ''; ?>">
                        <label>Phone Number</label>
                        <i class="fas fa-phone"></i>
                        <input type="text" name="phone" placeholder="03XXXXXXXXX" maxlength="<?php echo MAX_PHONE_LEN; ?>" value="<?php echo e($old['phone']); ?>">
                        <?php if (isset($errors['phone'])): ?><span class="field-error"><?php echo e($errors['phone']); ?></span><?php endif; ?>
                    </div>

                    <!-- Password -->
                    <div class="input-group <?php echo isset($errors['password']) ? 'has-error' : ''; ?>">
                        <label>Password</label>
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="pass" placeholder="••••••••" minlength="8" required>
                        <?php if (isset($errors['password'])): ?>
                            <span class="field-error"><?php echo e($errors['password']); ?></span>
                        <?php else: ?>
                            <span class="field-hint">At least 8 characters</span>
                        <?php endif; ?>
                    </div>

                    <!-- Confirm Password -->
                    <div class="input-group <?php echo isset($errors['confirm_password']) ? 'has-error' : ''; ?>">
                        <label>Confirm Password</label>
                        <i class="fas fa-check-circle"></i>
                        <input type="password" name="confirm_password" id="confirm_pass" placeholder="••••••••" minlength="8" required>
                        <span class="field-error" id="confirm-pass-live-error" style="display:none;">Passwords do not match</span>
                        <?php if (isset($errors['confirm_password'])): ?><span class="field-error"><?php echo e($errors['confirm_password']); ?></span><?php endif; ?>
                    </div>

                    <!-- Gender -->
                    <div class="input-group">
                        <label>Gender</label>
                        <i class="fas fa-venus-mars"></i>
                        <select name="gender" style="padding-left: 35px;">
                            <option value="">Select</option>
                            <option <?php echo $old['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option <?php echo $old['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>

                    <!-- Address -->
                    <div class="input-group full-width">
                        <label>Address</label>
                        <i class="fas fa-map-marker-alt"></i>
                        <textarea name="address" rows="2" placeholder="Street, City, Country" style="padding-left: 35px;"><?php echo e($old['address']); ?></textarea>
                    </div>
                </div>

                <button type="submit" name="register_btn">
                    <i class="fas fa-paper-plane"></i> Register Now
                </button>
            </form>

            <p class="login-link">
                Already have an account? <a href="login.php">Login Here</a>
            </p>
        </div>
    </div>
</section>

<footer>
    &copy; <?php echo date("Y"); ?> EduManage | All Rights Reserved
</footer>

<script>
    // Progressive enhancement only — the real check is server-side (see
    // register.php). This just gives instant feedback before submitting.
    (function () {
        var pass = document.getElementById('pass');
        var confirm = document.getElementById('confirm_pass');
        var liveError = document.getElementById('confirm-pass-live-error');
        if (!pass || !confirm || !liveError) return;

        function checkMatch() {
            if (confirm.value.length > 0 && confirm.value !== pass.value) {
                liveError.style.display = 'block';
                confirm.closest('.input-group').classList.add('has-error');
            } else {
                liveError.style.display = 'none';
                confirm.closest('.input-group').classList.remove('has-error');
            }
        }
        pass.addEventListener('input', checkMatch);
        confirm.addEventListener('input', checkMatch);
    })();
</script>

</body>
</html>